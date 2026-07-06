<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;

class QueuedVerifyEmail extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public $tries = 5;
    public $backoff = 30;

    /**
     * Bungkus id, hash, dan waktu kedaluwarsa dalam satu token terenkripsi
     * (bukan query string id/hash/expires/signature yang plain) supaya
     * detail user tidak terekspos di URL verifikasi.
     */
    protected function verificationUrl($notifiable)
    {
        $token = Crypt::encryptString(json_encode([
            'id'      => $notifiable->getKey(),
            'hash'    => sha1($notifiable->getEmailForVerification()),
            'expires' => now()->addMinutes(Config::get('auth.verification.expire', 60))->timestamp,
        ]));

        return URL::route('verification.verify', ['token' => $token]);
    }
}
