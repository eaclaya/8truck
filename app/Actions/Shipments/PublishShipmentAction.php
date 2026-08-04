<?php

namespace App\Actions\Shipments;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PublishShipmentAction
{
    public function __construct(private TransitionShipmentStatusAction $transition) {}

    /**
     * Publish a draft shipment so transporters can quote it. The shipment
     * expires at the end of its pickup date if nothing has been accepted.
     */
    public function execute(Shipment $shipment, ?User $actor = null): Shipment
    {
        return DB::transaction(function () use ($shipment, $actor) {
            $shipment->published_at = now();
            $shipment->expires_at = $shipment->pickup_date->endOfDay();

            return $this->transition->execute($shipment, ShipmentStatus::Published, $actor);
        });
    }
}
