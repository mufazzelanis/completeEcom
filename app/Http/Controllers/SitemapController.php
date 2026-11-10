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
        // absolute URL for an image path (Google Images reads <image:loc> from the sitemap)
        $img = fn (?string $path) => \App\Support\Watermark::publicUrl($path); // the address really served, never a redirect

        $urls[] = ['loc' => route('home'), 'changefreq' => 'daily', 'priority' => '1.0'];
        $urls[] = ['loc' => route('shop.index'), 'changefreq' => 'daily', 'priority' => '0.9'];
        $urls[] = ['loc' => route('blog.index'), 'changefreq' => 'daily', 'priority' => '0.7'];

        // Only sales that are on (or teased) right now — ended ones are noindex on the page itself
        $flashSales = \App\Models\FlashSale::live()->get()
            ->merge(\App\Models\FlashSale::upcoming()->where('show_upcoming', true)->get());
        foreach ($flashSales as $sale) {
            $urls[] = ['loc' => $sale->url, 'lastmod' => $sale->updated_at->toAtomString(), 'changefreq' => 'hourly', 'priority' => '0.8'];
        }

        foreach (Category::active()->where('noindex', false)->get() as $category) {
            $urls[] = [
                'loc' => route('shop.category', $category->slug),
                'lastmod' => $category->updated_at->toAtomString(),
                'images' => array_filter([$img($category->image)]),
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
        Product::active()->where('noindex', false)->with('images:id,product_id,image,sort_order')->chunk(200, function ($products) use (&$urls, $img) {
            foreach ($products as $product) {
                $urls[] = [
                    'loc' => $product->canonical_url ?: route('products.show', $product),
                    'lastmod' => $product->updated_at->toAtomString(),
                    'changefreq' => $product->sitemap_changefreq ?: 'weekly',
                    'priority' => number_format((float) ($product->sitemap_priority ?? 0.5), 1),
                    // main photo + up to 4 gallery photos → product images can rank in Google Images
                    'images' => array_values(array_unique(array_filter([$img($product->image), ...$product->images->sortBy('sort_order')->take(4)->map(fn ($i) => $img($i->image))->all()]))),
                ];
            }
        });

        BlogPost::published()->chunk(200, function ($posts) use (&$urls, $img) {
            foreach ($posts as $post) {
                $urls[] = [
                    'loc' => route('blog.show', $post),
                    'lastmod' => $post->updated_at->toAtomString(),
                    'changefreq' => 'monthly',
                    'priority' => '0.6',
                    'images' => array_filter([$img($post->image)]),
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
        // Crawlers that bring this store no shoppers but fetch thousands of product pages a day
        // (Admin → Activity Log: QlyzeBot ~1.6k, TokenFoundryBot ~2.5k, PetalBot ~450 per day).
        // All of these honour robots.txt. Search engines (Googlebot, Bingbot), Facebook's link
        // previews and the AI assistants that can send visitors (ChatGPT, Perplexity…) stay allowed.
        $blockedBots = ['QlyzeBot', 'TokenFoundryBot', 'PetalBot', 'Bytespider', 'DotBot', 'MJ12bot', 'BLEXBot', 'DataForSeoBot', 'SemrushBot', 'AhrefsBot'];

        $lines = [
            ...array_map(fn ($bot) => "User-agent: {$bot}", $blockedBots),
            'Disallow: /',
            '',
            'User-agent: *',
            'Disallow: /admin',
            // Cart, checkout, login, account, orders… are NOT blocked here on purpose: they send
            // X-Robots-Tag: noindex (SecurityHeaders), and Google can only obey that if it may fetch
            // the page. Blocked, they showed up as "Indexed, though blocked by robots.txt".
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
            'Disallow: /r/', // per-order review links (also noindex)
            // (/home-sections/… is deliberately NOT disallowed: homepage sections below the fold
            // load their products from it, and Googlebot must be able to fetch it to see them.
            // The JSON itself carries X-Robots-Tag: noindex.)
            'Disallow: /auth/',
            // one-click unsubscribe links act on a plain GET: a crawler must never follow one
            'Disallow: /email/',
            'Disallow: /newsletter/',
        ];

        if (setting('sitemap_enabled', '1') === '1') {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . route('sitemap');
        }

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }

    /** IndexNow ownership proof (App\Services\IndexNow) — the key as plain text. */
    public function indexNowKey()
    {
        return response(\App\Services\IndexNow::key(), 200)->header('Content-Type', 'text/plain; charset=utf-8');
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
