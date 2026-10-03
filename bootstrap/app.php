<?php

use App\Listeners\RecordLoginActivityListener;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\MaintenanceMode;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RecordLoginActivity;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VendorMiddleware;
use Illuminate\Auth\Events\Login;
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
            'record-login' => RecordLoginActivity::class,
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
        // Drains the default queue (bulk product imports, Facebook Conversions API
        // events, etc.) without needing a long-running `queue:work` process, which
        // most shared hosts don't allow — this piggybacks on the same one-cron-entry
        // `schedule:run` setup as everything else above. --stop-when-empty means it
        // exits almost instantly when there's nothing queued, but when there IS a
        // backlog it was keeping a PHP process alive for up to 50 of every 60 seconds
        // (83% of the time) — on shared hosting, where the account is capped at a
        // small number of concurrent processes (CloudLinux/LVE), that was enough to
        // starve an incoming visitor's request of a process slot entirely, producing
        // exactly the "site times out, then loads fine 5-10s later" symptom. 15s
        // caps the contention window at 25% of each minute instead; a big backlog
        // just drains over a few more ticks rather than one long one. withoutOverlapping
        // still skips a run if the previous minute's worker hasn't finished.
        $schedule->command('queue:work --stop-when-empty --max-time=15')
            ->everyMinute()
            ->withoutOverlapping();
        // OrderObserver keeps sales_reports in sync in real time; this nightly run
        // just self-heals the last couple of days in case of bulk edits or direct
        // DB writes that bypass Eloquent events.
        $schedule->command('sales-report:rebuild', ['--from' => now()->subDays(2)->toDateString()])->dailyAt('00:15');

        // CRM: nightly full recompute (RFM cut-offs shift as the customer base changes) + win-back tasks;
        // the hourly pass just fires reminders for tasks that have come due.
        $schedule->command('crm:refresh')->dailyAt('01:00');
        $schedule->command('crm:refresh', ['--reminders' => true])->hourly();
    })->create();
