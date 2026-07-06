<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class AuthWebController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->afterAuthenticate($request, Auth::user());
        }

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => 'Login dengan Google gagal. Silakan coba lagi.']);
        }

        $user = User::where('google_id', $googleUser->getId())->first();

        if (!$user) {
            // Hubungkan ke akun email/password yang sudah ada, hanya jika email
            // tsb sudah diverifikasi kepemilikannya oleh Google.
            $emailVerified = (bool) ($googleUser->user['email_verified'] ?? $googleUser->user['verified_email'] ?? false);

            if ($emailVerified) {
                $user = User::where('email', $googleUser->getEmail())->first();
            }

            if (!$user) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Email Google ini belum terdaftar. Silakan daftar akun baru terlebih dahulu.',
                ]);
            }

            $user->forceFill(['google_id' => $googleUser->getId()])->save();
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        return $this->afterAuthenticate($request, $user);
    }

    protected function afterAuthenticate(Request $request, User $user)
    {
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi administrator.']);
        }

        if (!$user->is_super_admin && $user->isCompanyExpired()) {
            $email       = $user->email;
            $activeUntil = $user->companyRegistrant()?->active_until;
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('account.expired', [
                'email'        => $email,
                'active_until' => $activeUntil?->format('Y-m-d'),
            ]);
        }

        if (!$request->session()->has('active_package')) {
            $pkgs       = $user->is_super_admin ? ['task_management'] : $user->activePackages();
            $defaultPkg = $pkgs[0] ?? null;
            if ($defaultPkg) {
                $request->session()->put('active_package', $defaultPkg);
            }
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function expired(Request $request)
    {
        return view('auth.expired', [
            'email'       => $request->query('email'),
            'activeUntil' => $request->query('active_until'),
        ]);
    }
}
