<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Deploy webhook dipanggil dari CI/CD, billing/notification dipanggil server-to-server oleh Midtrans — keduanya tidak butuh CSRF token
        $middleware->validateCsrfTokens(except: [
            'deploy/webhook',
            'billing/notification',
        ]);

        $middleware->alias([
            'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'superadmin'         => \App\Http\Middleware\SuperAdminMiddleware::class,
            'check.active'       => \App\Http\Middleware\CheckActiveAccess::class,
            'package'            => \App\Http\Middleware\CheckPackageMiddleware::class,
        ]);
    })
    ->withSchedule(function (\Illuminate\Console\Scheduling\Schedule $schedule): void {
        $schedule->command('sla:check')->everyFiveMinutes();
        $schedule->command('invoices:check-overdue')->daily();
        $schedule->command('tasks:generate-recurring')->dailyAt('00:05');
        $schedule->command('notifications:deadline-reminders')->dailyAt('08:00');
        $schedule->command('approvals:expire')->everyFifteenMinutes();
        $schedule->command('companies:check-expiring')->dailyAt('08:00');
        $schedule->command('meetings:send-reminders')->everyFifteenMinutes();

        // Jaga model AI Assistant (Ollama, CPU-only) tetap di RAM. keep_alive di
        // AiAssistantWebController::KEEP_ALIVE diset 60m, tapi kalau tidak ada
        // yang chat sama sekali dalam rentang itu modelnya ke-unload dan chat
        // berikutnya kena cold-start (lihat komentar di
        // AiAssistantWebController::MODEL). Ping tiap 15 menit — jauh di bawah
        // jendela 60m — supaya nyaris selalu sudah warm saat user chat.
        // /api/generate tanpa "prompt" cuma memuat model, tidak generate token.
        $schedule->call(function () {
            try {
                \Illuminate\Support\Facades\Http::timeout(60)->post('http://127.0.0.1:11434/api/generate', [
                    'model'      => \App\Http\Controllers\Web\AiAssistantWebController::MODEL,
                    'keep_alive' => \App\Http\Controllers\Web\AiAssistantWebController::KEEP_ALIVE,
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        })->everyFifteenMinutes()->name('ai-assistant-warmup')->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
