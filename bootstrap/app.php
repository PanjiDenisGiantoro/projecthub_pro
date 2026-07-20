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
        // Deploy webhook dipanggil dari CI/CD — tidak butuh CSRF token
        $middleware->validateCsrfTokens(except: [
            'deploy/webhook',
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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
