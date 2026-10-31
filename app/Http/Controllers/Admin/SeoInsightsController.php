<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\SearchConsoleRow;
use App\Services\SearchConsole;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Admin → SEO Insights: Google Search Console turned into a to-do list. Which searches show each
 * product, at what position, how many people saw/clicked it, and — per product — what to fix
 * next (description, reviews, keyword missing from the title, Bangla searches…), ordered by how
 * much there is to win. Data comes from App\Services\SearchConsole (synced daily).
 */
class SeoInsightsController extends Controller
{
    private const WEAK = ['price', 'in', 'bd', 'bangladesh', 'the', 'for', 'of', 'and', 'with', 'buy', 'online', 'original', 'best', 'dam', 'koto'];

    public function index(Request $request)
    {
        $connected = SearchConsole::connected();
        $hasData = SearchConsoleRow::query()->exists();
        if (! $hasData) {
            return view('admin.seo-insights.index', ['connected' => $connected, 'hasData' => false] + $this->setupInfo());
        }

        $period = in_array((int) $request->period, [7, 28, 90], true) ? (int) $request->period : 28;
        $end = Carbon::parse(SearchConsoleRow::max('date'));
        $from = $end->copy()->subDays($period - 1);
        $prevTo = $from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($period - 1);
        $range = [$from->toDateString(), $end->toDateString()];
        $prevRange = [$prevFrom->toDateString(), $prevTo->toDateString()];

        $kpi = fn ($r) => SearchConsoleRow::whereBetween('date', $r)
            ->selectRaw('COALESCE(SUM(clicks),0) c, COALESCE(SUM(impressions),0) i, COALESCE(SUM(position*impressions)/NULLIF(SUM(impressions),0),0) p')->first();
        $now = $kpi($range);
        $before = $kpi($prevRange);

        $daily = SearchConsoleRow::whereBetween('date', $range)->groupBy('date')->orderBy('date')
            ->selectRaw('date, SUM(clicks) c, SUM(impressions) i')->get();

        $tab = in_array($request->tab, ['plan', 'keywords', 'pages'], true) ? $request->tab : 'plan';

        return view('admin.seo-insights.index', [
            'connected' => $connected, 'hasData' => true, 'period' => $period, 'tab' => $tab,
            'from' => $from, 'end' => $end, 'now' => $now, 'before' => $before, 'daily' => $daily,
            'plan' => $tab === 'plan' ? $this->plan($range) : null,
            'keywords' => $tab === 'keywords' ? $this->keywords($request, $range, $prevRange) : null,
            'pages' => $tab === 'pages' ? $this->pages($range) : null,
        ] + $this->setupInfo());
    }

    /** Products ordered by how much there is to win, each with its searches and its to-do list. */
    private function plan(array $range)
    {
        $agg = SearchConsoleRow::whereBetween('date', $range)->whereNotNull('product_id')
            ->groupBy('product_id')
            ->selectRaw('product_id, SUM(clicks) c, SUM(impressions) i, SUM(position*impressions)/NULLIF(SUM(impressions),0) p')
            ->orderByDesc('i')->limit(150)->get()->keyBy('product_id');
        if ($agg->isEmpty()) {
            return collect();
        }

        $queries = SearchConsoleRow::whereBetween('date', $range)->whereIn('product_id', $agg->keys())
            ->groupBy('product_id', 'query_hash')
            ->selectRaw('product_id, MIN(query) q, SUM(clicks) c, SUM(impressions) i, SUM(position*impressions)/NULLIF(SUM(impressions),0) p')
            ->get()->groupBy('product_id');

        $products = Product::whereIn('id', $agg->keys())->withCount('reviews')->withAvg('reviews', 'rating')
            ->get(['id', 'name', 'slug', 'image', 'description', 'short_description', 'meta_title', 'meta_description', 'stock', 'is_active'])
            ->keyBy('id');

        return $agg->map(function ($a) use ($queries, $products) {
            $p = $products[$a->product_id] ?? null;
            if (! $p) {
                return null;
            }
            $qs = ($queries[$a->product_id] ?? collect())->sortByDesc('i')->values();
            $pos = (float) $a->p;
            $ctr = $a->i ? $a->c / $a->i * 100 : 0;
            // most to win: lots of impressions just below the top (page 1 bottom / page 2)
            $weight = match (true) { $pos <= 3 => .25, $pos <= 10 => 1, $pos <= 20 => .85, $pos <= 30 => .5, default => .25 };

            return (object) [
                'product' => $p, 'clicks' => (int) $a->c, 'impressions' => (int) $a->i, 'position' => $pos, 'ctr' => $ctr,
                'score' => $a->i * $weight, 'queries' => $qs->take(8),
                'tips' => $this->tips($p, $qs, $pos, $ctr),
                'stage' => $pos <= 3 ? 'top' : ($pos <= 10 ? 'page1' : ($pos <= 20 ? 'page2' : 'deep')),
            ];
        })->filter()->sortByDesc('score')->values();
    }

