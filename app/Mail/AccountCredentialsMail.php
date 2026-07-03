<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountCredentialsMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 5;
    public $backoff = 30;

    public function __construct(
        public User $user,
        public string $password,
    ) {}

    public function build()
    {
        return $this->subject('Akun Flovig Anda Sudah Siap')
            ->view('emails.account-credentials')
            ->with([
                'name'     => $this->user->name,
                'email'    => $this->user->email,
                'password' => $this->password,
                'loginUrl' => route('login'),
            ]);
    }
}