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
     * Fan out to transporters whose operating regions cover the origin and
     * whose fleet matches the requested truck type (when one is set).
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
                ->whereHas('trucks', fn ($trucks) => $trucks
                    ->where('truck_type_id', $shipment->truck_type_id)
                    ->where('availability', '!=', TruckAvailability::Inactive)))
            ->with('user')
            ->chunkById(100, function (Collection $profiles) use ($shipment) {
                Notification::send($profiles->pluck('user'), new NewLoadAvailable($shipment));
            });
    }
}
