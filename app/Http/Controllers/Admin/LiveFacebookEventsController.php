<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FacebookConversionLog;
use App\Models\LandingPage;
use App\Models\LiveVisitor;
use App\Services\Facebook\ConversionsApi;
use App\Support\VisitorIntel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin → Live & Traffic → "Meta events": the Conversions API events the server sent, filterable
 * by event ID, event, main site / landing page, status and time. The page renders the filter bar
 * once and polls stream() for the results only, so typing in a filter is never interrupted by
 * the live refresh.
 */
class LiveFacebookEventsController extends Controller
{
    public const EVENTS = [
        'ViewContent' => ['Viewed product', '#0ea5e9', '👀'],
        'AddToCart' => ['Add to cart', '#f59e0b', '🛒'],
        'InitiateCheckout' => ['Checkout', '#8b5cf6', '💳'],
        'Purchase' => ['Purchase', '#16a34a', '✅'],
        'PageView' => ['Page view', '#64748b', '📄'],
    ];

    /** Rows shown here; everything else is one click away in the full log. */
    public const SHOW = 5;

    public const MAX = 100;

    public const RANGES = ['live' => 'Last hour', 'today' => 'Today', 'yesterday' => 'Yesterday', '7d' => '7 days', '30d' => '30 days', 'custom' => 'Custom'];

    public static function meta(string $event): array
    {
        return self::EVENTS[$event] ?? [$event, '#6366f1', '⚡'];
    }

    /** JSON for the live poll: the results block only. */
    public function stream(Request $request)
    {
        $data = self::data($request);

        return response()->json([
            'html' => view('admin.live-visitors._fb-results', $data)->render(),
            'top' => $data['topId'],
            'total' => $data['total'],
        ]);
    }

    /** The filters from the query string, cleaned up. */
    public static function filters(Request $request): array
    {
        $event = (string) $request->query('fb_event', '');
        $status = (string) $request->query('fb_status', '');
        $source = (string) $request->query('fb_source', '');
        $range = (string) $request->query('fb_range', 'today');

        return [
            'q' => mb_substr(trim((string) $request->query('fb_q', '')), 0, 80),
            'event' => preg_match('/^[A-Za-z]{2,40}$/', $event) ? $event : '',
            'status' => in_array($status, ['sent', 'failed'], true) ? $status : '',
            'source' => $source === 'main' || preg_match('/^lp:\d+$/', $source) ? $source : '',
            'range' => array_key_exists($range, self::RANGES) ? $range : 'today',
            'from' => self::dt($request->query('fb_from')),
            'to' => self::dt($request->query('fb_to')),
            'limit' => max(self::SHOW, min(self::MAX, (int) $request->query('fb_limit', self::SHOW))), // the latest few; "Show more" opens 10 at a time
            'after' => max(0, (int) $request->query('fb_after', 0)), // rows newer than this get the "new" glow
        ];
    }

