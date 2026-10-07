<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class PushNotification extends Notification
{
    public function __construct(
        protected string $title,
        protected string $body,
        protected array $data = [],
    ) {}

    public function via($notifiable): array
    {
        if (!extension_loaded('curl')) {
            return [];
        }
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon('/flovig_logo.webp')
            ->body($this->body)
            ->data($this->data);
    }
}
