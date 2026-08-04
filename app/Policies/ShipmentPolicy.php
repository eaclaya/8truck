<?php

namespace App\Policies;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    public function view(User $user, Shipment $shipment): bool
    {
        return $shipment->customer_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function publish(User $user, Shipment $shipment): bool
    {
        return $shipment->customer_id === $user->id
            && $shipment->status === ShipmentStatus::Draft;
    }

    public function acceptQuote(User $user, Shipment $shipment): bool
    {
        return $shipment->customer_id === $user->id
            && $shipment->status === ShipmentStatus::Quoted;
    }
}
