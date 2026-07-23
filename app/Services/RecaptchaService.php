<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RecaptchaService
{
    public function enabled(): bool
    {
        return setting('recaptcha_enabled', '0') === '1'
            && setting('recaptcha_site_key', '') !== ''
            && setting('recaptcha_secret_key', '') !== '';
    }

    public function siteKey(): string
    {
        return setting('recaptcha_site_key', '');
    }

    public function version(): string
    {
        return setting('recaptcha_version', 'v2');
    }

    /**
     * Verifies a submitted token against Google's siteverify API. Returns true when
     * reCAPTCHA isn't configured at all — the toggle only starts enforcing once the
     * admin has actually supplied real site/secret keys from the Google console.
     */
    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if (! $token) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => setting('recaptcha_secret_key', ''),
                'response' => $token,
                'remoteip' => $remoteIp,
            ]);

            $data = $response->json() ?? [];

            if ($this->version() === 'v3') {
                return ($data['success'] ?? false) && ($data['score'] ?? 0) >= 0.5;
            }

            return (bool) ($data['success'] ?? false);
        } catch (\Throwable $e) {
            // Google's endpoint being unreachable shouldn't take the whole form down —
            // log it and fail open, same posture as a form with reCAPTCHA disabled.
            Log::warning('reCAPTCHA verification request failed: ' . $e->getMessage());
            return true;
        }
    }
}
