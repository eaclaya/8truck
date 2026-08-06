<?php

namespace App\Notifications\Concerns;

use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

trait SendsFcmPush
{
    /**
     * Reuse the database payload (title, body, url) as the push content.
     */
    public function toFcm(object $notifiable): FcmMessage
    {
        $data = $this->toArray($notifiable);

        return new FcmMessage(
            notification: new FcmNotification(
                title: $data['title'] ?? config('app.name'),
                body: $data['body'] ?? '',
            ),
            data: ['url' => (string) ($data['url'] ?? '')],
        );
    }

    /**
     * @return array<int, string>
     */
    protected function channels(): array
    {
        $channels = ['database', 'broadcast', 'mail'];

        if (config('services.fcm.enabled')) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }
}
