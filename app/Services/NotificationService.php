<?php

namespace App\Services;

use App\Models\PhNotification;
use App\Models\User;

class NotificationService
{
    public function send(int $userId, string $type, string $title, string $message, array $data = []): PhNotification
    {
        return PhNotification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public function notifyManagers(string $type, string $title, string $message, array $data = []): void
    {
        $this->notifyByRole('manager', $type, $title, $message, $data);
        $this->notifyByRole('admin', $type, $title, $message, $data);
    }

    public function notifyByRole(string $role, string $type, string $title, string $message, array $data = []): void
    {
        User::role($role)->where('is_active', true)->each(
            fn($user) => $this->send($user->id, $type, $title, $message, $data)
        );
    }
}
