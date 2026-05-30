<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckActiveAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->is_super_admin && Auth::user()->isExpired()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Masa aktif akun Anda telah habis. Silakan hubungi administrator untuk memperpanjang akses.']);
        }

        return $next($request);
    }
}
