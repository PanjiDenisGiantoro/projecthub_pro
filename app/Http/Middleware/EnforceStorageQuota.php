<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Web\CompanyWebController;
use App\Http\Controllers\Web\Hris\AbsensiController;
use App\Http\Controllers\Web\ProfileWebController;
use App\Http\Controllers\Web\UserWebController;
use App\Support\PlanLimits;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tolak request ber-file kalau total upload melebihi sisa storage paket perusahaan.
 * Berlaku untuk semua upload (web & API), kecuali avatar, logo perusahaan & import Excel
 * yang tidak masuk kuota.
 */
class EnforceStorageQuota
{
    private const EXCLUDED_CONTROLLERS = [
        ProfileWebController::class,  // avatar
        UserWebController::class,     // avatar & import user
        CompanyWebController::class,  // logo perusahaan
        AbsensiController::class,     // import absensi & foto wajah
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $files = array_filter(Arr::flatten($request->allFiles()), fn ($f) => $f instanceof UploadedFile && $f->isValid());

        if (! $files || in_array($request->route()?->getControllerClass(), self::EXCLUDED_CONTROLLERS, true)) {
            return $next($request);
        }

        $limits = PlanLimits::forUser($request->user() ?? $request->user('sanctum'));
        $bytes  = array_sum(array_map(fn (UploadedFile $f) => $f->getSize(), $files));

        if ($limits->canStore($bytes)) {
            return $next($request);
        }

        $notice = $limits->limitNotice('storage');

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'ok'          => false,
                'message'     => $notice['message'],
                'error'       => $notice['message'],
                'upgrade_url' => $notice['url'],
                'plan_limit'  => $notice,
            ], 403);
        }

        return back()->withInput()->with('plan_limit', $notice);
    }
}
