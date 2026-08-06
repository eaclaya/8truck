<?php

namespace App\Events;

use App\Models\Quote;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class QuoteAccepted implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public Quote $quote) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('shipments.'.$this->quote->shipment_id);
    }

    /**
     * Keep the payload minimal: subscribers reload fresh props via Inertia.
     *
     * @return array<string, int>
     */
    public function broadcastWith(): array
    {
        return ['shipment_id' => $this->quote->shipment_id];
    }
}
