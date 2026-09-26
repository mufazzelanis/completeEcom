<?php

namespace App\Providers;

use App\Listeners\RecordLoginActivityListener;
use App\Listeners\RecordLogoutActivityListener;
use App\Models\Order;
use App\Models\User;
use App\Observers\CrmOrderObserver;
use App\Observers\CrmUserObserver;
use App\Observers\OrderAlertObserver;
use App\Observers\OrderObserver;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            $rule = Password::min(10)->mixedCase()->numbers()->symbols();

            // The breach-corpus check calls an external API (api.pwnedpasswords.com);
            // skip it outside production so registration/reset never breaks in an
            // offline dev/test environment, but keep it where it matters most.
            return $this->app->environment('production') ? $rule->uncompromised() : $rule;
        });

        Order::observe(OrderObserver::class);
        Order::observe(OrderAlertObserver::class);
        Order::observe(CrmOrderObserver::class);
        User::observe(CrmUserObserver::class);

        // Named limiters, one per purpose. The unnamed `throttle:N,M` form keys every route
        // by the signed-in user alone, so ALL of them share ONE counter — the bell polls the
        // feed every few seconds, and that traffic silently used up the tiny quota of the
        // "turn phone alerts on/off" buttons, which then failed with 429 for no visible reason.
        RateLimiter::for('admin-alerts-feed', fn (Request $request) => Limit::perMinute(120)->by('aa-feed:' . ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('admin-alerts-action', fn (Request $request) => Limit::perMinute(30)->by('aa-action:' . ($request->user()?->id ?? $request->ip())));
        RateLimiter::for('admin-alerts-test', fn (Request $request) => Limit::perMinute(10)->by('aa-test:' . ($request->user()?->id ?? $request->ip())));

        Event::listen(Login::class, RecordLoginActivityListener::class);
        Event::listen(Logout::class, RecordLogoutActivityListener::class);
    }
}
