<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\Category;
use App\Models\LiveVisitor;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Live visitors. The storefront page posts a tiny heartbeat (ping) every ~20 s while it's open
 * and visible, and on every page change; that keeps one row per browser up to date with the
 * page it's on. Bots never appear: the heartbeat comes from the page's JavaScript (scrapers
 * don't run it) and requests that look automated are ignored anyway.
 */
class LiveVisitorController extends Controller
{
    private const PATH_LENGTH = 25;

    public function ping(Request $request)
    {
        $data = $request->validate([
            'vk' => ['required', 'string', 'regex:/^[A-Za-z0-9-]{16,40}$/'],
            'url' => 'required|string|max:1000',
            'title' => 'nullable|string|max:300',
            'ref' => 'nullable|string|max:1000',
        ]);
        if (ActivityLog::automationSignal($request)) {
            return response()->noContent();
        }

        $url = $data['url'];
        $parts = parse_url($url);
        if (!$parts || strcasecmp($parts['host'] ?? '', $request->getHost()) !== 0) {
            return response()->noContent(); // only this site's own pages
        }
        [$type, $product, $label] = $this->classify($parts['path'] ?? '/', $parts['query'] ?? '');
        $title = Str::limit(trim(preg_replace('/\s*[|–-]\s*' . preg_quote(setting('site_name', ''), '/') . '\s*$/u', '', strip_tags($data['title'] ?? ''))), 250, '…') ?: $label;
        $url = Str::limit($url, 500, '');

        $visitor = LiveVisitor::firstOrNew(['visitor_key' => $data['vk']]);
        $now = now();
        if (!$visitor->exists) {
            [$device, $browser, $platform] = ActivityLog::parseUserAgent((string) $request->userAgent());
            $visitor->fill([
                'device' => $device, 'browser' => $browser, 'platform' => $platform,
                'source' => ActivityLog::channelFromReferrer($data['ref'] ?? null),
                'first_seen_at' => $now,
            ]);
        }
        // a visitor coming back after a break starts a new session (journey + time on site)
        if ($visitor->exists && $visitor->last_seen_at && $visitor->last_seen_at->lt($now->copy()->subMinutes(30))) {
            $visitor->first_seen_at = $now;
            $visitor->path = [];
            $visitor->source = ActivityLog::channelFromReferrer($data['ref'] ?? null);
        }
        if ($visitor->url !== $url) {
            $path = $visitor->path ?? [];
            $path[] = ['t' => $type, 'title' => $title, 'url' => $url, 'at' => $now->toIso8601String()];
            $visitor->path = array_slice($path, -self::PATH_LENGTH);
            $visitor->page_started_at = $now;
        }

        $cart = auth()->check() ? Cart::where('user_id', auth()->id()) : Cart::where('session_id', session()->getId());
        $cartItems = $cart->with('product:id,price,sale_price', 'combination')->get();

        $visitor->fill([
            'user_id' => auth()->id(),
            'ip_address' => $request->ip(),
            'page_type' => $type,
            'url' => $url,
            'title' => $title,
            'product_id' => $product?->id,
            'cart_count' => (int) $cartItems->sum('quantity'),
            'cart_total' => (float) $cartItems->sum(fn ($i) => $i->unit_price * $i->quantity),
            'pings' => ($visitor->pings ?? 0) + 1,
            'last_seen_at' => $now,
        ])->save();

        $this->lookupGeo((string) $request->ip());

        return response()->noContent();
    }

    /** [page type, product (on a product page), readable label] from the URL. */
    private function classify(string $path, string $query): array
    {
        $path = '/' . trim($path, '/');
        parse_str($query, $q);

        return match (true) {
            $path === '/' => ['home', null, 'Homepage'],
            (bool) preg_match('#^/products/([^/]+)$#', $path, $m) => ['product', Product::where('slug', $m[1])->first(['id', 'name']), 'Product'],
            (bool) preg_match('#^/shop/category/([^/]+)$#', $path, $m) => ['category', null, 'Category: ' . (Category::where('slug', $m[1])->value('name') ?? $m[1])],
            (bool) preg_match('#^/brand/([^/]+)$#', $path, $m) => ['brand', null, 'Brand: ' . (Brand::where('slug', $m[1])->value('name') ?? $m[1])],
            $path === '/shop' && !empty($q['search']) => ['search', null, 'Search: “' . Str::limit((string) $q['search'], 60) . '”'],
            in_array($path, ['/shop', '/brands', '/categories'], true) => ['shop', null, 'Shop'],
            $path === '/cart' => ['cart', null, 'Cart'],
            str_starts_with($path, '/checkout') => ['checkout', null, 'Checkout'],
            str_starts_with($path, '/blog') => ['blog', null, 'Blog'],
            (bool) preg_match('#^/(orders|account|profile|wishlist|track-order|login|register)#', $path) => ['account', null, 'Account'],
            (bool) preg_match('#^/[^/]+$#', $path) => ['landing', null, 'Landing page'],
            default => ['other', null, 'Page'],
        };
    }

    /** City/country for an IP, looked up once (after the response) and cached for a month. */
    private function lookupGeo(string $ip): void
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) || Cache::has("ip_geo:{$ip}")) {
            return;
        }
        Cache::put("ip_geo:{$ip}", null, now()->addMinutes(10)); // don't ask again while this one is pending
        after_response(function () use ($ip) {
            $r = Http::timeout(5)->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,city,country,countryCode']);
            if ($r->ok() && ($r->json('status') === 'success')) {
                Cache::put("ip_geo:{$ip}", ['city' => $r->json('city'), 'country' => $r->json('country'), 'cc' => $r->json('countryCode')], now()->addMonth());
                Cache::put("ip_location:{$ip}", collect([$r->json('city'), $r->json('country')])->filter()->implode(', '), now()->addMonth());
            }
        });
    }
}