    /** Concrete next steps for one product, most valuable first. */
    private function tips(Product $p, $queries, float $pos, float $ctr): array
    {
        $tips = [];
        $top = $queries->first();
        $text = mb_strtolower(strip_tags($p->name . ' ' . $p->meta_title));
        $desc = mb_strtolower(strip_tags((string) $p->description . ' ' . $p->short_description));
        $words = count(preg_split('/\s+/u', trim(strip_tags((string) $p->description)), -1, PREG_SPLIT_NO_EMPTY));
        $has = function (string $haystack, string $q) {
            $need = array_filter(preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($q)), fn ($w) => mb_strlen($w) > 2 && ! in_array($w, self::WEAK, true));
            return $need && collect($need)->every(fn ($w) => str_contains($haystack, $w));
        };

        if ($pos > 3 && $pos <= 10 && $ctr < 3) {
            $tips[] = ['🎯', 'On page 1 but few people click (' . number_format($ctr, 1) . '%). Make the SEO title more tempting — e.g. “' . Str::limit($p->name, 40) . ' Price in BD – 100% Original | Cash on Delivery”.'];
        }
        if ($top && ! $has($text, $top->q)) {
            $tips[] = ['🔑', 'Main search “' . $top->q . '” is not in the product name / SEO title — add its words to the SEO title.'];
        }
        if ($top && ! $has($desc, $top->q)) {
            $tips[] = ['✍️', 'Use “' . $top->q . '” naturally in the first paragraph of the description.'];
        }
        if ($words < 150) {
            $tips[] = ['📝', 'Description is short (' . $words . ' words). Write 150–300 of your own words: what it does, who it\'s for, how to use, how to spot an original.'];
        }
        if ((int) $p->reviews_count === 0) {
            $tips[] = ['⭐', 'No reviews yet — ask 2–3 recent buyers for a review (stars show in Google and lift clicks).'];
        } elseif ((int) $p->reviews_count < 3) {
            $tips[] = ['⭐', 'Only ' . $p->reviews_count . ' review(s) — a few more build trust and help ranking.'];
        }
        if ($pos > 10 && $pos <= 20) {
            $tips[] = ['🚀', 'Close to page 1 (position ' . number_format($pos, 1) . '). A blog post linking here + more reviews can push it up.'];
        }
        $bangla = $queries->first(fn ($q) => preg_match('/\p{Bengali}/u', $q->q));
        if ($bangla && ! preg_match('/\p{Bengali}/u', $desc)) {
            $tips[] = ['🇧🇩', 'People search in Bangla (“' . $bangla->q . '”) — add a short Bangla paragraph or FAQ.'];
        }
        if ((int) $p->stock <= 0) {
            $tips[] = ['📦', 'Out of stock — Google traffic is wasted until it\'s back (pre-order is on, but restock if you can).'];
        }

