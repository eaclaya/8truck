<?php

namespace App\Policies;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;

class ShipmentPolicy
{
    /**
     * Admins manage everything through the Filament panel.
     */
    public function before(User $user): ?bool
    {
        return $user->hasRole('admin') ? true : null;
    }

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

    public function complete(User $user, Shipment $shipment): bool
    {
        return $shipment->customer_id === $user->id
            && $shipment->status === ShipmentStatus::Delivered;
    }

    /**
     * A transporter may browse an open load that is not their own shipment.
     */
    public function viewAsLoad(User $user, Shipment $shipment): bool
    {
        return $user->transporterProfile !== null
            && $shipment->customer_id !== $user->id
            && ($shipment->status->isOpenForQuotes()
                || $shipment->quotes()->where('transporter_profile_id', $user->transporterProfile->id)->exists());
    }

    /**
     * Participants of a completed shipment may rate the counterparty once.
     */
    public function rate(User $user, Shipment $shipment): bool
    {
        if ($shipment->status !== ShipmentStatus::Completed) {
            return false;
        }

        $isParticipant = $shipment->customer_id === $user->id
            || ($user->transporterProfile !== null
                && $shipment->assigned_transporter_id === $user->transporterProfile->id);

        return $isParticipant
            && ! $shipment->ratings()->where('rater_id', $user->id)->exists();
    }

    /**
     * The assigned transporter attaches proof of delivery while the cargo
     * is on the road or just delivered.
     */
    public function uploadPod(User $user, Shipment $shipment): bool
    {
        return $this->advance($user, $shipment)
            && in_array($shipment->status, [ShipmentStatus::InTransit, ShipmentStatus::Delivered], true);
    }

    /**
     * The assigned transporter may advance the delivery status.
     */
    public function advance(User $user, Shipment $shipment): bool
    {
        return $user->transporterProfile !== null
            && $shipment->assigned_transporter_id === $user->transporterProfile->id;
    }
}
