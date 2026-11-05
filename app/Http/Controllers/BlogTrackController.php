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

    private const MAX_CLICKS = 30;

    public function __invoke(Request $request)
    {
        // a bad beacon gets a quiet 204 — never Laravel's redirect-back, which fetch() would follow
        // straight into the article and record a second, phantom view
        $v = validator($request->all(), [
            'r'   => ['required', 'string', 'max:36', 'regex:/^[A-Za-z0-9-]+$/'],
            'k'   => ['required', 'string', 'size:32'],
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
        if ($v->fails()) {
            return response()->noContent();
        }
        $d = $v->validated();

        // Only a read key this server issued with the page (BlogRead::sign) — no minting readers by
        // script. automationSignal(): bot User-Agent, or headers every real browser sends missing (the
        // same test Activity Log uses). Staff checking their own posts aren't readers either.
        if (!hash_equals(BlogRead::sign($d['r'], (int) $d['p']), $d['k'])
            || ActivityLog::automationSignal($request) !== null
            || auth()->user()?->canAccessAdmin()
            || !$this->isLivePost((int) $d['p'])) {
            return response()->noContent();
        }

        // usually the row the server wrote when it served the page (BlogRead::begin); created here
        // only for a page that was prefetched first and then shown
        $ua = mb_substr((string) $request->userAgent(), 0, 500);
        $read = BlogRead::firstOrCreate(['read_key' => $d['r']], [
            'blog_post_id'  => (int) $d['p'],
            'visitor_key'   => $d['v'],
            'user_id'       => auth()->id(),
            'ip'            => $request->ip(),
            'user_agent'    => $ua,
            'read_date'     => now(setting('timezone', 'Asia/Dhaka'))->toDateString(),
            'source'        => self::source($d['ref'] ?? null, $d['q'] ?? null, $request->getHost()),
            'referrer_host' => self::host($d['ref'] ?? null),
            'device'        => self::device($ua),
        ]);
        if ($read->blog_post_id !== (int) $d['p'] || $read->bot_name) {
            return response()->noContent(); // a declared bot never becomes a reader, whatever it sends
        }

        // the tracker only ever reports after a real touch / mouse / key event: this is a person
        if (!$read->engaged_at) {
            BlogRead::whereKey($read->id)->toBase()->update([
                'engaged_at'  => now(),
                'visitor_key' => $d['v'], // the browser's own id replaces the server's IP-based stand-in
                'user_id'     => $read->user_id ?? auth()->id(),
            ]);
        }

        // never more reading time than has passed since the page was served (+ the up-to-30 s the
        // tracker credits for the opening, + network slack): a forged "t=3600" can't inflate it
        $elapsed = $read->created_at ? (int) $read->created_at->diffInSeconds(now(), true) : 0;
        $seconds = min((int) ($d['t'] ?? 0), self::MAX_SECONDS, $elapsed + 40);
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

        // a real reader clicks a handful of times; past MAX_CLICKS it's someone hammering the button
        if (($d['e'] ?? null) === 'click' && !empty($d['pid']) && $read->product_clicks < self::MAX_CLICKS
            && Product::whereKey($d['pid'])->exists()) {
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

    public static function host(?string $url): ?string
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

    public static function device(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/iPad|Tablet|PlayBook|Silk|(Android(?!.*Mobile))/i', $ua) => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod|Android|Opera Mini|IEMobile/i', $ua) => 'mobile',
            default => 'desktop',
        };
    }
}
