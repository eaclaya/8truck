<?php

namespace App\Notifications;

use App\Models\Shipment;
use App\Notifications\Concerns\SendsFcmPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewLoadAvailable extends Notification implements ShouldQueue
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
            ->subject(__('notifications.new_load.title'))
            ->line(__('notifications.new_load.body', $this->bodyParams()))
            ->action(__('notifications.action'), route('loads.show', $this->shipment));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.new_load.title'),
            'body' => __('notifications.new_load.body', $this->bodyParams()),
            'url' => route('loads.show', $this->shipment, absolute: false),
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
            'date' => $this->shipment->pickup_date->toDateString(),
        ];
    }
}
