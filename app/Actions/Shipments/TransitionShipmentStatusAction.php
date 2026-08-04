<?php

namespace App\Actions\Shipments;

use App\Enums\ShipmentStatus;
use App\Exceptions\ShipmentException;
use App\Models\Shipment;
use App\Models\User;

class TransitionShipmentStatusAction
{
    /**
     * Move a shipment to a new status, enforcing the transition map and
     * recording the change in the status history audit table.
     */
    public function execute(Shipment $shipment, ShipmentStatus $to, ?User $actor = null, ?string $notes = null): Shipment
    {
        $from = $shipment->status;

        if (! $from->canTransitionTo($to)) {
            throw ShipmentException::invalidTransition($from, $to);
        }

        $shipment->status = $to;
        $shipment->save();

        $shipment->statusHistories()->create([
            'from_status' => $from,
            'to_status' => $to,
            'actor_id' => $actor?->id,
            'notes' => $notes,
        ]);

        return $shipment;
    }
}
