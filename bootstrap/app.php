<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckAccountAccess;
use App\Http\Middleware\CheckStaffPageAccess;
use App\Http\Middleware\ClientMiddleware;
use App\Http\Middleware\RateLimitPerDevice;
use App\Http\Middleware\RejectOversizedProofUpload;
use App\Http\Middleware\StaffMiddleware;
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
        $middleware->prepend(RejectOversizedProofUpload::class);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'account.active' => CheckAccountAccess::class,
            'staff' => StaffMiddleware::class,
            'staff.page.access' => CheckStaffPageAccess::class,
            'client' => ClientMiddleware::class,
            'rate_limit_per_device' => RateLimitPerDevice::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
