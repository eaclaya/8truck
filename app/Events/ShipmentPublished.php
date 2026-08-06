<?php

namespace App\Events;

use App\Models\Shipment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShipmentPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Shipment $shipment) {}
}
