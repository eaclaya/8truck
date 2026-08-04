<?php

namespace App\Actions\Shipments;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateShipmentAction
{
    /**
     * Create a draft shipment for a customer.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(User $customer, array $data): Shipment
    {
        return DB::transaction(function () use ($customer, $data) {
            $shipment = Shipment::create([
                ...$data,
                'customer_id' => $customer->id,
                'company_id' => $customer->company?->id,
            ]);

            $shipment->statusHistories()->create([
                'from_status' => null,
                'to_status' => ShipmentStatus::Draft,
                'actor_id' => $customer->id,
            ]);

            return $shipment;
        });
    }
}
