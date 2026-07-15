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

        if ($user && $package === 'hris' && $user->hasRole('customer')) {
            abort(403, "Paket '{$package}' tidak tersedia untuk akun Anda.");
        }

        if ($user && ($user->is_super_admin || in_array($package, $user->activePackages()))) {
            return $next($request);
        }

        abort(403, "Paket '{$package}' tidak aktif untuk akun Anda.");
    }
}
