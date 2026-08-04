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

    private function ewkt(Point $point): string
    {
        return sprintf('SRID=4326;POINT(%s %s)', $point->longitude, $point->latitude);
    }
}
