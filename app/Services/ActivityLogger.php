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

            ActivityLog::create([
                'user_id'      => Auth::id(),
                'event'        => $event,
                'description'  => $description,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id'   => $subject?->getKey(),
                'url'          => Request::fullUrl(),
                'ip_address'   => Request::ip(),
                'user_agent'   => $userAgent,
                'device'       => $device,
                'browser'      => $browser,
                'platform'     => $platform,
                'properties'   => $properties ?: null,
                'created_at'   => now(),
            ]);
        } catch (\Throwable $e) {
            Log::error('ActivityLogger failed: ' . $e->getMessage());
        }
    }
}
