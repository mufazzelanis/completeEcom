<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Records behavioral/security activity (logins, views, cart actions) for the
     * admin activity dashboard. Wrapped in try/catch because these calls sit in
     * hot request paths (product page, cart add) — a logging failure must never
     * break the actual page/action.
     */
    public static function log(string $event, string $description, ?Model $subject = null, array $properties = []): void
    {
        try {
            $userAgent = Request::userAgent() ?? '';
            [$device, $browser, $platform] = ActivityLog::parseUserAgent($userAgent);

            // A logged-in admin/staff member/customer is never a bot. For guests: a bot-like
            // User-Agent, missing browser headers (ActivityLog::automationSignal), or reading
            // product pages faster than a person can — 30+ in 10 minutes from one IP.
            $botReason = null;
            if (Auth::guest()) {
                $botReason = ActivityLog::automationSignal(request());
                if (!$botReason && $event === 'product.view' && static::tooFast((string) Request::ip())) {
                    $botReason = 'too-fast';
                }
                if ($botReason) {
                    $properties['bot'] = $botReason;
                }
            }

            ActivityLog::create([
                'user_id'      => Auth::id(),
                'event'        => $event,
                'description'  => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'url'          => Request::fullUrl(),
                'referrer'     => Request::header('referer'),
                'ip_address'   => Request::ip(),
                'user_agent'   => $userAgent,
                'device'       => $device,
                'browser'      => $browser,
                'platform'     => $platform,
                'is_bot'       => $botReason !== null,
                'properties'   => $properties ?: null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ActivityLogger failed: ' . $e->getMessage());
        }
    }

    /** More than 30 product views from this IP in the current 10-minute window. */
    private static function tooFast(string $ip): bool
    {
        if ($ip === '') {
            return false;
        }
        $key = 'pv-rate:' . $ip . ':' . intdiv(time(), 600);
        \Illuminate\Support\Facades\Cache::add($key, 0, 660);

        return \Illuminate\Support\Facades\Cache::increment($key) > 30;
    }
}
