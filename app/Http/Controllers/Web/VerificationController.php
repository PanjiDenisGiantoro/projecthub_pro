<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VerificationController extends Controller
{
    public function notice()
    {
        if (Auth::user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(Request $request, string $token)
    {
        try {
            $payload = json_decode(Crypt::decryptString($token), true);
        } catch (DecryptException $e) {
            throw new HttpException(403, 'Link verifikasi tidak valid.');
        }

        if (!is_array($payload) || !isset($payload['id'], $payload['hash'], $payload['expires'])) {
            throw new HttpException(403, 'Link verifikasi tidak valid.');
        }

        if (now()->timestamp > $payload['expires']) {
            throw new HttpException(403, 'Link verifikasi sudah kedaluwarsa. Silakan minta link baru.');
        }

        $user = $request->user();

        if (!hash_equals((string) $user->getKey(), (string) $payload['id'])
            || !hash_equals(sha1($user->getEmailForVerification()), (string) $payload['hash'])) {
            throw new HttpException(403, 'Link verifikasi tidak valid.');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->route('dashboard')->with('success', 'Email berhasil diverifikasi.');
    }

    public function resend(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('status', 'Link verifikasi baru telah dikirim ke email Anda.');
    }
}