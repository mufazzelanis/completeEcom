<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\BlogPost;
use App\Models\BlogProductClick;
use App\Models\BlogRead;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Receives the article tracker's beacons (blog/partials/tracker): a "start" when the post is shown,
 * periodic "ping"s with the reader's active time + deepest scroll, and a "click" for every product
 * interaction. One blog_reads row per page view, keyed by the browser-made read_key; values only
 * ever grow (GREATEST), so out-of-order or repeated beacons are harmless.
 */
class BlogTrackController extends Controller
{
    private const MAX_SECONDS = 3600; // a tab left open all afternoon isn't an hour of reading

    public function __invoke(Request $request)
    {
        $d = $request->validate([
            'r'   => ['required', 'string', 'max:36', 'regex:/^[A-Za-z0-9-]+$/'],
            'v'   => ['required', 'string', 'max:36', 'regex:/^[A-Za-z0-9-]+$/'],
            'p'   => ['required', 'integer'],
            't'   => ['nullable', 'integer', 'min:0'],
            's'   => ['nullable', 'integer', 'min:0', 'max:100'],
            'e'   => ['nullable', 'in:start,ping,click'],
            'pid' => ['nullable', 'integer'],
            'pl'  => ['nullable', 'in:' . implode(',', array_keys(BlogProductClick::PLACEMENTS))],
            'a'   => ['nullable', 'in:' . implode(',', BlogProductClick::ACTIONS)],
            'ref' => ['nullable', 'string', 'max:1000'],
            'q'   => ['nullable', 'string', 'max:1000'],
        ]);

        if (ActivityLog::isBotUserAgent((string) $request->userAgent()) || !$this->isLivePost((int) $d['p'])) {
            return response()->noContent();
        }

        $read = BlogRead::firstOrCreate(['read_key' => $d['r']], [
            'blog_post_id'  => (int) $d['p'],
            'visitor_key'   => $d['v'],
            'user_id'       => auth()->id(),
            'read_date'     => now(setting('timezone', 'Asia/Dhaka'))->toDateString(),
            'source'        => self::source($d['ref'] ?? null, $d['q'] ?? null, $request->getHost()),
            'referrer_host' => self::host($d['ref'] ?? null),
            'device'        => self::device((string) $request->userAgent()),
        ]);
        if ($read->blog_post_id !== (int) $d['p']) {
            return response()->noContent();
        }

        $seconds = min((int) ($d['t'] ?? 0), self::MAX_SECONDS);
        $scroll = (int) ($d['s'] ?? 0);
        if ($seconds > $read->active_seconds || $scroll > $read->max_scroll) {
            BlogRead::whereKey($read->id)->update([
                'active_seconds' => DB::raw('GREATEST(active_seconds, ' . $seconds . ')'),
                'max_scroll'     => DB::raw('GREATEST(max_scroll, ' . $scroll . ')'),
                'updated_at'     => now(),
            ]);
        } elseif (($d['e'] ?? null) === 'ping') {
            // keeps "reading now" on the dashboard honest while someone sits on one paragraph
            BlogRead::whereKey($read->id)->toBase()->update(['updated_at' => now()]);
        }

        if (($d['e'] ?? null) === 'click' && !empty($d['pid']) && Product::whereKey($d['pid'])->exists()) {
            BlogProductClick::create([
                'blog_post_id' => $read->blog_post_id,
                'blog_read_id' => $read->id,
                'product_id'   => (int) $d['pid'],
                'placement'    => $d['pl'] ?? 'inline',
                'action'       => $d['a'] ?? 'open',
            ]);
            BlogRead::whereKey($read->id)->toBase()->increment('product_clicks');
        }

        return response()->noContent();
    }

    private function isLivePost(int $id): bool
    {
        return Cache::remember("blog-track:live:{$id}", 600, fn () => BlogPost::published()->whereKey($id)->exists());
    }

    private static function host(?string $url): ?string
    {
        $host = $url ? parse_url($url, PHP_URL_HOST) : null;

        return $host ? mb_substr(preg_replace('/^www\./i', '', strtolower($host)), 0, 120) : null;
    }

    /** Where the reader came from: the referrer, refined by the landing URL's utm_* / click ids. */
    public static function source(?string $referrer, ?string $query, string $ownHost): string
    {
        parse_str(ltrim((string) $query, '?'), $q);
        $medium = strtolower((string) ($q['utm_medium'] ?? ''));
        $utmSource = strtolower((string) ($q['utm_source'] ?? ''));

        if (isset($q['gclid']) || isset($q['wbraid']) || isset($q['gbraid']) || preg_match('/^(cpc|ppc|paid|ads?|display|cpm)/', $medium)) {
            return 'ads';
        }
        if (preg_match('/(email|newsletter|sms)/', $medium . ' ' . $utmSource)) {
            return 'email';
        }

        $host = self::host($referrer);
        if ($host && $host === preg_replace('/^www\./i', '', strtolower($ownHost))) {
            return 'internal';
        }
        if ($host && preg_match('/(^|\.)(google|bing|yahoo|duckduckgo|yandex|baidu|ecosia|brave|naver)\./', $host)) {
            return 'search';
        }
        if (isset($q['fbclid']) || preg_match('/(social|facebook|instagram|whatsapp|messenger)/', $medium . ' ' . $utmSource)
            || ($host && preg_match('/(facebook|fb\.|instagram|messenger|whatsapp|t\.co$|twitter|x\.com$|linkedin|pinterest|youtube|tiktok|reddit|threads)/', $host))) {
            return 'social';
        }

        return $host ? 'referral' : 'direct';
    }

    private static function device(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua) => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android|Opera Mini|IEMobile/i', $ua) => 'mobile',
            default => 'desktop',
        };
    }
}
