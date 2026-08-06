<?php

namespace App\Notifications;

use App\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentStatusUpdated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Shipment $shipment)
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
            ->subject(__('notifications.status_updated.title'))
            ->line(__('notifications.status_updated.body', $this->bodyParams()))
            ->action(__('notifications.action'), route('shipments.show', $this->shipment));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.status_updated.title'),
            'body' => __('notifications.status_updated.body', $this->bodyParams()),
            'url' => route('shipments.show', $this->shipment, absolute: false),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function bodyParams(): array
    {
        return [
            'origin' => $this->shipment->originLabel(),
            'destination' => $this->shipment->destinationLabel(),
            'status' => $this->shipment->status->getLabel(),
        ];
    }
}
