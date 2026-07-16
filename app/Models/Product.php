<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'seller_id',
        'type', 'category_id', 'subcategory_id', 'brand_id', 'name', 'slug',
        'short_description', 'description', 'sku', 'barcode', 'price', 'sale_price',
        'stock', 'low_stock_threshold', 'weight', 'image', 'download_file',
        'download_expiry_days', 'is_active', 'is_featured', 'views',
        // Basic SEO
        'meta_title', 'meta_description', 'focus_keyword', 'canonical_url', 'robots_meta',
        'sitemap_priority', 'sitemap_changefreq', 'redirect_url', 'breadcrumb_title',
        // Social sharing
        'og_title', 'og_description', 'og_image',
        'twitter_card', 'twitter_title', 'twitter_description', 'twitter_image',
        // Search visibility
        'noindex', 'nofollow', 'nosnippet', 'noimageindex',
        // Schema markup
        'schema_type', 'schema_condition', 'schema_availability', 'price_valid_until',
        'gtin', 'mpn', 'country_of_origin',
        // Merchant Center
        'google_category', 'google_product_type', 'age_group', 'gender',
        'color_description', 'size_description', 'material',
        // Image SEO
        'image_alt', 'image_title',
        // AI SEO
        'ai_summary', 'ai_overview', 'ai_key_features', 'ai_benefits', 'ai_use_cases', 'ai_comparison',
        'seo_score',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'sitemap_priority' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'noindex' => 'boolean',
        'nofollow' => 'boolean',
        'nosnippet' => 'boolean',
        'noimageindex' => 'boolean',
        'price_valid_until' => 'date',
        'ai_key_features' => 'array',
        'ai_benefits' => 'array',
        'ai_use_cases' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Category::class, 'subcategory_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('is_approved', true);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function seller()
    {
        return $this->belongsTo(Vendor::class, 'seller_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function faqs()
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
    }

    public function specs()
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort_order');
    }

    public function bundleItems()
    {
        return $this->hasMany(BundleItem::class, 'bundle_product_id')->with('itemProduct')->orderBy('sort_order');
    }

    public function recommendations()
    {
        return $this->hasMany(ProductRecommendation::class);
    }

    public function crossSells()
    {
        return $this->hasMany(ProductRecommendation::class)->where('type', 'cross_sell')->with('recommended');
    }

    public function upsells()
    {
        return $this->hasMany(ProductRecommendation::class)->where('type', 'upsell')->with('recommended');
    }

    public function warehouseStock()
    {
        return $this->hasMany(WarehouseStock::class);
    }

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function isLowStock(): bool
    {
        return $this->stock <= $this->low_stock_threshold;
    }

    public function isDigital(): bool
    {
        return $this->type === 'digital';
    }

    public function isBundle(): bool
    {
        return $this->type === 'bundle';
    }

    public function isVariable(): bool
    {
        return $this->type === 'variable';
    }

    public function isSimple(): bool
    {
        return $this->type === 'simple';
    }

    public function typeBadge(): string
    {
        return match ($this->type) {
            'variable' => 'bg-purple-100 text-purple-700',
            'bundle' => 'bg-blue-100 text-blue-700',
            'digital' => 'bg-teal-100 text-teal-700',
            default => 'bg-gray-100 text-gray-600',
        };
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function colors()
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getEffectivePriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getAverageRatingAttribute()
    {
        return $this->reviews()->avg('rating') ?? 0;
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        if ($field === null && ctype_digit((string) $value)) {
            return $this->where('id', $value)->first();
        }

        return parent::resolveRouteBinding($value, $field);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
