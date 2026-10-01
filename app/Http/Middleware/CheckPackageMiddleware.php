<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPackageMiddleware
{
    public function handle(Request $request, Closure $next, string $package): Response
    {
        $user = $request->user();

        if ($user && $package === 'hris' && $user->hasRole('client')) {
            abort(403, "Paket '{$package}' tidak tersedia untuk akun Anda.");
        }

        if ($user && in_array($package, $user->accessiblePackages())) {
            return $next($request);
        }

        abort(403, "Paket '{$package}' tidak aktif untuk akun Anda.");
    }
}
