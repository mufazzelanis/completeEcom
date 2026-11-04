<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlogConversion;
use App\Models\BlogPost;
use App\Models\BlogProductClick;
use App\Models\BlogRead;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Blog → Analytics: who read which post, for how long, how far, which products they clicked and
 * what it sold. Reads come from blog_reads (one row per page view, JS-only so crawlers don't count);
 * dates are the store's own days (setting timezone), not UTC.
 */
class BlogAnalyticsController extends Controller
{
    public const PERIODS = [
        'today'     => 'Today',
        'yesterday' => 'Yesterday',
        '7d'        => 'Last 7 days',
        '30d'       => 'Last 30 days',
        '90d'       => 'Last 90 days',
    ];

    private const SORTS = ['readers', 'reads', 'avg_time', 'avg_scroll', 'finish_rate', 'clicks', 'ctr', 'orders', 'revenue'];

    public function index(Request $request)
    {
        $r = $this->range($request);
        $kpi = $this->kpis(null, $r['from'], $r['to']);
        $prev = $this->kpis(null, $r['prevFrom'], $r['prevTo']);
        $trend = $this->trend(null, $r);

        // per-post table
        $reads = $this->reads(null, $r['from'], $r['to'])
            ->groupBy('blog_post_id')
            ->selectRaw('blog_post_id, COUNT(*) n_reads, COUNT(DISTINCT visitor_key) readers, AVG(active_seconds) avg_time, AVG(max_scroll) avg_scroll, SUM(max_scroll >= ?) finished, SUM(product_clicks > 0) clicked_reads', [BlogRead::COMPLETE_AT])
            ->get()->keyBy('blog_post_id');
        [$a, $b] = $this->utc($r['from'], $r['to']);
        $clicks = BlogProductClick::whereBetween('created_at', [$a, $b])->groupBy('blog_post_id')
            ->selectRaw("blog_post_id, COUNT(*) clicks, SUM(action IN ('cart','buy')) intents")->get()->keyBy('blog_post_id');
        $conv = BlogConversion::whereBetween('created_at', [$a, $b])->groupBy('blog_post_id')
            ->selectRaw('blog_post_id, COUNT(*) orders, SUM(revenue) revenue, SUM(matched_revenue) matched')->get()->keyBy('blog_post_id');
        $prevReaders = $this->reads(null, $r['prevFrom'], $r['prevTo'])->groupBy('blog_post_id')
            ->selectRaw('blog_post_id, COUNT(DISTINCT visitor_key) readers')->pluck('readers', 'blog_post_id');

        $ids = $reads->keys()->merge($clicks->keys())->merge($conv->keys())->unique();
        $posts = BlogPost::with('category:id,name')->whereIn('id', $ids)->get(['id', 'title', 'slug', 'image', 'blog_category_id', 'status'])->keyBy('id');

        $rows = $ids->map(function ($id) use ($reads, $clicks, $conv, $prevReaders, $posts) {
            $p = $posts->get($id);
            if (!$p) {
                return null;
            }
            $rd = $reads->get($id);
            $n = (int) ($rd->n_reads ?? 0);

            return (object) [
                'post'        => $p,
                'reads'       => $n,
                'readers'     => (int) ($rd->readers ?? 0),
                'prev'        => (int) ($prevReaders[$id] ?? 0),
                'avg_time'    => (float) ($rd->avg_time ?? 0),
                'avg_scroll'  => (float) ($rd->avg_scroll ?? 0),
                'finish_rate' => $n ? $rd->finished / $n * 100 : 0,
                'clicks'      => (int) ($clicks[$id]->clicks ?? 0),
                'intents'     => (int) ($clicks[$id]->intents ?? 0),
                'ctr'         => $n ? $rd->clicked_reads / $n * 100 : 0,
                'orders'      => (int) ($conv[$id]->orders ?? 0),
                'revenue'     => (float) ($conv[$id]->matched ?? 0),
            ];
        })->filter();

        if ($request->filled('search')) {
            $s = mb_strtolower($request->search);
            $rows = $rows->filter(fn ($row) => str_contains(mb_strtolower($row->post->title), $s));
        }
        $sort = in_array($request->query('sort'), self::SORTS, true) ? $request->query('sort') : 'readers';
        $rows = $rows->sortByDesc(fn ($row) => [$row->{$sort}, $row->reads])->values();

        $silent = BlogPost::published()->whereNotIn('id', $reads->keys())->count();

        $topProducts = $this->topProducts(null, $a, $b);
        $sources = $this->breakdown(null, $r, 'source');
        $devices = $this->breakdown(null, $r, 'device');
        $live = $this->live();

        return view('admin.blog.analytics.index', compact('r', 'kpi', 'prev', 'trend', 'rows', 'sort', 'silent', 'topProducts', 'sources', 'devices', 'live'));
    }

    public function show(Request $request, BlogPost $post)
    {
        $r = $this->range($request, '30d');
        $kpi = $this->kpis($post->id, $r['from'], $r['to']);
        $prev = $this->kpis($post->id, $r['prevFrom'], $r['prevTo']);
        $trend = $this->trend($post->id, $r);
        [$a, $b] = $this->utc($r['from'], $r['to']);

        $depth = $this->reads($post->id, $r['from'], $r['to'])
            ->selectRaw('COUNT(*) n, SUM(max_scroll >= 25) d25, SUM(max_scroll >= 50) d50, SUM(max_scroll >= 75) d75, SUM(max_scroll >= ?) d100', [BlogRead::COMPLETE_AT])->first();
        $time = $this->reads($post->id, $r['from'], $r['to'])
            ->selectRaw('SUM(active_seconds < 10) t0, SUM(active_seconds BETWEEN 10 AND 29) t1, SUM(active_seconds BETWEEN 30 AND 59) t2, SUM(active_seconds BETWEEN 60 AND 179) t3, SUM(active_seconds BETWEEN 180 AND 299) t4, SUM(active_seconds >= 300) t5')->first();

        $sources = $this->breakdown($post->id, $r, 'source');
        $devices = $this->breakdown($post->id, $r, 'device');
        $referrers = $this->reads($post->id, $r['from'], $r['to'])->whereNotNull('referrer_host')
            ->groupBy('referrer_host')->selectRaw('referrer_host, COUNT(*) n')->orderByDesc('n')->limit(8)->pluck('n', 'referrer_host');

        // per product: actions × placement
        $productStats = BlogProductClick::where('blog_post_id', $post->id)->whereBetween('created_at', [$a, $b])
            ->groupBy('product_id')
            ->selectRaw("product_id, COUNT(*) total, SUM(action='open') opens, SUM(action='cart') carts, SUM(action='buy') buys,
                SUM(placement='inline') p_inline, SUM(placement='shelf') p_shelf, SUM(placement='sticky') p_sticky, SUM(placement='sidebar') p_sidebar")
            ->orderByDesc('total')->get();
        $products = Product::whereIn('id', $productStats->pluck('product_id')->merge($post->products()->pluck('products.id')))
            ->get(['id', 'name', 'slug', 'image', 'price', 'sale_price'])->keyBy('id');
        $attachedIds = $post->products()->pluck('products.id');

        $conversions = BlogConversion::with('order:id,order_number,total,status,shipping_name,created_at')
            ->where('blog_post_id', $post->id)->whereBetween('created_at', [$a, $b])->latest('created_at')->limit(20)->get();
        $recent = BlogRead::where('blog_post_id', $post->id)->latest('id')->limit(25)
            ->get(['id', 'source', 'referrer_host', 'device', 'active_seconds', 'max_scroll', 'product_clicks', 'user_id', 'created_at', 'updated_at']);
        $liveNow = BlogRead::where('blog_post_id', $post->id)->where('updated_at', '>=', now()->subMinutes(2))->count();

        return view('admin.blog.analytics.show', compact(
            'post', 'r', 'kpi', 'prev', 'trend', 'depth', 'time', 'sources', 'devices', 'referrers',
            'productStats', 'products', 'attachedIds', 'conversions', 'recent', 'liveNow'
        ));
    }

    /** The selected period + the equally long one right before it (for the "vs" deltas), in store days. */
    private function range(Request $request, string $default = 'today'): array
    {
        $tz = setting('timezone', 'Asia/Dhaka');
        $today = now($tz)->startOfDay();
        $period = $request->query('period', $default);

        if ($period === 'custom' && $request->filled(['from', 'to'])) {
            try {
                $from = Carbon::parse($request->from, $tz)->startOfDay();
                $to = Carbon::parse($request->to, $tz)->startOfDay();
            } catch (\Throwable) {
                $period = $default;
            }
            if (isset($from, $to) && $from->gt($to)) {
                [$from, $to] = [$to, $from];
            }
            if (isset($to) && $to->gt($today)) {
                $to = $today->copy();
            }
            if (isset($from) && $from->diffInDays($to) > 365) {
                $from = $to->copy()->subDays(365);
            }
        }
        if ($period !== 'custom' || !isset($from)) {
            $period = array_key_exists($period, self::PERIODS) ? $period : $default;
            [$from, $to] = match ($period) {
                'today'     => [$today->copy(), $today->copy()],
                'yesterday' => [$today->copy()->subDay(), $today->copy()->subDay()],
                '7d'        => [$today->copy()->subDays(6), $today->copy()],
                '90d'       => [$today->copy()->subDays(89), $today->copy()],
                default     => [$today->copy()->subDays(29), $today->copy()],
            };
        }

        $days = (int) $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay();

        return [
            'period' => $period, 'tz' => $tz, 'from' => $from, 'to' => $to, 'days' => $days,
            'prevFrom' => $prevTo->copy()->subDays($days - 1), 'prevTo' => $prevTo,
            'label' => self::PERIODS[$period] ?? ($from->format('d M') . ' – ' . $to->format('d M Y')),
            'prevLabel' => match ($period) { 'today' => 'yesterday', 'yesterday' => 'the day before', default => 'previous ' . $days . ' days' },
        ];
    }

    /** [start, end] of those store days as UTC timestamps, for the created_at columns. */
    private function utc(Carbon $from, Carbon $to): array
    {
        return [$from->copy()->startOfDay()->utc(), $to->copy()->endOfDay()->utc()];
    }

    private function reads(?int $postId, Carbon $from, Carbon $to)
    {
        return BlogRead::query()
            ->whereBetween('read_date', [$from->toDateString(), $to->toDateString()])
            ->when($postId, fn ($q) => $q->where('blog_post_id', $postId));
    }

    private function kpis(?int $postId, Carbon $from, Carbon $to): array
    {
        $rd = $this->reads($postId, $from, $to)
            ->selectRaw('COUNT(*) n_reads, COUNT(DISTINCT visitor_key) readers, AVG(active_seconds) avg_time, AVG(max_scroll) avg_scroll, SUM(max_scroll >= ?) finished, SUM(product_clicks > 0) clicked_reads, SUM(active_seconds) total_time', [BlogRead::COMPLETE_AT])
            ->first();
        [$a, $b] = $this->utc($from, $to);
        $cl = BlogProductClick::whereBetween('created_at', [$a, $b])->when($postId, fn ($q) => $q->where('blog_post_id', $postId))
            ->selectRaw("COUNT(*) total, SUM(action='open') opens, SUM(action='cart') carts, SUM(action='buy') buys")->first();
        $cv = BlogConversion::whereBetween('created_at', [$a, $b])->when($postId, fn ($q) => $q->where('blog_post_id', $postId))
            ->selectRaw('COUNT(*) orders, COALESCE(SUM(revenue), 0) revenue, COALESCE(SUM(matched_revenue), 0) matched')->first();

        $n = (int) $rd->n_reads;
        $readers = (int) $rd->readers;

        return [
            'reads'       => $n,
            'readers'     => $readers,
            'avg_time'    => (float) $rd->avg_time,
            'total_time'  => (int) $rd->total_time,
            'avg_scroll'  => (float) $rd->avg_scroll,
            'finish_rate' => $n ? $rd->finished / $n * 100 : 0,
            'ctr'         => $n ? $rd->clicked_reads / $n * 100 : 0,
            'clicks'      => (int) $cl->total,
            'opens'       => (int) $cl->opens,
            'carts'       => (int) $cl->carts,
            'buys'        => (int) $cl->buys,
            'orders'      => (int) $cv->orders,
            'revenue'     => (float) $cv->matched,
            'basket'      => (float) $cv->revenue,
            'conv_rate'   => $readers ? $cv->orders / $readers * 100 : 0,
        ];
    }

    /** Readers + product clicks per hour (a single day) or per day. */
    private function trend(?int $postId, array $r): array
    {
        $offset = (int) now($r['tz'])->getOffset(); // store time = UTC + offset (no DST in BD; good enough elsewhere)
        [$a, $b] = $this->utc($r['from'], $r['to']);

        if ($r['days'] === 1) {
            $bucket = "HOUR(DATE_ADD(created_at, INTERVAL {$offset} SECOND))";
            $keys = range(0, $r['from']->isToday() ? now($r['tz'])->hour : 23);
            $labels = array_map(fn ($h) => Carbon::createFromTime($h)->format('ga'), $keys);
            $readers = BlogRead::whereBetween('created_at', [$a, $b]);
        } else {
            $bucket = 'read_date';
            $keys = [];
            for ($d = $r['from']->copy(); $d->lte($r['to']); $d->addDay()) {
                $keys[] = $d->toDateString();
            }
            $labels = array_map(fn ($k) => Carbon::parse($k)->format('d M'), $keys);
            $readers = $this->reads(null, $r['from'], $r['to']);
        }

        $readers = $readers->when($postId, fn ($q) => $q->where('blog_post_id', $postId))
            ->groupBy(DB::raw($bucket))->selectRaw("{$bucket} k, COUNT(DISTINCT visitor_key) n")->pluck('n', 'k');
        $clickBucket = $r['days'] === 1 ? $bucket : "DATE(DATE_ADD(created_at, INTERVAL {$offset} SECOND))";
        $clicks = BlogProductClick::whereBetween('created_at', [$a, $b])->when($postId, fn ($q) => $q->where('blog_post_id', $postId))
            ->groupBy(DB::raw($clickBucket))->selectRaw("{$clickBucket} k, COUNT(*) n")->pluck('n', 'k');

        $norm = fn (Collection $c) => $c->mapWithKeys(fn ($v, $k) => [is_numeric($k) ? (int) $k : Carbon::parse($k)->toDateString() => (int) $v]);
        $readers = $norm($readers);
        $clicks = $norm($clicks);

        return [
            'labels'  => $labels,
            'readers' => array_map(fn ($k) => $readers[$k] ?? 0, $keys),
            'clicks'  => array_map(fn ($k) => $clicks[$k] ?? 0, $keys),
            'hourly'  => $r['days'] === 1,
        ];
    }

    private function breakdown(?int $postId, array $r, string $column): Collection
    {
        return $this->reads($postId, $r['from'], $r['to'])->groupBy($column)
            ->selectRaw("{$column} k, COUNT(*) n")->orderByDesc('n')->pluck('n', 'k');
    }

    private function topProducts(?int $postId, $a, $b): Collection
    {
        $stats = BlogProductClick::whereBetween('created_at', [$a, $b])->when($postId, fn ($q) => $q->where('blog_post_id', $postId))
            ->groupBy('product_id')
            ->selectRaw("product_id, COUNT(*) total, SUM(action='open') opens, SUM(action IN ('cart','buy')) intents, COUNT(DISTINCT blog_post_id) posts")
            ->orderByDesc('total')->limit(8)->get();
        $products = Product::whereIn('id', $stats->pluck('product_id'))->get(['id', 'name', 'slug', 'image'])->keyBy('id');

        return $stats->map(fn ($s) => (object) ['product' => $products->get($s->product_id), 'total' => (int) $s->total, 'opens' => (int) $s->opens, 'intents' => (int) $s->intents, 'posts' => (int) $s->posts])
            ->filter(fn ($s) => $s->product);
    }

    /** Reading right now: tracker pinged in the last 2 minutes. */
    private function live(): array
    {
        $rows = BlogRead::where('updated_at', '>=', now()->subMinutes(2))->groupBy('blog_post_id')
            ->selectRaw('blog_post_id, COUNT(*) n')->orderByDesc('n')->limit(5)->pluck('n', 'blog_post_id');
        $posts = BlogPost::whereIn('id', $rows->keys())->get(['id', 'title', 'slug'])->keyBy('id');

        return [
            'total' => BlogRead::where('updated_at', '>=', now()->subMinutes(2))->count(),
            'posts' => $rows->filter(fn ($n, $id) => $posts->has($id))->map(fn ($n, $id) => ['title' => $posts[$id]->title, 'slug' => $posts[$id]->slug, 'n' => $n])->values(),
        ];
    }
}
