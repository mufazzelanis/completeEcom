<?php

namespace App\Models;

use App\Support\StorefrontCache;
use Illuminate\Database\Eloquent\Model;

/**
 * A storefront promotion popup (Marketing → Promo Popups). The server picks the popups that
 * fit the current page (forPage); the browser then applies device / visitor / traffic-source /
 * trigger / frequency rules (partials/storefront/promo-popup) — those depend on the visitor.
 */
class PromoPopup extends Model
{
    protected $guarded = ['id', 'views', 'clicks', 'closes', 'orders', 'revenue'];

    protected $casts = [
        'is_active' => 'boolean',
        'new_tab' => 'boolean',
        'pages' => 'array',
        'category_ids' => 'array',
        'product_ids' => 'array',
        'countdown_to' => 'datetime',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'stats_since' => 'datetime',
        'revenue' => 'decimal:2',
    ];

    public const PAGES = ['all' => 'Every page', 'home' => 'Home', 'product' => 'Product pages', 'category' => 'Category pages', 'shop' => 'Shop / brands', 'blog' => 'Blog'];
    public const DEVICES = ['all' => 'Mobile + desktop', 'mobile' => 'Mobile only', 'desktop' => 'Desktop only'];
    public const AUDIENCES = ['all' => 'Everyone', 'new' => 'First-time visitors', 'returning' => 'Returning visitors'];
    public const SOURCES = ['all' => 'Any source', 'facebook' => 'Facebook / Instagram', 'google' => 'Google', 'direct' => 'Direct / typed'];
    public const TRIGGERS = ['delay' => 'After a few seconds', 'scroll' => 'After scrolling', 'exit' => 'When leaving (exit intent)', 'views' => 'After viewing products'];
    public const FREQUENCIES = ['session' => 'Once per visit', 'daily' => 'Once a day', 'days' => 'Once every few days', 'once' => 'Only once ever'];

    /** Session key: the popup a visitor last clicked, so an order placed afterwards is credited to it. */
    public const CLICK_SESSION_KEY = 'promo_popup_click';
    private const ATTRIBUTION_WINDOW = 86400; // seconds

    public function scopeLive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    /** Live / Scheduled / Ended / Off — with a colour for the admin badge. */
    public function status(): array
    {
        return match (true) {
            !$this->is_active => ['Off', '#6b7280'],
            $this->ends_at && $this->ends_at->isPast() => ['Ended', '#b91c1c'],
            $this->starts_at && $this->starts_at->isFuture() => ['Scheduled', '#2563eb'],
            default => ['Live', '#059669'],
        };
    }

    public function layout(): string
    {
        $hasText = filled($this->title) || filled($this->body) || filled($this->coupon_code) || filled($this->button_text);

        return $this->image ? ($hasText ? 'both' : 'image') : 'text';
    }

    public function ctr(): float
    {
        return $this->views ? round($this->clicks / $this->views * 100, 1) : 0.0;
    }

    /** What the storefront script needs — content and the visitor-side rules. */
    public function toClient(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'layout' => $this->layout(),
            'image' => $this->image ? image_thumb($this->image, 900) : null,
            'title' => $this->title,
            'body' => $this->body,
            'button' => $this->button_text,
            'url' => $this->link_url,
            'newTab' => $this->new_tab,
            'coupon' => $this->coupon_code,
            'countdown' => $this->countdown_to?->getTimestampMs(),
            'accent' => $this->accent_color ?: '#f97316',
            'device' => $this->device,
            'audience' => $this->audience,
            'source' => $this->source,
            'trigger' => $this->trigger,
            'triggerValue' => (int) $this->trigger_value,
            'frequency' => $this->frequency,
            'frequencyDays' => max(1, (int) $this->frequency_days),
        ];
    }

    /**
     * Popups for one storefront page, best first. $ctx = ['page' => home|product|category|shop|blog|other,
     * 'product' => id, 'categories' => [ids]]. The active list is cached (StorefrontCache resets it
     * whenever a popup is saved); the schedule is checked here on every request.
     */
    public static function forPage(array $ctx): array
    {
        $all = StorefrontCache::remember('promo_popups', fn () => self::where('is_active', true)
            ->orderByDesc('priority')->orderByDesc('id')->get()
            ->map(fn (self $p) => $p->toClient() + [
                '_pages' => $p->pages ?: ['all'],
                '_cats' => array_map('intval', $p->category_ids ?: []),
                '_products' => array_map('intval', $p->product_ids ?: []),
                '_starts' => $p->starts_at?->getTimestamp(),
                '_ends' => $p->ends_at?->getTimestamp(),
            ])->all());

        $now = time();
        $out = [];
        foreach ($all as $p) {
            if (($p['_starts'] && $p['_starts'] > $now) || ($p['_ends'] && $p['_ends'] < $now) || !self::fitsPage($p, $ctx)) {
                continue;
            }
            $out[] = array_filter($p, fn ($k) => $k[0] !== '_', ARRAY_FILTER_USE_KEY);
            if (count($out) === 3) {
                break;
            }
        }

        return $out;
    }

    private static function fitsPage(array $p, array $ctx): bool
    {
        $page = $ctx['page'] ?? 'other';
        if (!in_array('all', $p['_pages'], true) && !in_array($page, $p['_pages'], true)) {
            return false;
        }
        // "only these categories / products" narrows product and category pages
        if (in_array($page, ['product', 'category'], true) && ($p['_cats'] || $p['_products'])) {
            $inProduct = $page === 'product' && in_array((int) ($ctx['product'] ?? 0), $p['_products'], true);
            $inCategory = (bool) array_intersect($p['_cats'], $ctx['categories'] ?? []);

            return $inProduct || $inCategory;
        }

        return true;
    }

    /** Credits an order placed within a day of clicking a popup (AppServiceProvider: Order::created). */
    public static function attributeOrder(Order $order): void
    {
        if (app()->runningInConsole() || request()->is('admin/*') || !request()->hasSession()) {
            return;
        }
        $click = session(self::CLICK_SESSION_KEY);
        if (!is_array($click) || time() - (int) ($click['at'] ?? 0) > self::ATTRIBUTION_WINDOW) {
            return;
        }
        session()->forget(self::CLICK_SESSION_KEY);
        self::whereKey((int) $click['id'])->incrementEach(['orders' => 1, 'revenue' => (float) $order->total]);
    }
}
