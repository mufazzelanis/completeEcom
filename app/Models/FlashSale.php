<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlashSale extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'banner_text', 'banner_color',
        'theme', 'layout', 'home_limit', 'show_on_home', 'show_upcoming', 'show_stock_bar', 'priority',
        'starts_at', 'ends_at', 'is_active',
    ];

    protected $casts = [
        'starts_at'      => 'datetime',
        'ends_at'        => 'datetime',
        'is_active'      => 'boolean',
        'show_on_home'   => 'boolean',
        'show_upcoming'  => 'boolean',
        'show_stock_bar' => 'boolean',
        'home_limit'     => 'integer',
        'priority'       => 'integer',
    ];

    /**
     * Storefront looks. Each is three gradient stops plus a readable accent; they're
     * applied as CSS variables (see style_vars) rather than Tailwind classes, so the
     * admin can switch themes without a rebuild. 'custom' derives its stops from
     * banner_color.
     */
    public const THEMES = [
        'inferno'  => ['label' => 'Inferno',  'from' => '#f97316', 'via' => '#ef4444', 'to' => '#db2777', 'accent' => '#ea580c'],
        'midnight' => ['label' => 'Midnight', 'from' => '#4f46e5', 'via' => '#7c3aed', 'to' => '#c026d3', 'accent' => '#7c3aed'],
        'emerald'  => ['label' => 'Emerald',  'from' => '#059669', 'via' => '#0d9488', 'to' => '#0891b2', 'accent' => '#059669'],
        'gold'     => ['label' => 'Gold Rush', 'from' => '#f59e0b', 'via' => '#f97316', 'to' => '#e11d48', 'accent' => '#d97706'],
        'ocean'    => ['label' => 'Ocean',    'from' => '#0284c7', 'via' => '#2563eb', 'to' => '#4f46e5', 'accent' => '#2563eb'],
        'noir'     => ['label' => 'Noir',     'from' => '#18181b', 'via' => '#3f3f46', 'to' => '#18181b', 'accent' => '#f59e0b'],
        'custom'   => ['label' => 'Custom',   'from' => null,      'via' => null,      'to' => null,      'accent' => null],
    ];

    public const LAYOUTS = ['carousel' => 'Carousel (swipe row)', 'grid' => 'Grid'];

    protected static function booted(): void
    {
        static::saving(function (self $sale) {
            if (blank($sale->slug)) {
                $base = Str::slug($sale->name) ?: 'flash-sale';
                $slug = $base;
                $i = 2;
                while (static::where('slug', $slug)->where('id', '!=', $sale->id ?? 0)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $sale->slug = $slug;
            }
        });
    }

    public function products()
    {
        return $this->hasMany(FlashSaleProduct::class)->orderByDesc('is_featured')->orderBy('sort_order')->orderBy('id');
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now());
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where('is_active', true)->where('starts_at', '>', now());
    }

    public function isLive(): bool
    {
        return $this->is_active
            && now()->between($this->starts_at, $this->ends_at);
    }

    public function getStatusAttribute(): string
    {
        if (! $this->is_active) return 'inactive';
        if (now()->lt($this->starts_at))  return 'upcoming';
        if (now()->gt($this->ends_at))    return 'ended';
        return 'live';
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'live'     => 'bg-green-100 text-green-700',
            'upcoming' => 'bg-blue-100 text-blue-700',
            'ended'    => 'bg-gray-100 text-gray-500',
            default    => 'bg-red-100 text-red-600',
        };
    }

    /** How far through its window the sale is, 0-100 (for the admin timeline bar). */
    public function getElapsedPercentAttribute(): int
    {
        $total = max(1, $this->ends_at->getTimestamp() - $this->starts_at->getTimestamp());
        $done = now()->getTimestamp() - $this->starts_at->getTimestamp();

        return (int) max(0, min(100, round($done / $total * 100)));
    }

    /** @return array{from:string, via:string, to:string, accent:string} */
    public function getPaletteAttribute(): array
    {
        $theme = self::THEMES[$this->theme] ?? self::THEMES['inferno'];

        if ($this->theme === 'custom' || ! $theme['from']) {
            $c = preg_match('/^#[0-9a-f]{6}$/i', (string) $this->banner_color) ? $this->banner_color : '#ef4444';
            return ['from' => $c, 'via' => self::shade($c, -0.12), 'to' => self::shade($c, -0.3), 'accent' => self::shade($c, -0.1)];
        }

        return $theme;
    }

    /** Inline style that exposes the palette as --fs-* custom properties. */
    public function getStyleVarsAttribute(): string
    {
        $p = $this->palette;

        return "--fs-from:{$p['from']};--fs-via:{$p['via']};--fs-to:{$p['to']};--fs-accent:{$p['accent']};";
    }

    public function getUrlAttribute(): string
    {
        return route('flash-sale.show', $this->slug);
    }

    /** The live sale shown on the storefront — highest priority first, then whichever ends soonest. */
    public static function current(): ?self
    {
        return self::live()->orderByDesc('priority')->orderBy('ends_at')->first();
    }

    /** The next scheduled sale that's allowed to be teased before it starts. */
    public static function nextUpcoming(): ?self
    {
        return self::upcoming()->where('show_upcoming', true)->orderBy('starts_at')->first();
    }

    /** A paused, renamed copy with the same products (sold counters reset). */
    public function duplicate(): self
    {
        $copy = $this->replicate(['slug']);
        $copy->name = $this->name . ' (copy)';
        $copy->is_active = false;
        $copy->save();

        foreach ($this->products()->get() as $item) {
            $copy->products()->create($item->only([
                'product_id', 'discount_type', 'discount_value', 'stock_limit', 'per_order_limit', 'sort_order', 'is_featured',
            ]));
        }

        return $copy;
    }

    private static function shade(string $hex, float $amount): string
    {
        $rgb = array_map('hexdec', str_split(ltrim($hex, '#'), 2));
        $rgb = array_map(fn ($c) => (int) max(0, min(255, $amount < 0 ? $c * (1 + $amount) : $c + (255 - $c) * $amount)), $rgb);

        return sprintf('#%02x%02x%02x', ...$rgb);
    }
}
