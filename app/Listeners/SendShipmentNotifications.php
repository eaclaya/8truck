<?php

namespace App\Listeners;

use App\Events\ShipmentCompleted;
use App\Events\ShipmentRated;
use App\Events\ShipmentStatusAdvanced;
use App\Notifications\RatingReceived;
use App\Notifications\ShipmentCompletedNotification;
use App\Notifications\ShipmentStatusUpdated;

class SendShipmentNotifications
{
    public function handleStatusAdvanced(ShipmentStatusAdvanced $event): void
    {
        $shipment = $event->shipment->load(['originCity:id,name', 'destinationCity:id,name']);

        $shipment->customer->notify(new ShipmentStatusUpdated($shipment));
    }

    public function handleCompleted(ShipmentCompleted $event): void
    {
        $shipment = $event->shipment->load(['originCity:id,name', 'destinationCity:id,name', 'assignedTransporter.user']);

        $shipment->assignedTransporter?->user->notify(new ShipmentCompletedNotification($shipment));
    }

    public function handleRated(ShipmentRated $event): void
    {
        $rating = $event->rating->load(['rater', 'ratee', 'shipment']);

        $rating->ratee->notify(new RatingReceived($rating));
    }
}
