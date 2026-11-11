<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LandingPage extends Model
{
    protected $fillable = [
        'title', 'slug', 'product_id', 'status',
        'hero_heading', 'hero_subheading', 'hero_image', 'content',
        'price_override',
        'header_logo', 'favicon', 'order_button_text', 'brand_color', 'theme',
        // Pixel tracking — per-page override of the site-wide Facebook Pixel / Google
        // Analytics / Google Ads settings, for campaigns that need their own ad-account pixel.
        'fb_pixel_id', 'fb_capi_access_token', 'ga_measurement_id', 'google_ads_conversion_id', 'google_ads_conversion_label',
        'collect_address', 'require_address', 'order_form_fields',
        'thank_you_heading', 'thank_you_message', 'thank_you_redirect_url', 'thank_you_button_text',
        'meta_title', 'meta_description', 'og_image',
        'views_count',
        // Urgency bar
        'urgency_bar_enabled', 'urgency_bar_text', 'urgency_bar_minutes', 'urgency_bar_seconds',
        // Rating line + trust badges
        'rating_value', 'rating_count', 'trust_badges',
        // How it works video
        'how_it_works_heading', 'how_it_works_video',
        // Benefits grid
        'benefits_heading', 'benefits',
        // Who is this for
        'who_for_heading', 'who_for',
        // Testimonials
        'testimonials_heading', 'testimonial_videos', 'testimonial_images',
        // Pricing / offer box
        'offer_badge_text', 'pricing_items', 'compare_at_price',
        // FAQ
        'faqs_heading', 'faqs',
        // Certificates
        'certificates_heading', 'certificates_subheading', 'certificates',
        // Delivery zones (shipping charge picker on the order form)
        'delivery_zones', 'delivery_mode',
    ];

    protected $casts = [
        'collect_address'      => 'boolean',
        'require_address'      => 'boolean',
        'order_form_fields'    => 'array',
        'price_override'       => 'decimal:2',
        'urgency_bar_enabled'  => 'boolean',
        'urgency_bar_minutes'  => 'integer',
        'urgency_bar_seconds'  => 'integer',
        'rating_value'         => 'decimal:1',
        'rating_count'         => 'integer',
        'trust_badges'         => 'array',
        'benefits'             => 'array',
        'who_for'              => 'array',
        'testimonial_videos'   => 'array',
        'testimonial_images'   => 'array',
        'pricing_items'        => 'array',
        'compare_at_price'     => 'decimal:2',
        'faqs'                 => 'array',
        'certificates'         => 'array',
        'delivery_zones'       => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($m) {
            if (empty($m->slug)) {
                $m->slug = Str::slug($m->title);
            }
        });
    }

    // Sanitized on write, not read — same reasoning as Page::content (rendered unescaped
    // with {!! !!} in landing/show.blade.php, so the stored HTML must already be safe).
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = $value !== null ? clean($value, 'rich_content') : $value;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * What actually shows on the page and gets charged — price_override if the admin set
     * one, otherwise whatever the linked product's own final price is right now (so a
     * flash sale on the linked product is reflected here too), otherwise null (no price
     * — e.g. a lead-gen landing page with no product/price at all).
     */
    public function getEffectivePriceAttribute(): ?float
    {
        if ($this->price_override !== null) {
            return (float) $this->price_override;
        }

        return $this->product?->final_price;
    }

    /** site | custom | none — where the order form's delivery charge comes from. */
    public function deliveryMode(): string
    {
        return in_array($this->delivery_mode, ['site', 'custom', 'none'], true) ? $this->delivery_mode : 'site';
    }

    /**
     * The delivery areas the order form offers, each [key, label, charge]. `key` is the store's
     * zone ('dhaka' / 'outside_dhaka' / 'flat') for Settings → Shipping, or null for the page's
     * own zones. Charges are before the free-delivery threshold (see deliveryFreeFrom()).
     */
    public function deliveryOptions(): array
    {
        $calc = \App\Services\ShippingCalculator::class;

        return match ($this->deliveryMode()) {
            'none' => [],
            'custom' => collect($this->delivery_zones ?: [])
                ->map(fn ($z) => ['key' => null, 'label' => (string) ($z['label'] ?? ''), 'charge' => (float) ($z['charge'] ?? 0)])
                ->filter(fn ($z) => $z['label'] !== '')->values()->all(),
            default => $calc::usesZones()
                ? [
                    ['key' => $calc::ZONE_DHAKA, 'label' => 'ঢাকার ভিতরে', 'charge' => (float) setting('dhaka_charge', 60)],
                    ['key' => $calc::ZONE_OUTSIDE_DHAKA, 'label' => 'ঢাকার বাইরে', 'charge' => (float) setting('outside_dhaka_charge', 120)],
                ]
                : [['key' => 'flat', 'label' => setting('flat_rate_label', 'Standard Delivery') ?: 'ডেলিভারি চার্জ', 'charge' => (float) setting('flat_rate_amount', 60)]],
        };
    }

    /** Settings → Shipping's free-delivery threshold, for pages that use those settings; null when none. */
    public function deliveryFreeFrom(): ?float
    {
        return $this->deliveryMode() === 'site' && setting('free_shipping_enabled', '0') === '1'
            ? (float) setting('free_shipping_min', 999)
            : null;
    }

    /**
     * Which option starts selected: Settings → Shipping → Default Delivery Area. On a page's own
     * zones that's the one whose name says inside/ভিতরে or outside/বাইরে; null = the customer picks.
     */
    public function defaultDeliveryIndex(): ?int
    {
        $options = $this->deliveryOptions();
        if (count($options) <= 1) {
            return $options ? 0 : null;
        }
        $zone = \App\Services\ShippingCalculator::defaultZone();
        if ($zone === null) {
            return null;
        }
        foreach ($options as $i => $o) {
            if ($o['key'] === $zone) {
                return $i;
            }
        }
        $outside = '/(বাইর|বাহির|outside|out of dhaka)/iu';
        $inside = '/(ভিতর|ভেতর|inside|dhaka city|ঢাকা সিটি)/iu';
        foreach ($options as $i => $o) {
            $isOutside = preg_match($outside, $o['label']) === 1;
            if ($zone === \App\Services\ShippingCalculator::ZONE_OUTSIDE_DHAKA ? $isOutside : (!$isOutside && preg_match($inside, $o['label']))) {
                return $i;
            }
        }

        return 0;
    }

    /** The delivery charge for an option at this subtotal (free-delivery threshold applied for site settings). */
    public function deliveryCharge(int $index, float $subtotal): float
    {
        $option = $this->deliveryOptions()[$index] ?? null;
        if (!$option) {
            return 0.0;
        }
        if ($this->deliveryMode() === 'site') {
            return \App\Services\ShippingCalculator::calculate($subtotal, $option['key'] === 'flat' ? null : $option['key']);
        }

        return $option['charge'];
    }
}
