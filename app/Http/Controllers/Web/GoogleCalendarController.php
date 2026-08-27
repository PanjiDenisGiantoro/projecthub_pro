<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\GoogleToken;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleCalendarController extends Controller
{
    public function connect()
    {
        return Socialite::driver('google')
            ->scopes(['https://www.googleapis.com/auth/calendar.events'])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->redirectUrl(config('services.google.calendar_redirect'))
            ->redirect();
    }

    public function callback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(config('services.google.calendar_redirect'))
                ->user();
        } catch (\Throwable $e) {
            return redirect()->route('profile')->with('error', 'Menghubungkan Google Calendar gagal. Silakan coba lagi.');
        }

        $refreshToken = $googleUser->refreshToken;

        $attributes = [
            'access_token' => $googleUser->token,
            'expires_at'   => now()->addSeconds($googleUser->expiresIn ?? 3600),
            'scope'        => 'https://www.googleapis.com/auth/calendar.events',
        ];

        // refresh_token cuma dikirim Google saat consent pertama kali — jangan ditimpa null saat reconnect.
        if ($refreshToken) {
            $attributes['refresh_token'] = $refreshToken;
        }

        GoogleToken::updateOrCreate(['user_id' => auth()->id()], $attributes);

        return redirect()->route('profile')->with('success', 'Google Calendar berhasil terhubung.');
    }

    public function disconnect()
    {
        GoogleToken::where('user_id', auth()->id())->delete();

        return back()->with('success', 'Google Calendar berhasil diputus.');
    }
}
