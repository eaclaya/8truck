<?php

namespace App\Notifications;

use App\Models\Shipment;
use App\Notifications\Concerns\SendsFcmPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable, SendsFcmPush;

    public function __construct(public Shipment $shipment)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->channels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.completed.title'))
            ->line(__('notifications.completed.body', $this->bodyParams()))
            ->action(__('notifications.action'), route('jobs.index'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.completed.title'),
            'body' => __('notifications.completed.body', $this->bodyParams()),
            'url' => route('jobs.index', absolute: false),
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
        ];
    }
}
