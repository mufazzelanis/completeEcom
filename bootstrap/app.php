<?php

use App\Listeners\RecordLoginActivityListener;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ForceHttps;
use App\Http\Middleware\CustomRedirects;
use App\Http\Middleware\LegacyUrlRedirects;
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
        // Global, not web: most legacy WooCommerce paths (/product/x, /wp-json/…) match no route,
        // so route middleware would never see them.
        $middleware->prepend(LegacyUrlRedirects::class);
        // prepended after, so it runs first: the admin's own redirects (Settings → Redirects)
        // take precedence over the built-in WooCommerce/WordPress cleanup rules
        $middleware->prepend(CustomRedirects::class);
        $middleware->web(prepend: [ForceHttps::class]);
        $middleware->web(append: [SetLocale::class, MaintenanceMode::class, SecurityHeaders::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
        // Count storefront 404s (Settings → Redirects → 404 log) so broken links that still get
        // visits can be redirected; returning nothing keeps the normal 404 page.
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, Request $request) {
            \App\Models\NotFoundLog::record($request);

            return null;
        });
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

        // Keeps the homepage personalized section's source tables (product_views,
        // search_queries) from growing forever — see the command's own docblock.
        $schedule->command('personalization:prune')->dailyAt('02:00');

        // Post-delivery review requests (SMS + email) — daytime only, Bangladesh time, so
        // nobody gets a text at night; each order is asked once (see the command's docblock).
        // 404 log (Settings → Redirects): drop addresses nobody has hit for 90 days, so the
        // list stays about links that still get traffic
        $schedule->call(fn () => \App\Models\NotFoundLog::where('last_hit_at', '<', now()->subDays(90))->delete())
            ->name('not-found-log:prune')->dailyAt('03:00');

        $schedule->command('reviews:send-requests')->hourly()->between('10:00', '20:00')->timezone('Asia/Dhaka')->withoutOverlapping();
    })->create();
