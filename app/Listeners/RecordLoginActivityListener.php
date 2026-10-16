<?php

namespace App\Listeners;

use App\Models\LoginActivity;
use App\Services\ActivityLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class RecordLoginActivityListener
{
    public function handle(Login $event): void
    {
        try {
            $user = $event->user;
            if ($user) {
                LoginActivity::record(
                    $user->id,
                    Request::userAgent() ?? '',
                    Request::ip()
                );

                // Set by SocialAuthController right before Auth::login() when this was a
                // "Continue with Google/Facebook/LinkedIn" sign-in rather than the password
                // form — pull (get + forget in one step) so it never leaks into a later,
                // unrelated login in the same session.
                $via = Request::session()->pull('_login_via');

                if ($via) {
                    // ucfirst() alone would render "linkedin" as "Linkedin", not the brand's
                    // actual capitalization — the other two providers' names already ucfirst()
                    // correctly, so only LinkedIn needs a named exception.
                    $providerLabel = $via['provider'] === 'linkedin' ? 'LinkedIn' : ucfirst($via['provider']);
                    $description = "{$user->name} logged in with {$providerLabel}"
                        . ($via['email'] ? " ({$via['email']})" : '');

                    ActivityLogger::log('login', $description, $user, [
                        'login_provider' => $via['provider'],
                        'login_provider_email' => $via['email'],
                        'login_provider_name' => $via['name'],
                    ]);
                } else {
                    ActivityLogger::log('login', "{$user->name} logged in with email & password", $user);
                }
            }
        } catch (\Throwable $e) {
            Log::error('LoginActivity listener failed: ' . $e->getMessage());
        }
    }
}
