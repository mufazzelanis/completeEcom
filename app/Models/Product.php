<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'seller_id',
        'type', 'category_id', 'subcategory_id', 'brand_id', 'name', 'slug', 'sort_order',
        'short_description', 'description', 'sku', 'barcode', 'price', 'sale_price',
        'stock', 'low_stock_threshold', 'weight', 'image', 'download_file',
        'download_expiry_days', 'is_active', 'is_featured', 'views',
        'approval_status', 'rejection_reason',
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
        'marked_out_of_stock' => 'boolean',
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
        // Last chance to name any inventory-ledger row that doesn't carry its own copy yet
        // (e.g. inserted by raw query) before ON DELETE SET NULL detaches it from this product.
        static::deleting(function ($product) {
            foreach (['stock_adjustments', 'purchase_items'] as $table) {
                \Illuminate\Support\Facades\DB::table($table)->where('product_id', $product->id)->whereNull('product_name')
                    ->update(['product_name' => $product->name, 'product_sku' => $product->sku]);
            }
        });
    }

    // Sanitized on write, not read — description is rendered unescaped
    // ({!! !!}) on the product page, and this is the only gate: both admin and
    // seller-authored descriptions pass through here (sellers are the less
    // trusted author, and their description field had no HTML restriction at
    // all before this). See Page::setContentAttribute() for the same pattern.
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = $value !== null ? clean($value, 'rich_content') : $value;
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
        return $this->hasMany(ProductRecommendation::class)->where('type', 'cross_sell')
            ->whereHas('recommended', fn ($q) => $q->where('is_active', true))
            ->with('recommended')->orderBy('sort_order');
    }

    public function upsells()
    {
        return $this->hasMany(ProductRecommendation::class)->where('type', 'upsell')
            ->whereHas('recommended', fn ($q) => $q->where('is_active', true))
            ->with('recommended')->orderBy('sort_order');
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

    /**
     * Out of stock but can be pre-ordered: Settings → pre-orders on, a physical product with no
     * size/colour choice, and at least one bKash / Nagad (mobile banking) method active to take
     * the delivery-charge advance.
     */
    public function canPreorder(): bool
    {
        return setting('preorder_enabled', '1') === '1'
            && $this->is_active
            && $this->available_stock <= 0
            && !$this->isDigital()
            && !$this->isVariable()
            && static::preorderPaymentMethods()->isNotEmpty();
    }

    /** Active mobile-banking methods (bKash, Nagad, Rocket…) — how a pre-order's advance is paid. */
    public static function preorderPaymentMethods()
    {
        static $methods = null;

        return $methods ??= PaymentMethod::where('is_active', true)->where('type', 'mobile_banking')->orderBy('sort_order')->get();
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

    public function sizes()
    {
        return $this->hasMany(ProductSize::class)->orderBy('sort_order')->orderBy('id');
    }

    public function colors()
    {
        return $this->hasMany(ProductColor::class)->orderBy('sort_order')->orderBy('id');
    }

    public function combinations()
    {
        return $this->hasMany(ProductVariantCombination::class)->orderBy('sort_order')->orderBy('id');
    }

    public function getEffectivePriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    /**
     * What can actually be sold right now. 0 while the admin has the product switched to
     * "out of stock" (marked_out_of_stock) — every storefront check (cards, product page,
     * cart, checkout, landing pages, feeds) goes through this, so one switch covers them all.
     */
    public function getAvailableStockAttribute()
    {
        if ($this->marked_out_of_stock) {
            return 0;
        }
        if (! $this->isBundle()) {
            return $this->stock;
        }

        if ($this->bundleItems->isEmpty()) {
            return 0;
        }

        return $this->bundleItems->min(fn ($item) => intdiv($item->itemProduct->stock, $item->quantity));
    }

    /**
     * A bundle's own `stock` column is otherwise never touched (a bundle isn't
     * decremented directly — its components are), so it stays synced here as a
     * denormalized copy of the same number getAvailableStockAttribute() already
     * computes live, for everything that queries `stock` in SQL directly
     * (admin filters, reports, low-stock) rather than through the accessor.
     * Call after anything that changes bundle composition or a component's stock.
     */
    public function refreshBundleStock(): void
    {
        if (! $this->isBundle()) {
            return;
        }

        $items = $this->bundleItems()->with('itemProduct')->get();

        $stock = $items->isEmpty() ? 0 : (int) $items->min(
            fn ($item) => $item->itemProduct ? intdiv($item->itemProduct->stock, max(1, $item->quantity)) : 0
        );

        $this->update(['stock' => $stock]);
    }

    public function activeFlashSaleProduct()
    {
        return $this->hasOne(FlashSaleProduct::class, 'product_id')
            ->whereHas('flashSale', fn ($q) => $q->where('is_active', true)
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now()));
    }

    public function getFinalPriceAttribute()
    {
        $flash = $this->activeFlashSaleProduct;

        if ($flash && $flash->isAvailable()) {
            return $flash->sale_price;
        }

        return $this->effective_price;
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

    /**
     * Every storefront listing (shop, category, home sections) filters through
     * this scope — folding the approval check in here means a vendor's pending
     * or rejected product is automatically invisible everywhere, with no other
     * call site needing to know approval_status exists.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('approval_status', 'approved');
    }

    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /*
     * Storefront search (shop page + search-box suggestions). A single "%whole phrase%" match
     * found nothing for how customers actually type — "cerave 453g" against "CeraVe
     * Moisturizing Cream ... 453G", "natures bounty" against "Nature’s Bounty" — so:
     *   - every word must appear somewhere in the name, SKU or brand, in any order;
     *   - apostrophes are ignored on both sides, Bangla digits are read as digits;
     *   - British/US spellings match each other (moisturising ↔ moisturizing);
     *   - loose mode (used only when nothing matches every word) needs at least half the
     *     words, so a slightly-off search still shows the closest products.
     * Results are ranked by orderByRelevance(): name starting with the search, then containing
     * it as a phrase, then by how many words matched.
     */

    /** Normalized search words: lowercase, no apostrophes, 2+ characters (or a number). */
    public static function searchWords(string $term): array
    {
        $term = str_replace(['’', "'", '`', '‘'], '', mb_strtolower(normalize_digits($term)));
        $words = preg_split('/[^\p{L}\p{N}.]+/u', $term, -1, PREG_SPLIT_NO_EMPTY);

        return array_values(array_unique(array_filter($words, fn ($w) => mb_strlen(trim($w, '.')) >= 2 || ctype_digit($w))));
    }

    /** SQL condition (with bindings) for one word matching the name, SKU or brand. */
    private static function searchWordSql(string $word): array
    {
        $variants = [$word];
        foreach ([['ising', 'izing'], ['iser', 'izer'], ['ise', 'ize'], ['colour', 'color'], ['flavour', 'flavor']] as [$uk, $us]) {
            if (str_contains($word, $uk)) {
                $variants[] = str_replace($uk, $us, $word);
            } elseif (str_contains($word, $us)) {
                $variants[] = str_replace($us, $uk, $word);
            }
        }
        $sql = [];
        $bindings = [];
        foreach (array_unique($variants) as $v) {
            $like = '%' . addcslashes($v, '%_\\') . '%';
            // name with apostrophes stripped, so "natures" matches "Nature’s"
            $sql[] = "REPLACE(REPLACE(products.name, '’', ''), '''', '') LIKE ? OR products.sku LIKE ?"
                . ' OR EXISTS (SELECT 1 FROM brands WHERE brands.id = products.brand_id AND brands.name LIKE ?)';
            array_push($bindings, $like, $like, $like);
        }

        return ['(' . implode(' OR ', $sql) . ')', $bindings];
    }

    /** "How many of the words match" as one SQL expression. */
    private static function searchScoreSql(array $words): array
    {
        $parts = [];
        $bindings = [];
        foreach ($words as $w) {
            [$cond, $b] = static::searchWordSql($w);
            $parts[] = "(CASE WHEN {$cond} THEN 1 ELSE 0 END)";
            array_push($bindings, ...$b);
        }

        return ['(' . implode(' + ', $parts) . ')', $bindings];
    }

    public function scopeSearch($query, string $term, bool $loose = false)
    {
        $words = static::searchWords($term);
        $phrase = '%' . addcslashes(trim($term), '%_\\') . '%';
        if (!$words) {
            return $query->where('products.name', 'like', $phrase);
        }

        if ($loose && count($words) > 1) {
            [$score, $bindings] = static::searchScoreSql($words);

            return $query->whereRaw("{$score} >= ?", [...$bindings, (int) ceil(count($words) / 2)]);
        }

        return $query->where(function ($q) use ($words, $phrase) {
            $q->where(function ($all) use ($words) {
                foreach ($words as $w) {
                    [$cond, $bindings] = static::searchWordSql($w);
                    $all->whereRaw($cond, $bindings);
                }
            })->orWhere('products.short_description', 'like', $phrase);
        });
    }

    public function scopeOrderByRelevance($query, string $term)
    {
        $t = addcslashes(trim($term), '%_\\');
        $words = static::searchWords($term);
        [$score, $bindings] = $words ? static::searchScoreSql($words) : ['0', []];

        return $query
            ->orderByRaw('CASE WHEN products.name LIKE ? THEN 0 WHEN products.name LIKE ? THEN 1 ELSE 2 END', ["{$t}%", "%{$t}%"])
            ->orderByRaw("{$score} DESC", $bindings)
            ->orderByDesc('products.views');
    }
}
