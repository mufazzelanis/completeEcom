<?php

namespace App\Services;

use Illuminate\Http\Request;

/**
 * Two zero-friction bot checks, meant to be used together via the honeypot
 * component: a hidden field real visitors never see or fill (scripted bots
 * that blindly populate every input trip it), and a render timestamp (bots
 * that POST straight to the endpoint without ever rendering/waiting on the
 * page submit implausibly fast).
 */
class SpamGuard
{
    private const HONEYPOT_FIELD = 'company_website';
    private const TIMESTAMP_FIELD = 'form_rendered_at';
    private const MIN_SECONDS = 3;

    public static function honeypotField(): string
    {
        return self::HONEYPOT_FIELD;
    }

    public static function timestampField(): string
    {
        return self::TIMESTAMP_FIELD;
    }

    /**
     * Returns a short machine-readable reason when the submission looks like
     * a bot, or null when it looks clean.
     */
    public static function check(Request $request): ?string
    {
        if (filled($request->input(self::HONEYPOT_FIELD))) {
            return 'honeypot';
        }

        $renderedAt = (int) $request->input(self::TIMESTAMP_FIELD);
        if ($renderedAt > 0 && (time() - $renderedAt) < self::MIN_SECONDS) {
            return 'too_fast';
        }

        return null;
    }
}