    private static function dt($value): ?string
    {
        return is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value) ? $value : null;
    }

    /** [from, to) in UTC for the chosen time filter, plus a human label. */
    private static function window(array $f): array
    {
        $tz = setting('timezone', 'Asia/Dhaka');
        $now = now($tz);

        [$from, $to] = match ($f['range']) {
            'live' => [$now->copy()->subHour(), null],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->startOfDay()],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), null],
            '30d' => [$now->copy()->subDays(29)->startOfDay(), null],
            'custom' => [
                $f['from'] ? Carbon::createFromFormat('Y-m-d\TH:i', $f['from'], $tz) : $now->copy()->startOfDay(),
                $f['to'] ? Carbon::createFromFormat('Y-m-d\TH:i', $f['to'], $tz)->addMinute() : null,
            ],
            default => [$now->copy()->startOfDay(), null],
        };

        return [$from->utc(), $to?->utc()];
    }

    public static function data(Request $request): array
    {
        $f = self::filters($request);
        [$from, $to] = self::window($f);

        // everything but the event filter — the event cards show the breakdown and act as that filter
        $base = FacebookConversionLog::query()
            ->where('sent_at', '>=', $from)
            ->when($to, fn ($q) => $q->where('sent_at', '<', $to))
            ->when($f['status'], fn ($q) => $q->where('status', $f['status']))
            ->when($f['source'] === 'main', fn ($q) => $q->whereNull('landing_page_id'))
            ->when(str_starts_with($f['source'], 'lp:'), fn ($q) => $q->where('landing_page_id', (int) substr($f['source'], 3)))
            // one box: part of an event ID, or a visitor's IP (clicking an IP fills it in)
            ->when($f['q'] !== '', fn ($q) => filter_var($f['q'], FILTER_VALIDATE_IP)
                ? $q->where('ip_address', $f['q'])
                : $q->where('event_id', 'like', '%' . addcslashes($f['q'], '%_\\') . '%'));

        $counts = (clone $base)->selectRaw('event_name, status, COUNT(*) AS c, SUM(value) AS v')->groupBy('event_name', 'status')->get();
        $byEvent = [];
        foreach ($counts as $row) {
            $byEvent[$row->event_name]['sent'] = ($byEvent[$row->event_name]['sent'] ?? 0) + ($row->status === 'sent' ? (int) $row->c : 0);
            $byEvent[$row->event_name]['failed'] = ($byEvent[$row->event_name]['failed'] ?? 0) + ($row->status === 'failed' ? (int) $row->c : 0);
            $byEvent[$row->event_name]['value'] = ($byEvent[$row->event_name]['value'] ?? 0) + (float) $row->v;
        }
        $names = collect(array_keys(self::EVENTS))->reject(fn ($e) => $e === 'PageView' && empty($byEvent['PageView']))
            ->merge(array_keys($byEvent))->unique()->values()->all();

        $list = (clone $base)->when($f['event'], fn ($q) => $q->where('event_name', $f['event']));
        $total = (clone $list)->count();
        $rows = $list->with(['landingPage:id,title,slug', 'product:id,name,slug,image'])
            ->orderByDesc('sent_at')->orderByDesc('id')->take($f['limit'])->get();

        return [
            'f' => $f,
            'rows' => $rows,
            'match' => self::matchSummary(clone $list),
            'visitors' => self::visitors($rows, $from, $to),
            'total' => $total,
            'byEvent' => $byEvent,
            'names' => $names,
            'all' => ['sent' => (int) $counts->where('status', 'sent')->sum('c'), 'failed' => (int) $counts->where('status', 'failed')->sum('c')],
            'topId' => (int) ($rows->max('id') ?? 0),
            'isLive' => in_array($f['range'], ['live', 'today', '7d', '30d'], true) || ($f['range'] === 'custom' && !$f['to']),
        ];
    }

    /**
     * "How well can Meta recognise these customers?" for the filtered events: the average match
     * score and, per key, the share of events that carried it. One grouped query — the distinct
     * key combinations are few — over events logged since key tracking began.
     */
    private static function matchSummary($query): ?array
    {
        $combos = $query->whereNotNull('match_keys')->groupBy('match_keys')
            ->selectRaw('match_keys, COUNT(*) AS c')->pluck('c', 'match_keys');
        $n = (int) $combos->sum();
        if (!$n) {
            return null;
        }

        $score = 0.0; $contact = 0;
        $has = array_fill_keys(array_keys(FacebookConversionLog::MATCH_KEYS), 0);
        foreach ($combos as $keys => $c) {
            $list = array_filter(explode(',', $keys));
            $score += FacebookConversionLog::scoreFor($list) * $c;
            $contact += array_intersect(['em', 'ph'], $list) ? $c : 0; // email or phone
            foreach ($list as $k) {
                if (isset($has[$k])) {
                    $has[$k] += $c;
                }
            }
        }
        $avg = round($score / $n, 1);

        return [
            'n' => $n,
            'score' => $avg,
            'grade' => FacebookConversionLog::grade($avg),
            // the ones worth watching; IP / browser are always there
            'cover' => collect(['fbp', 'external_id', 'fbc', 'em', 'ph', 'country'])
                ->mapWithKeys(fn ($k) => [$k => (int) round($has[$k] / $n * 100)])->all(),
            'contact' => (int) round($contact / $n * 100),
        ];
    }

    /**
     * The Visitor column, per IP on screen: network ("BRACNet", "☁️ Google"), city/country, how
     * many Meta events that IP fired in this time window, and whether it had visited the site
     * before the window (Returning) or not (New). Network/location come from the shared IP cache
     * (App\Support\VisitorIntel); IPs not looked up yet are resolved right after the response.
     */
    private static function visitors($rows, $from, $to): array
    {
        $ips = $rows->pluck('ip_address')->filter()->unique()->values();
        if ($ips->isEmpty()) {
            return [];
        }

        $intel = VisitorIntel::ips($ips);
        after_response(fn () => VisitorIntel::resolveQueued(1));

        $events = FacebookConversionLog::whereIn('ip_address', $ips)->where('sent_at', '>=', $from)
            ->when($to, fn ($q) => $q->where('sent_at', '<', $to))
            ->groupBy('ip_address')->selectRaw('ip_address, COUNT(*) AS c')->pluck('c', 'ip_address');
        // seen on the site before this window → returning (activity log has an (ip_address, created_at) index)
        $before = DB::table('activity_logs')->whereIn('ip_address', $ips)->where('created_at', '<', $from)
            ->distinct()->pluck('ip_address')->flip();

        return $ips->mapWithKeys(function ($ip) use ($intel, $events, $before) {
            $info = $intel[$ip] ?? null;

            return [$ip => [
                'count' => (int) ($events[$ip] ?? 0),
                'returning' => isset($before[$ip]),
                'network' => VisitorIntel::network($info),
                'hosting' => !empty($info['hosting']),
                'location' => VisitorIntel::location($info),
                'flag' => LiveVisitor::flag($info['cc'] ?? null),
            ]];
        })->all();
    }

    /** What the filter bar needs once, on page load. */
    public static function shell(Request $request): array
    {
        $lpIds = FacebookConversionLog::whereNotNull('landing_page_id')->distinct()->pluck('landing_page_id');

        return self::data($request) + [
            'enabled' => ConversionsApi::isEnabled() || LandingPage::whereNotNull('fb_capi_access_token')->where('fb_capi_access_token', '!=', '')->exists(),
            'landings' => LandingPage::whereIn('id', $lpIds)->orWhere(fn ($q) => $q->whereNotNull('fb_pixel_id')->where('fb_pixel_id', '!=', ''))
                ->orderBy('title')->get(['id', 'title']),
        ];
    }
}
