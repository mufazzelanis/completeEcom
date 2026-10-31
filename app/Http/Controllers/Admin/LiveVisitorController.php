<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\CheckoutLead;
use App\Models\LiveVisitor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\VisitorSession;
use Illuminate\Http\Request;

/**
 * Admin → Live Visitors: who's on the storefront right now, on which page / product (with
 * picture and price), from where, and what's in their cart. The page refreshes its feed every
 * few seconds without reloading; the dashboard shows a compact summary of the same data.
 * Team members (anyone who can open the admin) are left out unless ?team=1.
 */
class LiveVisitorController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.live-visitors.index', $this->snapshot($request) + ['stats' => $this->analytics($request)]);
    }

    /** Date range presets → [label, from, to) in the store's time zone. */
    private function range(Request $request): array
    {
        $tz = setting('timezone', 'Asia/Dhaka');
        $today = now($tz)->startOfDay();
        $key = $request->get('range', 'today');
        [$label, $from, $to] = match ($key) {
            'yesterday' => ['Yesterday', $today->copy()->subDay(), $today->copy()],
            '7d' => ['Last 7 days', $today->copy()->subDays(6), $today->copy()->addDay()],
            '30d' => ['Last 30 days', $today->copy()->subDays(29), $today->copy()->addDay()],
            'month' => ['This month', $today->copy()->startOfMonth(), $today->copy()->addDay()],
            'custom' => [
                'Custom',
                \Illuminate\Support\Carbon::parse($request->get('from', $today->toDateString()), $tz)->startOfDay(),
                \Illuminate\Support\Carbon::parse($request->get('to', $today->toDateString()), $tz)->addDay()->startOfDay(),
            ],
            default => ['Today', $today->copy(), $today->copy()->addDay()],
        };
        if ($to->lte($from)) {
            $to = $from->copy()->addDay();
        }
        $key = in_array($key, ['yesterday', '7d', '30d', 'month', 'custom'], true) ? $key : 'today';

        return [$key, $label, $from, $to, $tz];
    }

    /**
     * Visits, funnel, sources, devices, peak hours, products viewed-but-not-bought and abandoned
     * checkouts for the chosen range (+ the same numbers for the period before, for ▲▼).
     * Visits come from visitor_sessions (one per browser visit, bots and the team left out);
     * orders and revenue from the orders themselves.
     */
    private function analytics(Request $request): array
    {
        [$key, $label, $from, $to, $tz] = $this->range($request);
        $team = $request->boolean('team');

        // cached as plain JSON (the cache refuses to unserialize objects), objects rebuilt below
        $json = \Illuminate\Support\Facades\Cache::remember('lv:stats:' . md5(json_encode([$key, $from, $to, $team])), 60, fn () => json_encode($this->computeStats($key, $label, $from, $to, $tz, $team)));
        $data = json_decode($json, true);
        $rows = fn ($list) => collect($list)->map(fn ($r) => (object) array_map(fn ($v) => is_array($v) ? (object) $v : $v, $r));
        foreach (['sources', 'devices', 'landings', 'notBuying', 'topViewed', 'leads'] as $k) {
            $data[$k] = $rows($data[$k]);
        }
        $data['from'] = $from;
        $data['to'] = $to;

        return $data;
    }

    private function computeStats(string $key, string $label, $from, $to, string $tz, bool $team): array
    {
        return (function () use ($key, $label, $from, $to, $tz, $team) {
            $offset = $from->format('P'); // e.g. +06:00 — fixed offsets work without MySQL tz tables
            $days = (int) round($from->diffInDays($to));
            $utc = fn ($c) => $c->copy()->utc();
            $sessions = fn ($a, $b) => VisitorSession::query()->whereBetween('started_at', [$utc($a), $utc($b)->subSecond()])->when(!$team, fn ($q) => $q->where('is_team', false));
            $orders = fn ($a, $b) => Order::query()->whereBetween('created_at', [$utc($a), $utc($b)->subSecond()])->whereNotIn('status', ['cancelled', 'refunded']);

            $sum = function ($a, $b) use ($sessions, $orders) {
                $s = $sessions($a, $b)->selectRaw('COUNT(*) sessions, COUNT(DISTINCT visitor_key) visitors,
                    SUM(reached_product) product, SUM(reached_cart) cart, SUM(reached_checkout) checkout, SUM(ordered) ord,
                    AVG(LEAST(TIMESTAMPDIFF(SECOND, started_at, last_seen_at), 7200)) avg_secs,
                    SUM(CASE WHEN pages <= 1 AND TIMESTAMPDIFF(SECOND, started_at, last_seen_at) < 30 THEN 1 ELSE 0 END) bounces')->first();
                $o = $orders($a, $b)->selectRaw('COUNT(*) n, COALESCE(SUM(total),0) revenue')->first();

                return [
                    'sessions' => (int) $s->sessions, 'visitors' => (int) $s->visitors,
                    'product' => (int) $s->product, 'cart' => (int) $s->cart, 'checkout' => (int) $s->checkout, 'ordered' => (int) $s->ord,
                    'avg_secs' => (int) $s->avg_secs, 'bounce' => $s->sessions ? $s->bounces / $s->sessions * 100 : 0,
                    'orders' => (int) $o->n, 'revenue' => (float) $o->revenue,
                    'conversion' => $s->sessions ? min(100, $o->n / $s->sessions * 100) : 0,
                ];
            };
            $now = $sum($from, $to);
            $span = $from->diffInSeconds($to);
            $prev = $sum($from->copy()->subSeconds($span), $from->copy());

            // visits & orders by hour of day (store time) — peak / quiet hours
            $hourly = array_fill(0, 24, ['s' => 0, 'o' => 0]);
            foreach ($sessions($from, $to)->selectRaw("HOUR(CONVERT_TZ(started_at, '+00:00', ?)) h, COUNT(*) c", [$offset])->groupBy('h')->get() as $r) {
                $hourly[(int) $r->h]['s'] = (int) $r->c;
            }
            foreach ($orders($from, $to)->selectRaw("HOUR(CONVERT_TZ(created_at, '+00:00', ?)) h, COUNT(*) c", [$offset])->groupBy('h')->get() as $r) {
                $hourly[(int) $r->h]['o'] = (int) $r->c;
            }
            $active = array_filter($hourly, fn ($h) => $h['s'] > 0);
            $peak = $active ? array_search(max(array_column($hourly, 's')), array_column($hourly, 's')) : null;
            $quiet = $active ? collect($hourly)->filter(fn ($h) => $h['s'] > 0)->sortBy('s')->keys()->first() : null;

            // by day (for ranges longer than two days)
            $daily = [];
            if ($days > 2) {
                $s = $sessions($from, $to)->selectRaw("DATE(CONVERT_TZ(started_at, '+00:00', ?)) d, COUNT(*) c", [$offset])->groupBy('d')->pluck('c', 'd');
                $o = $orders($from, $to)->selectRaw("DATE(CONVERT_TZ(created_at, '+00:00', ?)) d, COUNT(*) c", [$offset])->groupBy('d')->pluck('c', 'd');
                for ($d = $from->copy(); $d->lt($to); $d->addDay()) {
                    $daily[] = ['d' => $d->format('d M'), 's' => (int) ($s[$d->toDateString()] ?? 0), 'o' => (int) ($o[$d->toDateString()] ?? 0)];
                }
            }

            $sources = $sessions($from, $to)->selectRaw("COALESCE(NULLIF(source, ''), 'Direct / App') src, COUNT(*) c, SUM(ordered) o, SUM(reached_cart) cart")
                ->groupBy('src')->orderByDesc('c')->limit(8)->get();
            $devices = $sessions($from, $to)->selectRaw("COALESCE(NULLIF(device, ''), 'Other') dev, COUNT(*) c, SUM(ordered) o")->groupBy('dev')->orderByDesc('c')->get();
            $landings = $sessions($from, $to)->whereNotNull('landing_url')->selectRaw('landing_url u, MIN(landing_type) t, COUNT(*) c, SUM(ordered) o')
                ->groupBy('landing_url')->orderByDesc('c')->limit(6)->get();

            // products people look at but don't buy (unique viewers vs units ordered vs cart adds)
            $views = ProductView::whereBetween('viewed_at', [$utc($from), $utc($to)->subSecond()])->selectRaw('product_id, COUNT(*) viewers')
                ->groupBy('product_id')->orderByDesc('viewers')->limit(60)->pluck('viewers', 'product_id');
            $sold = OrderItem::whereIn('product_id', $views->keys())
                ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$utc($from), $utc($to)->subSecond()])->whereNotIn('status', ['cancelled', 'refunded']))
                ->selectRaw('product_id, SUM(quantity) q')->groupBy('product_id')->pluck('q', 'product_id');
            $adds = ActivityLog::where('event', 'cart.add')->whereBetween('created_at', [$utc($from), $utc($to)->subSecond()])
                ->whereIn('subject_id', $views->keys())->selectRaw('subject_id, COUNT(*) c')->groupBy('subject_id')->pluck('c', 'subject_id');
            $names = Product::whereIn('id', $views->keys())->get(['id', 'name', 'slug', 'image', 'price', 'sale_price', 'stock'])->keyBy('id');
            $productRows = $views->map(fn ($v, $id) => isset($names[$id]) ? (object) ['p' => $names[$id], 'viewers' => (int) $v, 'adds' => (int) ($adds[$id] ?? 0), 'sold' => (int) ($sold[$id] ?? 0)] : null)->filter()->values();
            $notBuying = $productRows->filter(fn ($r) => $r->sold === 0 && $r->viewers >= 3)->take(6)->values();
            $topViewed = $productRows->take(6);

            $leadsQ = CheckoutLead::where('status', 'open')->whereBetween('last_activity_at', [$utc($from), $utc($to)->subSecond()]);
            $leadCount = (clone $leadsQ)->count();
            $leads = $leadsQ->latest('last_activity_at')->limit(5)->get(['id', 'name', 'phone', 'subtotal', 'last_activity_at']);

            $leads = $leads->map(fn ($l) => ['id' => $l->id, 'name' => $l->name, 'phone' => $l->phone, 'subtotal' => (float) $l->subtotal, 'ago' => $l->last_activity_at?->diffForHumans()]);
            $productRow = fn ($r) => ['p' => $r->p->only(['id', 'name', 'slug', 'image', 'price', 'sale_price', 'stock']), 'viewers' => $r->viewers, 'adds' => $r->adds, 'sold' => $r->sold];

            return compact('key', 'label', 'days', 'now', 'prev', 'hourly', 'peak', 'quiet', 'daily', 'leadCount')
                + [
                    'sources' => $sources->toArray(), 'devices' => $devices->toArray(), 'landings' => $landings->toArray(),
                    'notBuying' => $notBuying->map($productRow)->all(), 'topViewed' => $topViewed->map($productRow)->all(),
                    'leads' => $leads->all(),
                    'since' => VisitorSession::min('started_at'),
                ];
        })();
    }

    /** The live part only — polled by the page every few seconds. */
    public function feed(Request $request)
    {
        $data = $this->snapshot($request);

        return response()->json([
            'count' => $data['live']->count(),
            'html' => view('admin.live-visitors._feed', $data)->render(),
        ]);
    }

    /** Compact numbers for the dashboard card. */
    public function summary(Request $request)
    {
        $data = $this->snapshot($request);

        return response()->json([
            'count' => $data['live']->count(),
            'byType' => $data['byType'],
            'top' => collect($data['topProducts'])->take(3)->values(),
            'checkout' => $data['live']->where('page_type', 'checkout')->count(),
            'url' => route('admin.live-visitors.index'),
        ]);
    }

    private function snapshot(Request $request): array
    {
        $includeTeam = $request->boolean('team');
        $recent = LiveVisitor::with(['user:id,name,phone,role', 'product:id,name,slug,price,sale_price,image'])
            ->where('last_seen_at', '>=', now()->subMinutes(30))
            ->orderByDesc('page_started_at')
            ->get();

        $isTeam = fn (LiveVisitor $v) => $v->user && $v->user->canAccessAdmin();
        $teamLive = $recent->filter(fn ($v) => $v->last_seen_at->gte(now()->subSeconds(LiveVisitor::LIVE_SECONDS)) && $isTeam($v))->count();
        if (!$includeTeam) {
            $recent = $recent->reject($isTeam)->values();
        }

        $liveSince = now()->subSeconds(LiveVisitor::LIVE_SECONDS);
        $live = $recent->filter(fn ($v) => $v->last_seen_at->gte($liveSince))
            // checkout and cart first — those are the visitors about to order
            ->sortBy(fn ($v) => [array_search($v->page_type, ['checkout', 'cart', 'product']) === false ? 9 : array_search($v->page_type, ['checkout', 'cart', 'product']), -$v->page_started_at?->timestamp])
            ->values();
        $left = $recent->filter(fn ($v) => $v->last_seen_at->lt($liveSince) && $v->last_seen_at->gte(now()->subMinutes(10)))->values();

        $byType = $live->groupBy('page_type')->map->count()->sortDesc()->all();

        $topProducts = $live->whereNotNull('product_id')->groupBy('product_id')
            ->map(fn ($group) => [
                'name' => $group->first()->product?->name ?? $group->first()->title,
                'price' => (float) ($group->first()->product?->sale_price ?? $group->first()->product?->price ?? 0),
                'image' => $group->first()->product?->image ? image_thumb($group->first()->product->image, 200) : null,
                'url' => $group->first()->product ? route('products.show', $group->first()->product) : $group->first()->url,
                'viewers' => $group->count(),
            ])->sortByDesc('viewers')->values()->all();

        // visitors on the site in each of the last 30 minutes (oldest first)
        $chart = [];
        for ($i = 29; $i >= 0; $i--) {
            $start = now()->subMinutes($i + 1);
            $end = now()->subMinutes($i);
            $chart[] = $recent->filter(fn ($v) => $v->first_seen_at && $v->first_seen_at->lte($end) && $v->last_seen_at->gte($start))->count();
        }

        $today = LiveVisitor::where('first_seen_at', '>=', now()->setTimezone(setting('timezone', 'Asia/Dhaka'))->startOfDay()->utc())->count();

        return [
            'live' => $live,
            'left' => $left,
            'byType' => $byType,
            'topProducts' => $topProducts,
            'chart' => $chart,
            'visitorsToday' => $today,
            'teamLive' => $teamLive,
            'includeTeam' => $includeTeam,
            'cartValue' => (float) $live->sum('cart_total'),
        ];
    }
}
