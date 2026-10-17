<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Support\Facades\Response;

class SitemapController extends Controller
{
    public function index()
    {
        if (setting('sitemap_enabled', '1') !== '1') {
            abort(404);
        }

        $urls = [];

        $urls[] = ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'];
        $urls[] = ['loc' => route('shop.index'), 'changefreq' => 'daily', 'priority' => '0.9'];
        $urls[] = ['loc' => route('blog.index'), 'changefreq' => 'daily', 'priority' => '0.7'];

        foreach (Category::active()->where('noindex', false)->get() as $category) {
            $urls[] = [
                'loc' => route('shop.category', $category->slug),
                'lastmod' => $category->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8',
            ];
        }

        foreach (Brand::where('is_active', true)->whereHas('products', fn ($q) => $q->active())->get() as $brand) {
            $urls[] = [
                'loc' => route('shop.brand', $brand),
                'lastmod' => $brand->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7',
            ];
        }

        // Product::active() = is_active AND approval_status=approved — without the
        // latter check, a vendor's still-pending/rejected product (which defaults to
        // is_active=true) would get listed here and then 404 when Google crawls it,
        // since ProductController::show() blocks non-approved products.
        Product::active()->where('noindex', false)->chunk(200, function ($products) use (&$urls) {
            foreach ($products as $product) {
                $urls[] = [
                    'loc' => $product->canonical_url ?: route('products.show', $product),
                    'lastmod' => $product->updated_at->toAtomString(),
                    'changefreq' => $product->sitemap_changefreq ?: 'weekly',
                    'priority' => number_format((float) ($product->sitemap_priority ?? 0.5), 1),
                ];
            }
        });

        BlogPost::published()->chunk(200, function ($posts) use (&$urls) {
            foreach ($posts as $post) {
                $urls[] = [
                    'loc' => route('blog.show', $post),
                    'lastmod' => $post->updated_at->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                ];
            }
        });

        foreach (Page::where('is_active', true)->get() as $page) {
            $urls[] = [
                'loc' => $page->canonical_url ?: route('pages.show', $page),
                'lastmod' => $page->updated_at->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.5',
            ];
        }

        $xml = view('sitemap', compact('urls'))->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/xml']);
    }

    /**
     * A closure route can't be included in `php artisan route:cache` (Laravel refuses to
     * serialize it), so this lives here instead — same body as before, just moved.
     */
    public function robots()
    {
        // The "Enable Sitemap" admin toggle only controls sitemap.xml itself — it must
        // never also flip into a site-wide "Disallow: /", or an admin turning off the
        // sitemap feature for an unrelated reason would silently deindex the whole store.
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /cart',
            'Disallow: /checkout',
            'Disallow: /account',
            // Faceted navigation: every filter/sort/search combination on the shop is its own
            // URL (?sort=, ?brand=…&min_price=…, ?search=…) — thousands of near-duplicates that
            // all canonicalize back to /shop. Search Console showed ~1,900 such "not indexed"
            // URLs eating the crawl budget that should go to real product/category pages.
            // Plain /shop, /shop/category/… (incl. ?page=), /brand/…, products and blog stay open.
            'Disallow: /shop?',
            'Disallow: /*?*sort=',
            'Disallow: /*?*search=',
            'Disallow: /*?*min_price=',
            'Disallow: /*?*max_price=',
            'Disallow: /*?*in_stock=',
            'Disallow: /*?*on_sale=',
            'Disallow: /*?*featured=',
            'Disallow: /*?*tag=',
            // …except the WooCommerce leftovers spam backlinks point at (?add-to-cart=, ?filter_…):
            // they 301 to the clean URL (LegacyUrlRedirects), and Google has to be allowed to
            // fetch them to see that and drop them. These longer rules win over "/shop?".
            'Allow: /*?*add-to-cart=',
            'Allow: /*?*filter_',
            'Allow: /*?*orderby=',
            'Allow: /*?*product_cat=',
            // Utility / per-customer pages with nothing worth ranking. Root-level ones are
            // anchored with $ (exact page) or a trailing / (only what's under it) — landing
            // pages also live at the root (/{slug}), and a bare prefix like "/login" would
            // silently block a future landing page slugged "login-offer" too.
            'Disallow: /search/',
            'Disallow: /home-sections/',
            'Disallow: /auth/',
            'Disallow: /reset-password/',
            'Disallow: /verify-email/',
            'Disallow: /orders/',
            'Disallow: /track-order/',
            'Disallow: /email/',
            'Disallow: /newsletter/',
            ...array_map(fn ($page) => "Disallow: /{$page}$", [
                'login', 'register', 'forgot-password', 'verify-email', 'two-factor-challenge',
                'confirm-password', 'profile', 'orders', 'wishlist', 'track-order',
            ]),
        ];

        if (setting('sitemap_enabled', '1') === '1') {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . route('sitemap');
        }

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }

    public function adsTxt()
    {
        // AdSense's own "pub-<digits>" ID with the "ca-" prefix stripped — that prefix is
        // just AdSense's client-side script convention, ads.txt wants the bare publisher ID.
        $publisherId = trim((string) setting('adsense_publisher_id', ''));
        $publisherId = str_starts_with($publisherId, 'ca-') ? substr($publisherId, 3) : $publisherId;

        if ($publisherId === '') {
            abort(404);
        }

        $lines = ["google.com, {$publisherId}, DIRECT, f08c47fec0942fa0"];

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
