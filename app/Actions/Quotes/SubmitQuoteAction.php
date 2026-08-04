<?php

namespace App\Actions\Quotes;

use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\QuoteStatus;
use App\Enums\ShipmentStatus;
use App\Exceptions\ShipmentException;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\TransporterProfile;
use Illuminate\Support\Facades\DB;

class SubmitQuoteAction
{
    public function __construct(private TransitionShipmentStatusAction $transition) {}

    /**
     * Submit a transporter's quote for a published shipment. The first quote
     * moves the shipment from Published to Quoted.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(TransporterProfile $transporter, Shipment $shipment, array $data): Quote
    {
        if ($shipment->customer_id === $transporter->user_id) {
            throw ShipmentException::ownShipment();
        }

        return DB::transaction(function () use ($transporter, $shipment, $data) {
            $shipment = Shipment::query()->whereKey($shipment->id)->lockForUpdate()->firstOrFail();

            if (! $shipment->status->isOpenForQuotes()) {
                throw ShipmentException::notOpenForQuotes($shipment->status);
            }

            $alreadyQuoted = $shipment->quotes()
                ->where('transporter_profile_id', $transporter->id)
                ->exists();

            if ($alreadyQuoted) {
                throw ShipmentException::alreadyQuoted();
            }

            $quote = $shipment->quotes()->create([
                ...$data,
                'transporter_profile_id' => $transporter->id,
                'status' => QuoteStatus::Pending,
                'currency' => $data['currency'] ?? $shipment->currency,
            ]);

            if ($shipment->status === ShipmentStatus::Published) {
                $this->transition->execute($shipment, ShipmentStatus::Quoted, $transporter->user);
            }

            return $quote;
        });
    }
}
