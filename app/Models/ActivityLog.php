<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event', 'description', 'subject_type', 'subject_id',
        'url', 'referrer', 'ip_address', 'user_agent', 'device', 'browser', 'platform',
        'is_bot', 'properties', 'created_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
        'is_bot' => 'boolean',
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

    /**
     * Known crawler/bot/script signatures — computed once at write time (see
     * ActivityLogger::log()) into the is_bot column, so "real users only" on the
     * activity dashboard is a plain indexed WHERE instead of re-parsing every row's
     * user_agent on every single page load.
     *
     * Legitimate search/social crawlers (Googlebot, facebookexternalhit, ...) are
     * included deliberately, not just malicious scrapers — a site owner asking "which
     * of this is a real person" wants Googlebot excluded from that count exactly as
     * much as a Python scraper, even though the former is welcome traffic for a
     * different reason (SEO, not a customer). A handful of generic HTTP client
     * signatures (curl, python-requests, Go-http-client, PostmanRuntime, ...) are
     * included too — no real browser ever sends these as its own User-Agent.
     */
    private const BOT_SIGNATURES = '/bot|crawl|spider|slurp|archiver|facebookexternalhit|'
        . 'whatsapp|telegrambot|discordbot|skypeuripreview|redditbot|pinterest|'
        . 'python-requests|python-urllib|scrapy|curl\/|wget\/|libwww-perl|go-http-client|'
        . 'java\/|okhttp|postmanruntime|apache-httpclient|axios\/|node-fetch|httpie|'
        . 'headlesschrome|phantomjs|puppeteer|playwright|selenium/i';

    /**
     * The useful part of a log description — what an admin actually wants to copy or search
     * by: the product name from "Viewed product: X" / 'Added "X" x1 to cart', the order number
     * from "Viewed order #ORD-…", the person from "X logged in…". Falls back to the whole text.
     */
    public static function keyText(?string $description): string
    {
        $d = trim((string) $description);
        $patterns = [
            '/^Viewed product:\s*(.+)$/u',       // product.view
            '/^[A-Za-z]+ "(.+?)"/u',             // cart.add / cart.remove / order.download
            '/#\s*([A-Z]{2,}-[A-Z0-9-]+)/u',     // order.view / order.place (order number)
            '/^(.+?) logged (?:in|out)\b/u',     // login / logout
        ];
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $d, $m)) {
                return trim($m[1]);
            }
        }

        return $d;
    }

    public static function isBotUserAgent(string $userAgent): bool
    {
        return trim($userAgent) === '' || (bool) preg_match(self::BOT_SIGNATURES, $userAgent);
    }

    /**
     * Why this request looks automated, or null for a real browser. The User-Agent alone is
     * easy to fake — scrapers announce themselves as "Chrome on Windows" and passed as real
     * visitors (product views from Brazil, Colombia, Singapore… with no carts or orders). Two
     * things a real browser always sends and plain HTTP scripts don't:
     *   - Accept-Language — every browser sends the user's languages on every request;
     *   - Sec-Fetch-Mode — Chromium (Chrome, Edge, Opera, Samsung, Android in-app browsers)
     *     since v76 and Firefox since v90 send it on every request ("navigate" for a page,
     *     "cors" for Turbo/fetch). Only checked for those engines; iPhone browsers (WebKit)
     *     added it later, so they're never flagged for missing it.
     */
    /**
     * How an event is shown in Admin → Activity Log: [plain label, icon, hex colour]. Hex rather
     * than Tailwind classes so every event keeps its own colour in dark mode too (the dark theme
     * flattened the old bg-*-100 badges into one colour). Shopping-funnel steps go cool → warm →
     * green (view → cart → order); admin actions are coloured by what they did.
     */
    public static function eventMeta(string $event): array
    {
        return match (true) {
            $event === 'product.view'          => ['Product view', '👁', '#3b82f6'],
            $event === 'cart.add'              => ['Added to cart', '🛒', '#f59e0b'],
            $event === 'cart.remove'           => ['Removed from cart', '➖', '#ef4444'],
            $event === 'order.place'           => ['Order placed', '✅', '#10b981'],
            $event === 'order.view'            => ['Order viewed', '📄', '#8b5cf6'],
            $event === 'order.status_updated'  => ['Order status', '🔄', '#6366f1'],
            $event === 'order.payment_updated' => ['Payment update', '💳', '#14b8a6'],
            $event === 'order.fraud_rechecked' => ['Fraud check', '🛡️', '#f43f5e'],
            $event === 'login'                 => ['Login', '🔑', '#22c55e'],
            $event === 'logout'                => ['Logout', '🚪', '#94a3b8'],
            str_starts_with($event, 'search')  => ['Search', '🔎', '#d946ef'],
            str_contains($event, 'deleted')    => [\Illuminate\Support\Str::headline(str_replace('.', ' ', $event)), '🗑️', '#ef4444'],
            str_contains($event, 'created')    => [\Illuminate\Support\Str::headline(str_replace('.', ' ', $event)), '➕', '#10b981'],
            str_contains($event, 'updated')    => [\Illuminate\Support\Str::headline(str_replace('.', ' ', $event)), '✏️', '#0ea5e9'],
            str_contains($event, 'sent')       => [\Illuminate\Support\Str::headline(str_replace('.', ' ', $event)), '📤', '#06b6d4'],
            str_contains($event, 'payment')    => [\Illuminate\Support\Str::headline(str_replace('.', ' ', $event)), '💰', '#14b8a6'],
            default                            => [\Illuminate\Support\Str::headline(str_replace('.', ' ', $event)), '•', '#64748b'],
        };
    }

    public static function automationSignal(\Illuminate\Http\Request $request): ?string
    {
        $ua = (string) $request->userAgent();
        if (static::isBotUserAgent($ua)) {
            return 'user-agent';
        }
        if (!$request->headers->has('accept-language')) {
            return 'no-browser-headers';
        }
        $isIos = (bool) preg_match('/iPhone|iPad|iPod|CriOS|FxiOS/i', $ua);
        if (!$isIos && !$request->headers->has('sec-fetch-mode')) {
            if (preg_match('#(?:Chrome|Chromium|Edg|OPR|SamsungBrowser)/(\d+)#', $ua, $m) && (int) $m[1] >= 80) {
                return 'no-browser-headers';
            }
            if (preg_match('#Firefox/(\d+)#', $ua, $m) && (int) $m[1] >= 90) {
                return 'no-browser-headers';
            }
        }

        return null;
    }

    /**
     * "Which medium did this visit come through" — Google search, Facebook, a direct URL
     * (typed, bookmarked, or clicked from a non-web source like SMS/WhatsApp, which sends
     * no referrer at all), or another site. Derived from the raw referrer at display time
     * (not stored pre-computed) so relabeling what counts as which channel doesn't require
     * touching old rows — this app's own domain is filtered out first so an internal
     * click (e.g. home → this product) doesn't get mislabeled as an external "source".
     */
    public static function channelFromReferrer(?string $referrer): string
    {
        if (!$referrer) {
            return 'Direct / App';
        }

        $host = parse_url($referrer, PHP_URL_HOST) ?: '';
        if ($host === '' || $host === parse_url(config('app.url'), PHP_URL_HOST)) {
            return 'Direct / App';
        }

        return match (true) {
            (bool) preg_match('/(^|\.)google\./i', $host) => 'Google',
            (bool) preg_match('/(^|\.)bing\./i', $host) => 'Bing',
            (bool) preg_match('/(^|\.)(facebook|fb)\./i', $host) => 'Facebook',
            (bool) preg_match('/(^|\.)instagram\./i', $host) => 'Instagram',
            (bool) preg_match('/(^|\.)(twitter|x)\.com$/i', $host) || $host === 't.co' => 'Twitter/X',
            (bool) preg_match('/(^|\.)youtube\./i', $host) => 'YouTube',
            (bool) preg_match('/(^|\.)(wa\.me|whatsapp)\./i', $host) => 'WhatsApp',
            (bool) preg_match('/(^|\.)t\.me$/i', $host) || preg_match('/(^|\.)telegram\./i', $host) => 'Telegram',
            default => $host,
        };
    }
}
