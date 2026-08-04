<?php

namespace App\Actions\Loads;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FindAvailableLoadsAction
{
    /**
     * Open shipments a transporter can quote: published or quoted, not their
     * own, picking up today or later. When $onlyMyRegions is set, the origin
     * must fall inside one of the transporter's operating regions (PostGIS).
     *
     * @return Builder<Shipment>
     */
    public function execute(TransporterProfile $transporter, bool $onlyMyRegions = true): Builder
    {
        $query = Shipment::query()
            ->whereIn('status', [ShipmentStatus::Published, ShipmentStatus::Quoted])
            ->where('customer_id', '!=', $transporter->user_id)
            ->whereDate('pickup_date', '>=', today())
            ->withExists([
                'quotes as has_my_quote' => fn ($quotes) => $quotes
                    ->where('transporter_profile_id', $transporter->id),
            ])
            ->orderBy('pickup_date');

        if ($onlyMyRegions) {
            $query->whereExists(function ($sub) use ($transporter) {
                $sub->select(DB::raw(1))
                    ->from('operating_regions')
                    ->where('transporter_profile_id', $transporter->id)
                    ->whereRaw('ST_DWithin(shipments.origin, operating_regions.center, operating_regions.radius_m)');
            });
        }

        return $query;
    }
}
