<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\Product;

/**
 * The "buying guide" under a category or brand listing (shop/_guide): price range, stock, top
 * brands (or categories), popular products and a short FAQ — all computed from that listing's
 * own products, so every page gets text that is specific to it and stays true as the catalogue
 * changes. Category pages were a bare product grid (77 of 83 had no description), which is thin
 * content for exactly the "<category> price in Bangladesh" searches they should rank for.
 * Cached with the storefront data (StorefrontCache resets it whenever a product changes).
 */
class ListingGuide
{
    /** @param int[] $categoryIds the category and its subcategories */
    public static function forCategory(Category $category, array $categoryIds): ?array
    {
        return StorefrontCache::remember('guide:c:' . $category->id, fn () => self::build(
            $category->name,
            fn ($q) => $q->where(fn ($w) => $w->whereIn('category_id', $categoryIds)->orWhereIn('subcategory_id', $categoryIds)),
            'brand'
        ));
    }

    public static function forBrand(Brand $brand): ?array
    {
        return StorefrontCache::remember('guide:b:' . $brand->id, fn () => self::build(
            $brand->name,
            fn ($q) => $q->where('brand_id', $brand->id),
            'category'
        ));
    }

    private static function build(string $name, \Closure $scope, string $groupBy): ?array
    {
        $base = fn () => Product::query()->active()->where($scope);
        $price = 'COALESCE(NULLIF(sale_price, 0), price)';

        $stats = $base()->selectRaw("COUNT(*) n, MIN($price) lo, MAX($price) hi, SUM(CASE WHEN stock > 0 AND marked_out_of_stock = 0 THEN 1 ELSE 0 END) in_stock")->first();
        if (!$stats || (int) $stats->n < 2) {
            return null; // nothing useful to say about one product
        }

        // brands within a category, or categories within a brand
        $groups = $groupBy === 'brand'
            ? $base()->whereNotNull('brand_id')->selectRaw('brand_id k, COUNT(*) n')->groupBy('brand_id')->orderByDesc('n')->limit(8)->pluck('n', 'k')
            : $base()->whereNotNull('category_id')->selectRaw('category_id k, COUNT(*) n')->groupBy('category_id')->orderByDesc('n')->limit(8)->pluck('n', 'k');
        $models = ($groupBy === 'brand' ? Brand::where('is_active', true) : Category::active())
            ->whereIn('id', $groups->keys())->get(['id', 'name', 'slug'])->keyBy('id');
        $links = $groups->map(fn ($n, $id) => isset($models[$id]) ? [
            'name' => $models[$id]->name,
            'url' => $groupBy === 'brand' ? route('shop.brand', $models[$id]->slug) : route('shop.category', $models[$id]->slug),
            'count' => (int) $n,
        ] : null)->filter()->values()->all();

        $popular = $base()->where('stock', '>', 0)->where('marked_out_of_stock', false)
            ->orderByDesc('views')->limit(6)->get(['id', 'name', 'slug', 'price', 'sale_price'])
            ->map(fn ($p) => ['name' => $p->name, 'url' => route('products.show', $p->slug), 'price' => (float) ($p->sale_price ?: $p->price)])
            ->all();

        $sym = setting('currency_symbol', '৳');
        $money = fn ($v) => $sym . number_format((float) $v);
        preg_match_all('/\d+/', setting('delivery_days', '3-7'), $days);
        $days = array_map('intval', $days[0]) ?: [3, 7];
        $returnDays = (int) setting('return_days', '7');
        $cod = PaymentMethod::where('is_active', true)->whereIn('slug', ['cod', 'cash_on_delivery'])->exists();
        $site = setting('site_name', config('app.name'));

        $faq = [[
            "What is the price of {$name} in Bangladesh?",
            "At {$site}, {$name} products are priced from {$money($stats->lo)} to {$money($stats->hi)}. There are {$stats->n} products to choose from, {$stats->in_stock} of them in stock right now — the prices shown on each product are the current prices.",
        ]];
        if ($links) {
            $faq[] = $groupBy === 'brand'
                ? ["Which {$name} brands are available?", 'You can buy ' . collect($links)->pluck('name')->take(6)->join(', ', ' and ') . " {$name} products at {$site}."]
                : ["What {$name} products can I buy?", "{$name} products at {$site} include " . collect($links)->pluck('name')->take(6)->map(fn ($c) => mb_strtolower($c))->join(', ', ' and ') . '.'];
        }
        $faq[] = ['How long does delivery take?', 'Orders are delivered all over Bangladesh in ' . (min($days) === max($days) ? min($days) : min($days) . '–' . max($days)) . ' days' . ($cod ? ', and you can pay cash on delivery.' : '.')];
        if ($returnDays > 0) {
            $faq[] = ['Can I return a product?', "Yes — you can request a return within {$returnDays} days of delivery."];
        }

        return [
            'name' => $name,
            'count' => (int) $stats->n,
            'in_stock' => (int) $stats->in_stock,
            'lo' => $money($stats->lo),
            'hi' => $money($stats->hi),
            'groupLabel' => $groupBy === 'brand' ? 'Popular brands' : 'Shop by category',
            'links' => $links,
            'popular' => array_map(fn ($p) => $p + ['priceText' => $money($p['price'])], $popular),
            'faq' => $faq,
        ];
    }
}
