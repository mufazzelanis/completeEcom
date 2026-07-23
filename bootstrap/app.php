<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VendorMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'vendor' => VendorMiddleware::class,
        ]);
        $middleware->web(prepend: [ForceHttps::class]);
        $middleware->web(append: [SetLocale::class, MaintenanceMode::class, SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('blog:publish-scheduled')->everyMinute();
        $schedule->command('email-campaigns:process')->everyMinute();
    })->create();
