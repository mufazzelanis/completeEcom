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
        'header_logo', 'order_button_text',
        'collect_address', 'require_address', 'order_form_fields',
        'thank_you_heading', 'thank_you_message', 'thank_you_redirect_url',
        'meta_title', 'meta_description', 'og_image',
        'views_count',
    ];

    protected $casts = [
        'collect_address'   => 'boolean',
        'require_address'   => 'boolean',
        'order_form_fields' => 'array',
        'price_override'    => 'decimal:2',
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
}
