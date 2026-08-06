<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentStatusAdvanced implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Shipment $shipment) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('shipments.'.$this->shipment->id);
    }

    /**
     * Keep the payload minimal: subscribers reload fresh props via Inertia.
     *
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['shipment_id' => $this->shipment->id];
    }
}
