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
            // Semua user perusahaan yang masa aktifnya habis tetap login supaya
            // bisa memperpanjang mandiri via Midtrans di halaman billing.renew.
            if (! $request->routeIs('billing.*', 'logout')) {
                return redirect()->route('billing.renew');
            }

            return $next($request);
        }

        return $next($request);
    }
}
