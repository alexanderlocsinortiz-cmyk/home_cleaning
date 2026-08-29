<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AuthenticateMobileApiToken;
use App\Http\Middleware\CheckAccountAccess;
use App\Http\Middleware\CheckStaffPageAccess;
use App\Http\Middleware\ClientMiddleware;
use App\Http\Middleware\ProviderMiddleware;
use App\Http\Middleware\RateLimitPerDevice;
use App\Http\Middleware\RejectOversizedProofUpload;
use App\Http\Middleware\StaffMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('database:backup-cloud')
            ->dailyAt('02:00')
            ->withoutOverlapping(30);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(RejectOversizedProofUpload::class);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'account.active' => CheckAccountAccess::class,
            'auth.mobile' => AuthenticateMobileApiToken::class,
            'staff' => StaffMiddleware::class,
            'staff.page.access' => CheckStaffPageAccess::class,
            'client' => ClientMiddleware::class,
            'provider' => ProviderMiddleware::class,
            'rate_limit_per_device' => RateLimitPerDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
