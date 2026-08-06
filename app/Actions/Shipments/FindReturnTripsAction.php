<?php

namespace App\Actions\Shipments;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Database\Eloquent\Collection;
use MatanYadaev\EloquentSpatial\Objects\Point;

class FindReturnTripsAction
{
    /**
     * Find open shipments that would fill the truck on its way back: their
     * origin near this shipment's destination, their destination near this
     * shipment's origin, picking up within the configured window.
     *
     * @return Collection<int, Shipment>
     */
    public function execute(Shipment $shipment, ?int $radiusM = null, ?int $windowHours = null): Collection
    {
        $radiusM ??= (int) config('marketplace.return_trip.radius_m');
        $windowHours ??= (int) config('marketplace.return_trip.window_hours');

        return Shipment::query()
            ->whereKeyNot($shipment->id)
            ->whereIn('status', [ShipmentStatus::Published, ShipmentStatus::Quoted])
            ->whereRaw('ST_DWithin(origin, ST_GeographyFromText(?), ?)', [
                $this->ewkt($shipment->destination), $radiusM,
            ])
            ->whereRaw('ST_DWithin(destination, ST_GeographyFromText(?), ?)', [
                $this->ewkt($shipment->origin), $radiusM,
            ])
            ->whereBetween('pickup_date', [
                $shipment->pickup_date,
                $shipment->pickup_date->copy()->addHours($windowHours),
            ])
            ->orderBy('pickup_date')
            ->get();
    }

    /**
     * Compact payload of up to $limit return trips for a job row.
     *
     * @return array<int, array<string, mixed>>
     */
    public function summaryFor(Shipment $shipment, int $limit = 3): array
    {
        $activeStatuses = [
            ShipmentStatus::Accepted,
            ShipmentStatus::DriverAssigned,
            ShipmentStatus::PickedUp,
            ShipmentStatus::InTransit,
        ];

        if (! in_array($shipment->status, $activeStatuses, true)) {
            return [];
        }

        return $this->execute($shipment)
            ->take($limit)
            ->load(['originCity:id,name', 'destinationCity:id,name'])
            ->map(fn (Shipment $trip) => [
                'id' => $trip->id,
                'origin_city' => $trip->originLabel(),
                'destination_city' => $trip->destinationLabel(),
                'pickup_date' => $trip->pickup_date->toDateString(),
                'budget_amount' => $trip->budget_amount,
                'currency' => $trip->currency,
            ])
            ->values()
            ->all();
    }

    private function ewkt(Point $point): string
    {
        return sprintf('SRID=4326;POINT(%s %s)', $point->longitude, $point->latitude);
    }
}
