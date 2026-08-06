<?php

namespace App\Actions\Shipments;

use App\Enums\ShipmentStatus;
use App\Events\ShipmentCompleted;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteShipmentAction
{
    public function __construct(private TransitionShipmentStatusAction $transition) {}

    /**
     * Complete a delivered shipment and record the commission shadow ledger
     * row (0% during MVP validation, see config/marketplace.php).
     */
    public function execute(Shipment $shipment, ?User $actor = null): Shipment
    {
        $shipment = DB::transaction(function () use ($shipment, $actor) {
            $shipment->completed_at = now();

            $this->transition->execute($shipment, ShipmentStatus::Completed, $actor);

            $quote = $shipment->acceptedQuote;

            if ($quote !== null) {
                $rate = (float) config('marketplace.commission_rate');

                $shipment->commission()->create([
                    'transporter_profile_id' => $quote->transporter_profile_id,
                    'base_amount' => $quote->amount,
                    'rate' => $rate,
                    'fee_amount' => round((float) $quote->amount * $rate, 2),
                    'currency' => $quote->currency,
                ]);
            }

            return $shipment;
        });

        ShipmentCompleted::dispatch($shipment);

        return $shipment;
    }
}
