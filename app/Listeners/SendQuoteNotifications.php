<?php

namespace App\Listeners;

use App\Events\QuoteAccepted;
use App\Events\QuoteSubmitted;
use App\Notifications\QuoteAcceptedNotification;
use App\Notifications\QuoteReceived;

class SendQuoteNotifications
{
    public function handleSubmitted(QuoteSubmitted $event): void
    {
        $quote = $event->quote->load(['shipment.originCity:id,name', 'shipment.destinationCity:id,name', 'transporterProfile.user']);

        $quote->shipment->customer->notify(new QuoteReceived($quote));
    }

    public function handleAccepted(QuoteAccepted $event): void
    {
        $quote = $event->quote->load(['shipment.originCity:id,name', 'shipment.destinationCity:id,name', 'transporterProfile.user']);

        $quote->transporterProfile->user->notify(new QuoteAcceptedNotification($quote));
    }
}
