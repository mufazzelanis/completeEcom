<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cleans up URLs this site never had but that search engines and SEO tools keep requesting —
 * left over from a WooCommerce/WordPress shop that used this domain before, or invented by
 * spam backlink networks (Moz showed "?add-to-cart=1312", "?filter_brand=hp" as "top pages"):
 *
 *   - WooCommerce/WordPress query parameters (?add-to-cart=, ?filter_color=, ?orderby=, ?p=…)
 *     → 301 to the same page without them, so every variant consolidates into one indexed URL
 *     instead of rendering a 200 duplicate;
 *   - /product/{slug}, /product-category/…/{slug}, /product-brand/{slug} → 301 to the matching
 *     product/category/brand here when one exists;
 *   - every other WordPress-only path (feeds, wp-json, my-account, tags, /shop/page/N…) → 410
 *     Gone, which tells Google to drop the URL faster than a 404 would.
 *
 * Only GET/HEAD requests are touched; this site's own parameters (search, q, category, sort,
 * page, filter, utm_*, …) are never stripped.
 */
class LegacyUrlRedirects
{
    /** WooCommerce/WordPress parameters this site has never used. */
    private const LEGACY_PARAMS = [
        'add-to-cart', 'add_to_wishlist', 'remove_item', 'removed_item', 'undo_item', 'wc-ajax',
        'orderby', 'product_cat', 'product_tag', 'product_brand', 'post_type', 'p', 'page_id',
        's', 'replytocom', 'rating_filter', 'min_rating', 'paged', 'amp',
    ];

    private const LEGACY_PARAM_PREFIXES = ['filter_', 'query_type_', 'attribute_', 'pa_'];

    /** WordPress-only paths with no equivalent here → 410 Gone. */
    private const GONE_PATTERNS = [
        '#^feed(?:/|$)#', '#^comments/feed#', '#^(?:product-category|product-tag|tag|category|author)/.+/feed$#',
        '#^wp-json(?:/|$)#', '#^xmlrpc\.php$#', '#^wp-(?:content|includes)/#', '#^my-account(?:/|$)#',
        '#^product-tag/#', '#^tag/#', '#^author/#', '#^category/#', '#^shop/page/\d+$#', '#^page/\d+$#',
        '#^\d{4}/\d{2}(?:/|$)#',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->isMethod('GET') && !$request->isMethod('HEAD')) {
            return $next($request);
        }

        // 1. legacy query parameters → same URL without them
        $query = $request->query();
        $clean = array_filter($query, fn ($key) => !$this->isLegacyParam((string) $key), ARRAY_FILTER_USE_KEY);
        if (count($clean) !== count($query)) {
            $path = '/' . ltrim($request->path(), '/');

            return redirect()->to(url($path) . ($clean ? '?' . http_build_query($clean) : ''), 301);
        }

        // 2. legacy WooCommerce paths that have a real counterpart here
        $path = trim($request->path(), '/');
        if ($target = $this->counterpart($path)) {
            return redirect()->to($target, 301);
        }

        // 3. WordPress-only paths → gone
        foreach (self::GONE_PATTERNS as $pattern) {
            if (preg_match($pattern, $path)) {
                abort(410);
            }
        }

        return $next($request);
    }

    private function isLegacyParam(string $key): bool
    {
        if (in_array($key, self::LEGACY_PARAMS, true)) {
            return true;
        }
        foreach (self::LEGACY_PARAM_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /** Matching product/category/brand URL for an old WooCommerce path, or null. Unknown ones are gone. */
    private function counterpart(string $path): ?string
    {
        if (!preg_match('#^(product|product-category|product-brand|brand)/(.+)$#', $path, $m)) {
            return null;
        }
        $slug = basename($m[2]);

        $url = match ($m[1]) {
            'product'          => ($p = Product::active()->where('slug', $slug)->first()) ? route('products.show', $p) : null,
            'product-category' => ($c = Category::where('slug', $slug)->where('is_active', true)->first()) ? route('shop.category', $c) : null,
            // /brand/{slug} is a real route here — only its nested WooCommerce form is legacy
            'product-brand', 'brand' => str_contains($m[2], '/') || $m[1] === 'product-brand'
                ? (($b = Brand::where('slug', $slug)->first()) ? route('shop.brand', $b) : null)
                : false,
        };

        if ($url === false) {
            return null; // a real route of this site — not ours to handle
        }
        if ($url === null) {
            abort(410);
        }

        return $url;
    }
}
