<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class QuoteAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Quote $quote)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.quote_accepted.title'))
            ->line(__('notifications.quote_accepted.body', $this->bodyParams()))
            ->action(__('notifications.action'), route('jobs.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.quote_accepted.title'),
            'body' => __('notifications.quote_accepted.body', $this->bodyParams()),
            'url' => route('jobs.index', absolute: false),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function bodyParams(): array
    {
        $shipment = $this->quote->shipment;

        return [
            'amount' => $this->quote->currency.' '.number_format((float) $this->quote->amount, 2),
            'origin' => $shipment->originLabel(),
            'destination' => $shipment->destinationLabel(),
        ];
    }
}
