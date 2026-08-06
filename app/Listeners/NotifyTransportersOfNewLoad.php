<?php

namespace App\Listeners;

use App\Enums\TruckAvailability;
use App\Events\ShipmentPublished;
use App\Models\TransporterProfile;
use App\Notifications\NewLoadAvailable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class NotifyTransportersOfNewLoad implements ShouldQueue
{
    public string $queue = 'notifications';

    /**
     * Fan out to transporters whose operating regions cover the origin.
     * When the shipment requests a truck type, transporters who own trucks
     * are filtered to matching fleets - but truck-less (onboarding)
     * transporters still hear about every load in their regions.
     */
    public function handle(ShipmentPublished $event): void
    {
        $shipment = $event->shipment->load(['originCity:id,name', 'destinationCity:id,name']);

        $origin = sprintf(
            'SRID=4326;POINT(%s %s)',
            $shipment->origin->longitude,
            $shipment->origin->latitude,
        );

        TransporterProfile::query()
            ->where('user_id', '!=', $shipment->customer_id)
            ->whereHas('operatingRegions', fn ($regions) => $regions
                ->whereRaw('ST_DWithin(center, ST_GeographyFromText(?), radius_m)', [$origin]))
            ->when($shipment->truck_type_id !== null, fn ($query) => $query
                ->where(fn ($matching) => $matching
                    ->whereDoesntHave('trucks')
                    ->orWhereHas('trucks', fn ($trucks) => $trucks
                        ->where('truck_type_id', $shipment->truck_type_id)
                        ->where('availability', '!=', TruckAvailability::Inactive))))
            ->with('user')
            ->chunkById(100, function (Collection $profiles) use ($shipment) {
                Notification::send($profiles->pluck('user'), new NewLoadAvailable($shipment));
            });
    }
}
