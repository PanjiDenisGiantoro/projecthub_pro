<?php

namespace App\Services;

use App\Models\PhNotification;
use App\Models\User;
use App\Notifications\PushNotification;

class NotificationService
{
    public function send(int $userId, string $type, string $title, string $message, array $data = [], bool $push = true): PhNotification
    {
        $notification = PhNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);

        if ($push && ($user = User::find($userId))) {
            $user->notify(new PushNotification($title, $message, $data));
        }

        return $notification;
    }

    public function notifyManagers(string $type, string $title, string $message, array $data = [], bool $push = true): void
    {
        $this->notifyByRole('manager', $type, $title, $message, $data, $push);
        $this->notifyByRole('admin', $type, $title, $message, $data, $push);
    }

    public function notifyByRole(string $role, string $type, string $title, string $message, array $data = [], bool $push = true): void
    {
        User::role($role)->where('is_active', true)->each(
            fn($user) => $this->send($user->id, $type, $title, $message, $data, $push)
        );
    }
}
