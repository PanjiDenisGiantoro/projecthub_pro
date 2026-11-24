<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Email reset password untuk aplikasi mobile: berisi token yang ditempel
 * user di form "Reset Password dengan Token" (POST /api/auth/reset-password).
 * Belum ada halaman reset di web, jadi tidak memakai route('password.reset').
 */
class ResetPasswordToken extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 5;
    public $backoff = 30;

    public function __construct(public string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $expire = config('auth.passwords.users.expire', 60);

        return (new MailMessage)
            ->subject('Reset Password Akun Flovig')
            ->greeting('Halo ' . $notifiable->name . ',')
            ->line('Kami menerima permintaan reset password untuk akun ' . $notifiable->email . '.')
            ->line('Salin token di bawah ini, lalu tempel di halaman Reset Password pada aplikasi Flovig:')
            ->line('**' . $this->token . '**')
            ->line("Token berlaku {$expire} menit dan hanya bisa dipakai sekali.")
            ->line('Jika Anda tidak meminta reset password, abaikan email ini. Password Anda tidak berubah.');
    }
}
