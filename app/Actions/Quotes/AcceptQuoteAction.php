<?php

namespace App\Actions\Quotes;

use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\QuoteStatus;
use App\Enums\ShipmentStatus;
use App\Events\QuoteAccepted;
use App\Exceptions\ShipmentException;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AcceptQuoteAction
{
    public function __construct(private TransitionShipmentStatusAction $transition) {}

    /**
     * Accept a quote on behalf of the customer. Locks the shipment row so a
     * concurrent accept (double-tap, or accept-vs-withdraw) waits and then
     * fails the status re-check cleanly. Competing quotes are rejected.
     */
    public function execute(User $customer, Quote $quote): Shipment
    {
        $shipment = DB::transaction(function () use ($customer, $quote) {
            $shipment = Shipment::query()->whereKey($quote->shipment_id)->lockForUpdate()->firstOrFail();

            if ($shipment->customer_id !== $customer->id) {
                throw ShipmentException::notShipmentOwner();
            }

            if ($shipment->status !== ShipmentStatus::Quoted) {
                throw ShipmentException::invalidTransition($shipment->status, ShipmentStatus::Accepted);
            }

            $quote->refresh();

            if ($quote->status !== QuoteStatus::Pending) {
                throw ShipmentException::quoteNotPending();
            }

            $quote->update(['status' => QuoteStatus::Accepted]);

            $shipment->quotes()
                ->whereKeyNot($quote->id)
                ->where('status', QuoteStatus::Pending)
                ->update(['status' => QuoteStatus::Rejected]);

            $shipment->accepted_quote_id = $quote->id;
            $shipment->assigned_transporter_id = $quote->transporter_profile_id;
            $shipment->assigned_truck_id = $quote->truck_id;

            $this->transition->execute($shipment, ShipmentStatus::Accepted, $customer);

            return $shipment;
        });

        QuoteAccepted::dispatch($quote);

        return $shipment;
    }
}
