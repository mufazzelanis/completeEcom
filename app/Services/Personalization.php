<?php

namespace App\Services;

use App\Models\Banner;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductView;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What this visitor is into, from their own browsing (ProductView — user when logged in, else
 * the browser session): "Recently viewed", hero banners for their categories first, and
 * "Frequently bought together" from real orders. Everything that's the same for every visitor
 * is cached; per-visitor parts are single indexed queries, memoized per request. Product rows
 * are always loaded fresh, so stock and price are never stale.
 */
class Personalization
{
    private static ?Collection $views = null;

    /** Eager loads every product card partial needs (avoids per-card queries). */
    public const CARD_WITH = ['activeFlashSaleProduct', 'reviews', 'brand', 'category'];

    /** This visitor's latest views (newest first), one row per product. */
    private static function views(): Collection
    {
        if (static::$views !== null) {
            return static::$views;
        }
        $query = ProductView::query()->orderByDesc('viewed_at')->limit(40)->select('product_id', 'viewed_at');
        auth()->id() ? $query->where('user_id', auth()->id()) : $query->where('session_id', session()->getId());

        return static::$views = $query->get()->unique('product_id')->values();
    }

    /** Products this visitor looked at recently — active and in stock first, newest first. */
    public static function recentlyViewed(?int $exceptId = null, int $limit = 12): Collection
    {
        $ids = static::views()->pluck('product_id')->reject(fn ($id) => $id === $exceptId)->take($limit * 2)->values();
        if ($ids->isEmpty()) {
            return collect();
        }
        $products = Product::active()->with(static::CARD_WITH)->whereIn('id', $ids)->get()->keyBy('id');

        return $ids->map(fn ($id) => $products->get($id))->filter()->take($limit)->values();
    }

    /** Category ids this visitor has shown interest in, strongest first (by how many products they viewed there). */
    public static function interestCategoryIds(): Collection
    {
        $ids = static::views()->pluck('product_id')->take(30);
        if ($ids->isEmpty()) {
            return collect();
        }

        return Product::whereIn('id', $ids)->get(['category_id', 'subcategory_id'])
            ->flatMap(fn ($p) => array_filter([$p->category_id, $p->subcategory_id]))
            ->countBy()->sortDesc()->keys()->values();
    }

    /**
     * Hero banners with the ones pointing at this visitor's interest categories moved to the
     * front (admin order otherwise kept). A banner "points at" a category when its button link
     * contains that category's slug. Nothing changes for a first-time visitor.
     */
    public static function orderBanners(Collection $banners): Collection
    {
        if ($banners->count() < 2) {
            return $banners;
        }
        $interest = static::interestCategoryIds();
        if ($interest->isEmpty()) {
            return $banners;
        }
        $slugs = Cache::remember('personalization:category_slugs', 600, fn () => Category::pluck('slug', 'id')->all());
        $wanted = $interest->map(fn ($id) => $slugs[$id] ?? null)->filter()->values();
        if ($wanted->isEmpty()) {
            return $banners;
        }

        $rank = function (Banner $banner) use ($wanted) {
            $link = strtolower((string) $banner->button_link);
            foreach ($wanted as $i => $slug) {
                if ($link !== '' && preg_match('~(^|/|=)' . preg_quote(strtolower($slug), '~') . '($|[/?&#])~', $link)) {
                    return $i;
                }
            }

            return 999;
        };

        return $banners->values()->map(fn ($b, $i) => [$b, $rank($b), $i])
            ->sort(fn ($a, $b) => [$a[1], $a[2]] <=> [$b[1], $b[2]])
            ->map(fn ($x) => $x[0])->values();
    }

    /**
     * Products to offer alongside $product as a bundle: the admin's own cross-sell picks first,
     * then what real customers bought in the same order, then the best sellers of the same
     * category — so a new shop still gets sensible suggestions and they sharpen as orders come
     * in. Only simple, active, in-stock products (they go to the cart in one tap, no options).
     */
    public static function frequentlyBoughtTogether(Product $product, int $limit = 2): Collection
    {
        $ids = Cache::remember("fbt:{$product->id}", 21600, function () use ($product) {
            $manual = DB::table('product_recommendations')->where('product_id', $product->id)->where('type', 'cross_sell')
                ->orderBy('sort_order')->pluck('recommended_product_id');

            $together = DB::table('order_items as a')
                ->join('order_items as b', fn ($j) => $j->on('a.order_id', '=', 'b.order_id')->on('a.product_id', '!=', 'b.product_id'))
                ->join('orders as o', 'o.id', '=', 'a.order_id')
                ->where('a.product_id', $product->id)
                ->whereNotIn('o.status', ['cancelled', 'refunded'])
                ->groupBy('b.product_id')
                ->orderByRaw('COUNT(DISTINCT a.order_id) DESC')
                ->limit(10)
                ->pluck('b.product_id');

            $sameCategory = $product->category_id ? DB::table('order_items as oi')
                ->join('products as p', 'p.id', '=', 'oi.product_id')
                ->where('p.category_id', $product->category_id)
                ->where('oi.product_id', '!=', $product->id)
                ->groupBy('oi.product_id')
                ->orderByRaw('SUM(oi.quantity) DESC')
                ->limit(10)
                ->pluck('oi.product_id') : collect();

            return $manual->merge($together)->merge($sameCategory)->unique()->values()->all();
        });

        if (!$ids) {
            return collect();
        }
        $candidates = Product::active()->with(static::CARD_WITH)->whereIn('id', $ids)->get()->keyBy('id');

        return collect($ids)->map(fn ($id) => $candidates->get($id))->filter()
            ->filter(fn (Product $p) => !$p->isVariable() && !$p->isDigital() && $p->available_stock > 0)
            ->take($limit)->values();
    }
}
