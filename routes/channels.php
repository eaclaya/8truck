<?php

use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

/*
 * Live shipment updates: only the customer who owns the shipment and the
 * assigned transporter may listen.
 */
Broadcast::channel('shipments.{shipmentId}', function (User $user, int $shipmentId) {
    $shipment = Shipment::query()->find($shipmentId);

    if ($shipment === null) {
        return false;
    }

    return $shipment->customer_id === $user->id
        || ($user->transporterProfile !== null
            && $shipment->assigned_transporter_id === $user->transporterProfile->id);
});