        return array_slice($tips, 0, 5);
    }

    /** Every search query with position, change vs the previous period, and where it lands. */
    private function keywords(Request $request, array $range, array $prevRange)
    {
        $f = $request->get('f');
        $q = trim((string) $request->get('q'));
        $base = SearchConsoleRow::whereBetween('date', $range)
            ->when($q !== '', fn ($w) => $w->where('query', 'like', '%' . $q . '%'))
            ->groupBy('query_hash')
            ->selectRaw('query_hash, MIN(query) q, SUM(clicks) c, SUM(impressions) i, SUM(position*impressions)/NULLIF(SUM(impressions),0) p');

        $all = $base->get();
        $prev = SearchConsoleRow::whereBetween('date', $prevRange)->whereIn('query_hash', $all->pluck('query_hash'))
            ->groupBy('query_hash')->selectRaw('query_hash, SUM(position*impressions)/NULLIF(SUM(impressions),0) p')->pluck('p', 'query_hash');
        $firstSeen = SearchConsoleRow::whereIn('query_hash', $all->pluck('query_hash'))->groupBy('query_hash')->selectRaw('query_hash, MIN(date) d')->pluck('d', 'query_hash');
        $newSince = Carbon::parse($range[1])->subDays(6)->toDateString();

        $rows = $all->map(function ($r) use ($prev, $firstSeen, $newSince) {
            $r->ctr = $r->i ? $r->c / $r->i * 100 : 0;
            $r->change = isset($prev[$r->query_hash]) ? (float) $prev[$r->query_hash] - (float) $r->p : null; // + = moved up
            $r->isNew = ($firstSeen[$r->query_hash] ?? null) >= $newSince;
            $r->bangla = (bool) preg_match('/\p{Bengali}/u', $r->q);
            return $r;
        });

        $counts = [
            'all' => $rows->count(),
            'page1' => $rows->filter(fn ($r) => $r->p <= 10)->count(),
            'near' => $rows->filter(fn ($r) => $r->p > 10 && $r->p <= 20)->count(),
            'lowctr' => $rows->filter(fn ($r) => $r->p <= 10 && $r->i >= 20 && $r->ctr < 2)->count(),
            'new' => $rows->where('isNew', true)->count(),
            'bangla' => $rows->where('bangla', true)->count(),
        ];
        $rows = match ($f) {
            'page1' => $rows->filter(fn ($r) => $r->p <= 10),
            'near' => $rows->filter(fn ($r) => $r->p > 10 && $r->p <= 20),
            'lowctr' => $rows->filter(fn ($r) => $r->p <= 10 && $r->i >= 20 && $r->ctr < 2),
            'new' => $rows->where('isNew', true),
            'bangla' => $rows->where('bangla', true),
            default => $rows,
        };
        $rows = $rows->sortByDesc('i')->take(300)->values();

        // where each query lands most (product name / page type)
        $landing = SearchConsoleRow::whereBetween('date', $range)->whereIn('query_hash', $rows->pluck('query_hash'))
            ->groupBy('query_hash', 'product_id', 'page_type', 'page_hash')
            ->selectRaw('query_hash, product_id, page_type, MIN(page) page, SUM(impressions) i')->get()
            ->sortByDesc('i')->unique('query_hash')->keyBy('query_hash');
        $names = Product::whereIn('id', $landing->pluck('product_id')->filter())->pluck('name', 'id');
        $rows->each(function ($r) use ($landing, $names) {
            $l = $landing[$r->query_hash] ?? null;
            $r->landType = $l?->page_type;
            $r->landPage = $l?->page;
            $r->productId = $l?->product_id;
            $r->productName = $l?->product_id ? ($names[$l->product_id] ?? null) : null;
        });

        return (object) ['rows' => $rows, 'counts' => $counts, 'f' => $f, 'q' => $q];
    }

    /** Category / brand / blog / home pages (non-product) by search traffic. */
    private function pages(array $range)
    {
        return SearchConsoleRow::whereBetween('date', $range)->where(fn ($w) => $w->whereNull('product_id')->orWhere('page_type', '!=', 'product'))
            ->groupBy('page_hash', 'page_type')
            ->selectRaw('page_type, MIN(page) page, SUM(clicks) c, SUM(impressions) i, SUM(position*impressions)/NULLIF(SUM(impressions),0) p, COUNT(DISTINCT query_hash) qn')
            ->orderByDesc('i')->limit(150)->get();
    }

    private function setupInfo(): array
    {
        $email = null;
        try {
            $email = SearchConsole::connected() ? SearchConsole::credentials()['client_email'] : null;
        } catch (\Throwable $e) {
        }

        return ['serviceEmail' => $email, 'siteUrl' => SearchConsole::siteUrl(), 'lastSync' => setting('gsc_last_sync')];
    }

    public function connect(Request $request)
    {
        $request->validate([
            'key' => 'nullable|file|max:20',
            'site_url' => 'required|string|max:255',
        ]);
        if ($request->hasFile('key')) {
            $json = json_decode((string) file_get_contents($request->file('key')->getRealPath()), true);
            if (! is_array($json) || ($json['type'] ?? '') !== 'service_account' || empty($json['client_email']) || empty($json['private_key'])) {
                return back()->with('error', 'That file is not a Google service-account key (JSON). Download it from Google Cloud → Service Accounts → Keys → Add key → JSON.');
            }
            Storage::disk('private')->put(SearchConsole::KEY_FILE, json_encode($json));
            Cache::forget('gsc:token:' . md5($json['client_email']));
        } elseif (! SearchConsole::connected()) {
            return back()->with('error', 'Please choose the service-account JSON key file.');
        }
        \App\Models\Setting::set('gsc_site_url', trim($request->site_url), 'seo');

        return $this->test();
    }

    public function test()
    {
        try {
            $sites = SearchConsole::sites();
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
        $site = SearchConsole::siteUrl();
        if (! array_key_exists($site, $sites)) {
            return back()->with('error', 'Connected to Google, but this account can\'t see “' . $site . '” yet. '
                . ($sites ? 'It can see: ' . implode(', ', array_keys($sites)) . ' — use one of these as the property. ' : '')
                . 'In Search Console → Settings → Users and permissions → Add user: ' . SearchConsole::credentials()['client_email'] . ' (Restricted is enough).');
        }

        return back()->with('success', 'Connected to ' . $site . ' ✓ — now press “Load last 90 days”.');
    }

    public function sync(Request $request)
    {
        @set_time_limit(300);
        $days = in_array((int) $request->days, [5, 30, 90, 180], true) ? (int) $request->days : 5;
        try {
            $n = SearchConsole::sync(now()->subDays($days), now()->subDay());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Loaded ' . number_format($n) . ' search rows from Google (last ' . $days . ' days).');
    }

    public function disconnect()
    {
        Storage::disk('private')->delete(SearchConsole::KEY_FILE);

        return back()->with('success', 'Search Console disconnected. Already-loaded data stays until you connect again.');
    }

    /** Searches for one product — shown on its edit page while writing the description. */
    public static function forProduct(int $productId, int $days = 90)
    {
        $end = SearchConsoleRow::where('product_id', $productId)->max('date');
        if (! $end) {
            return collect();
        }

        return SearchConsoleRow::where('product_id', $productId)
            ->where('date', '>=', Carbon::parse($end)->subDays($days - 1)->toDateString())
            ->groupBy('query_hash')
            ->selectRaw('MIN(query) q, SUM(clicks) c, SUM(impressions) i, SUM(position*impressions)/NULLIF(SUM(impressions),0) p')
            ->orderByDesc('i')->limit(15)->get();
    }
}
