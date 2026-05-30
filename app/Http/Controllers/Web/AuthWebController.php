<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Akun Anda tidak aktif. Hubungi administrator.'])->onlyInput('email');
            }

            if (!$user->is_super_admin && $user->isExpired()) {
                Auth::logout();
                return back()->withErrors(['email' => 'Masa aktif akun Anda telah habis. Silakan hubungi administrator untuk memperpanjang akses.'])->onlyInput('email');
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

        return back()->withErrors(['email' => 'Email atau password salah.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
