<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class MeetingCreatedNotification extends Notification
{
    public function __construct(
        protected string $title,
        protected Carbon $startsAt,
        protected string $meetLink,
    ) {}

    public function via($notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush($notifiable, $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Meeting dibuat: '.$this->title)
            ->icon('/flovig_logo.webp')
            ->body('Dijadwalkan '.$this->startsAt->translatedFormat('d M Y, H:i').' — klik untuk join.')
            ->data(['meet_link' => $this->meetLink]);
    }
}
