<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
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
        ];

        if (setting('sitemap_enabled', '1') === '1') {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . route('sitemap');
        }

        return response(implode("\n", $lines), 200)->header('Content-Type', 'text/plain');
    }
}
