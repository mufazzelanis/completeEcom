<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A browser on the storefront, kept up to date by the page's heartbeat (see
 * LiveVisitorController::ping). Live = seen within LIVE_SECONDS.
 */
class LiveVisitor extends Model
{
    public $timestamps = false;

    /** Seen within this many seconds counts as "on the site now" (heartbeat is every 20 s). */
    public const LIVE_SECONDS = 60;

    protected $guarded = ['id'];

    protected $casts = [
        'path' => 'array',
        'cart_total' => 'decimal:2',
        'first_seen_at' => 'datetime',
        'page_started_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeLive($query)
    {
        return $query->where('last_seen_at', '>=', now()->subSeconds(self::LIVE_SECONDS));
    }

    /** ['city' => …, 'country' => …, 'cc' => 'BD'] for an IP, from the cache filled in the background. */
    public static function geo(?string $ip): ?array
    {
        if (!$ip) {
            return null;
        }
        // localhost / LAN addresses can't be looked up — say so instead of "Locating…" forever
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return ['city' => 'Local network', 'country' => null, 'cc' => null];
        }

        return Cache::get("ip_geo:{$ip}");
    }

    /** 🇧🇩 from "BD". */
    public static function flag(?string $countryCode): string
    {
        if (!$countryCode || strlen($countryCode) !== 2) {
            return '🌐';
        }

        return mb_chr(0x1F1E6 + ord(strtoupper($countryCode[0])) - 65) . mb_chr(0x1F1E6 + ord(strtoupper($countryCode[1])) - 65);
    }

    /**
     * Label + accent colour per kind of page — hex (not Tailwind classes) so every colour works
     * without a CSS rebuild and reads well on both the light and dark admin themes.
     */
    public const PAGE_TYPES = [
        'checkout' => ['Checkout', '#10b981', '💳'],
        'cart'     => ['Cart', '#f59e0b', '🛒'],
        'product'  => ['Product', '#6366f1', '🧴'],
        'search'   => ['Search', '#d946ef', '🔎'],
        'category' => ['Category', '#0ea5e9', '🗂️'],
        'brand'    => ['Brand', '#14b8a6', '🏷️'],
        'shop'     => ['Shop', '#3b82f6', '🛍️'],
        'home'     => ['Home', '#06b6d4', '🏠'],
        'landing'  => ['Landing page', '#f97316', '🚀'],
        'blog'     => ['Blog', '#f43f5e', '📝'],
        'account'  => ['Account', '#64748b', '👤'],
        'other'    => ['Other', '#94a3b8', '📄'],
    ];

    public function pageMeta(): array
    {
        return self::PAGE_TYPES[$this->page_type] ?? self::PAGE_TYPES['other'];
    }
}
