<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event', 'description', 'subject_type', 'subject_id',
        'url', 'ip_address', 'user_agent', 'device', 'browser', 'platform',
        'properties', 'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function subject()
    {
        return $this->morphTo();
    }

    public function getEventLabelAttribute(): string
    {
        return match (true) {
            $this->event === 'login'          => 'Login',
            $this->event === 'logout'         => 'Logout',
            str_starts_with($this->event, 'product.') => 'Product View',
            $this->event === 'order.place'    => 'Order Placed',
            $this->event === 'order.download' => 'Order Download',
            str_starts_with($this->event, 'order.')   => 'Order View',
            str_starts_with($this->event, 'cart.')    => 'Cart ' . ucfirst(substr($this->event, 5)),
            default => ucfirst(str_replace(['.', '_'], ' ', $this->event)),
        };
    }

    public function getEventColorAttribute(): string
    {
        return match (true) {
            $this->event === 'login'                  => 'bg-green-100 text-green-700',
            $this->event === 'logout'                 => 'bg-gray-100 text-gray-600',
            str_starts_with($this->event, 'product.') => 'bg-blue-100 text-blue-700',
            $this->event === 'order.place'             => 'bg-emerald-100 text-emerald-700',
            str_starts_with($this->event, 'order.')   => 'bg-purple-100 text-purple-700',
            $this->event === 'cart.add'                => 'bg-orange-100 text-orange-700',
            $this->event === 'cart.remove'             => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    /**
     * UA parse — factored out here so every logging call site (login, product view,
     * cart, etc.) gets consistent device/browser/platform values without duplicating it.
     *
     * The old version matched browsers/platforms in whatever order preg_match's
     * alternation happened to try them, which is wrong for UA strings specifically —
     * every Chromium browser's UA also contains "Safari" and "Chrome" tokens (Edge,
     * Opera, Samsung Internet are all Chrome-based), so an unordered check mislabeled
     * most of them, and a background/non-request call site with no real UA string
     * silently fell through to "Desktop" + "Unknown" instead of reporting that there
     * was no UA at all — reported as the Device column showing "Desktop · Unknown" far
     * more often than real Chrome/Firefox/Safari traffic would ever actually be unknown.
     */
    public static function parseUserAgent(string $userAgent): array
    {
        if (trim($userAgent) === '') {
            return ['Unknown', 'Unknown', 'Unknown'];
        }

        // Most-specific-first: every Chromium-based browser's UA also contains a
        // bare "Chrome/" token (and most contain "Safari/" too), so the specific
        // browser name has to be checked before the generic engine tokens it rides on.
        $browser = match (true) {
            (bool) preg_match('/EdgiOS|EdgA|Edg\//i', $userAgent) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $userAgent) => 'Opera',
            (bool) preg_match('/SamsungBrowser/i', $userAgent) => 'Samsung Internet',
            (bool) preg_match('/UCBrowser/i', $userAgent) => 'UC Browser',
            (bool) preg_match('/FxiOS/i', $userAgent) => 'Firefox',
            (bool) preg_match('/CriOS|Chrome\//i', $userAgent) => 'Chrome',
            (bool) preg_match('/Firefox\//i', $userAgent) => 'Firefox',
            (bool) preg_match('/Version\/[\d.]+.*Safari/i', $userAgent) => 'Safari', // bare Safari UA names the engine twice; Chrome et al. are already caught above
            (bool) preg_match('/MSIE|Trident/i', $userAgent) => 'Internet Explorer',
            default => 'Unknown',
        };

        $platform = match (true) {
            (bool) preg_match('/Windows NT/i', $userAgent) => 'Windows',
            (bool) preg_match('/Mac OS X/i', $userAgent) && !preg_match('/iPhone|iPad/i', $userAgent) => 'macOS',
            (bool) preg_match('/CrOS/i', $userAgent) => 'ChromeOS',
            (bool) preg_match('/Android/i', $userAgent) => 'Android',
            (bool) preg_match('/iPad/i', $userAgent) => 'iPadOS',
            (bool) preg_match('/iPhone|iPod/i', $userAgent) => 'iOS',
            (bool) preg_match('/Linux/i', $userAgent) => 'Linux',
            default => 'Unknown',
        };

        // Android device model, when present, rides along as "Android <ver>; <model>)"
        // in the UA string — the closest thing to a real "which phone" signal that's
        // actually available (never from the IP, which carries no device info at all).
        if ($platform === 'Android' && preg_match('/Android[^;]*;\s*([^;)]+)\)/i', $userAgent, $m)) {
            $model = trim($m[1]);
            if ($model !== '' && !str_contains(strtolower($model), 'wv')) {
                $platform .= " ({$model})";
            }
        }

        $device = match (true) {
            (bool) preg_match('/iPad/i', $userAgent) => 'Tablet',
            (bool) preg_match('/Android/i', $userAgent) && !preg_match('/Mobile/i', $userAgent) => 'Tablet',
            (bool) preg_match('/iPhone|iPod|Android|Mobile/i', $userAgent) => 'Mobile',
            default => 'Desktop',
        };

        return [$device, $browser, $platform];
    }
}
