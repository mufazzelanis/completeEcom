<?php

namespace App\Support;

use App\Models\BlogPost;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\ProductView;
use App\Services\ActivityLogger;
use App\Services\Facebook\ConversionsApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Side effects of viewing a page (view counters, activity log, recently-viewed history,
 * Meta CAPI ViewContent) — recorded only when a person actually sees the page.
 *
 * Turbo prefetches a link's page as soon as the mouse rests on it (X-Sec-Purpose: prefetch),
 * and the visitor may never click. For such a request record() returns a short-lived token
 * instead; the page posts it back (partials/page-view-beacon) the moment it's displayed, and
 * consume() runs the same side effects then — with the original URL and referrer.
 */
class PageView
{
    private const TOKEN_TTL = 900; // seconds a prefetched page can wait to be shown

    public static function isPrefetch(?Request $request = null): bool
    {
        $request ??= request();
        $purpose = strtolower((string) ($request->header('X-Sec-Purpose') ?? $request->header('Sec-Purpose') ?? $request->header('Purpose')));

        return str_contains($purpose, 'prefetch');
    }

    /** Records the view now, or returns the beacon token when this render is only a prefetch. */
    public static function record(string $type, int $id, array $data = []): ?string
    {
        $request = request();
        $data += ['url' => $request->fullUrl(), 'ref' => $request->header('referer')];

        if (!self::isPrefetch($request)) {
            self::run($type, $id, $data, live: true);

            return null;
        }

        return Crypt::encryptString(json_encode(['t' => $type, 'id' => $id, 'd' => $data, 'at' => time()]));
    }

    /** Beacon side: validates the token (once only — a Back-button re-render posts it again). */
    public static function consume(string $token): bool
    {
        try {
            $p = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return false;
        }
        if (!is_array($p) || time() - (int) ($p['at'] ?? 0) > self::TOKEN_TTL) {
            return false;
        }
        if (!Cache::add('pv:' . md5($token), 1, self::TOKEN_TTL)) {
            return false;
        }

        self::run((string) $p['t'], (int) $p['id'], (array) $p['d'], live: false);

        return true;
    }

    private static function run(string $type, int $id, array $data, bool $live): void
    {
        $url = $live ? null : ($data['url'] ?? null);
        $ref = $data['ref'] ?? null;

        match ($type) {
            'product' => self::product($id, $data, $url, $ref),
            // toBase(): a view isn't an edit — leave updated_at (the sitemap's <lastmod>) alone
            'blog'    => BlogPost::whereKey($id)->toBase()->increment('views'),
            'landing' => LandingPage::whereKey($id)->toBase()->increment('views_count'),
            default   => null,
        };
    }

    private static function product(int $id, array $data, ?string $url, ?string $ref): void
    {
        $product = Product::with('category', 'activeFlashSaleProduct')->find($id);
        if (!$product) {
            return;
        }

        // not $product->increment(): that also bumps updated_at, so every visit (bots included)
        // made the sitemap claim ~2,300 products changed today and Google learns to ignore <lastmod>
        Product::whereKey($product->id)->toBase()->increment('views');
        $userId = auth()->id();
        $sessionId = session()->getId();

        // after the response: the visitor never waits on these writes / the CAPI dispatch
        after_response(function () use ($product, $data, $url, $ref, $userId, $sessionId) {
            ActivityLogger::log('product.view', "Viewed product: {$product->name}", $product, [], $url, $ref);
            // Per-visitor history behind the homepage personalized section
            ProductView::record($product->id, $userId, $sessionId);

            if (!empty($data['event'])) {
                // eventID shared with the page's fbq('track', 'ViewContent') so Meta dedupes the pair
                $user = auth()->user();
                ConversionsApi::track(
                    eventName: 'ViewContent',
                    eventId: (string) $data['event'],
                    customData: [
                        'content_ids'      => [(string) $product->id], // raw product ID, same as the pixel
                        'content_type'     => 'product',
                        'content_name'     => $product->name,
                        'content_category' => $product->category->name ?? null,
                        'value'            => (float) $product->final_price,
                        'currency'         => setting('currency_code', 'BDT'),
                    ],
                    rawUserFields: $user ? ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone] : [],
                    eventSourceUrl: $url,
                );
            }
        });
    }
}
