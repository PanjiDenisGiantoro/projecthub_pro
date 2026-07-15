<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckActiveAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && !Auth::user()->is_super_admin && Auth::user()->isCompanyExpired()) {
            $user        = Auth::user();
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

        return $next($request);
    }
}
