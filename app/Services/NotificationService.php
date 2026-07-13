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

    public function notifyManagers(string $type, string $title, string $message, array $data = [], bool $push = true, ?int $companyId = null): void
    {
        $this->notifyByRole('manager', $type, $title, $message, $data, $push, $companyId);
        $this->notifyByRole('admin', $type, $title, $message, $data, $push, $companyId);
    }

    public function notifyByRole(string $role, string $type, string $title, string $message, array $data = [], bool $push = true, ?int $companyId = null): void
    {
        User::role($role)->where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->each(fn($user) => $this->send($user->id, $type, $title, $message, $data, $push));
    }

    /**
     * Notify users who hold a given permission, respecting per-company permission
     * customization (User::hasPermissionTo override), unlike Spatie's ->permission() query scope.
     */
    public function notifyByPermission(string $permission, string $type, string $title, string $message, array $data = [], bool $push = true, ?int $companyId = null, ?int $excludeUserId = null): void
    {
        User::where('is_active', true)
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($excludeUserId, fn($q) => $q->where('id', '!=', $excludeUserId))
            ->get()
            ->filter(fn($user) => $user->can($permission))
            ->each(fn($user) => $this->send($user->id, $type, $title, $message, $data, $push));
    }
}
