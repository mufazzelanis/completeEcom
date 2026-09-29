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
use Illuminate\Support\Facades\Schema;
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

        $this->applyMailSettings();
    }

    /**
     * Settings → Email & SMTP saves into the settings table, but nothing previously
     * read those columns back — every mail always used whatever was hardcoded in
     * .env, silently ignoring the admin panel. Wired here instead of at the .env
     * level so the panel (and its "Send Test" button) actually does something.
     */
    protected function applyMailSettings(): void
    {
        try {
            if (!Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        $mailer = setting('mail_mailer');
        if ($mailer) {
            config(['mail.default' => $mailer]);
        }

        if ($mailer === 'smtp' && setting('mail_host')) {
            config([
                'mail.mailers.smtp.host' => setting('mail_host'),
                'mail.mailers.smtp.port' => (int) setting('mail_port', 587),
                'mail.mailers.smtp.username' => setting('mail_username') ?: null,
                'mail.mailers.smtp.password' => setting('mail_password') ?: null,
                // Laravel infers STARTTLS ("smtp") vs implicit TLS ("smtps") from the
                // scheme; port 465 already implies smtps on its own, so this only needs
                // to force it for the "SSL" choice on a non-465 port.
                'mail.mailers.smtp.scheme' => setting('mail_encryption', 'tls') === 'ssl' ? 'smtps' : null,
            ]);
        }

        if ($from = setting('mail_from_address')) {
            config(['mail.from.address' => $from]);
        }
        if ($fromName = setting('mail_from_name')) {
            config(['mail.from.name' => $fromName]);
        }
    }
}
