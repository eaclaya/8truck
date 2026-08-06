<?php

namespace App\Notifications;

use App\Models\Rating;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RatingReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Rating $rating)
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
            ->subject(__('notifications.rating_received.title'))
            ->line(__('notifications.rating_received.body', $this->bodyParams()))
            ->action(__('notifications.action'), $this->url(absolute: true));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => __('notifications.rating_received.title'),
            'body' => __('notifications.rating_received.body', $this->bodyParams()),
            'url' => $this->url(absolute: false),
        ];
    }

    private function url(bool $absolute): string
    {
        $shipment = $this->rating->shipment;

        return $this->rating->ratee_id === $shipment->customer_id
            ? route('shipments.show', $shipment, absolute: $absolute)
            : route('jobs.index', absolute: $absolute);
    }

    /**
     * @return array<string, string|int>
     */
    private function bodyParams(): array
    {
        return [
            'rater' => $this->rating->rater->name,
            'score' => $this->rating->score,
        ];
    }
}
