<?php

namespace App\Providers;

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
    }
}
