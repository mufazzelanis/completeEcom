<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Admin → Activity Log: ActivityLog (logins, product/order views, cart, orders — customers and
 * guests) and AuditLog (admin data changes) in one filterable, newest-first feed.
 *
 * Every filter is applied to each table on its own, using its indexes, and only the rows needed
 * for the requested page are merged — the previous version merged both whole tables first and
 * filtered the result, which on ~30k live rows made each filter take so long it looked like
 * filtering did nothing. Totals are per-table counts added together; the event and user dropdown
 * lists are cached for 10 minutes; IP locations never hold the page up (see batchIpLocations).
 */
class ActivityLogController extends Controller
{
    private const PER_PAGE = 50;

    public function index(Request $request)
    {
        $tz = setting('timezone', 'Asia/Dhaka');
        [$start, $end] = $this->periodRange($request, $tz);

        $source = $request->input('source');
        $traffic = $request->input('traffic');
        $branches = [];
        if ($source !== 'audit') {
            $branches[] = $this->filtered($this->activityQuery(), $request, $start, $end, 'activity_logs', 'event');
        }
        // admin actions are never bot traffic, so "bots only" leaves them out entirely
        if ($source !== 'activity' && $traffic !== 'bots') {
            $branches[] = $this->filtered($this->auditQuery(), $request, $start, $end, 'audit_logs', 'action');
        }

        $total = collect($branches)->sum(fn ($q) => (clone $q)->reorder()->count());
        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * self::PER_PAGE;

        $rows = collect();
        if ($branches && $offset < $total) {
            // each table contributes at most offset+perPage of its newest matching rows
            $parts = array_map(fn ($q) => $q->orderByDesc('created_at')->limit($offset + self::PER_PAGE), $branches);
            $union = array_shift($parts);
            foreach ($parts as $part) {
                $union->unionAll($part);
            }
            $rows = DB::query()->fromSub($union, 'logs')
                ->orderByDesc('created_at')->offset($offset)->limit(self::PER_PAGE)->get();
        }

        $logs = new LengthAwarePaginator($rows, $total, self::PER_PAGE, $page, [
            'path' => $request->url(),
            'query' => $request->except('page'),
        ]);
        $ipLocations = $this->batchIpLocations($rows->pluck('ip_address')->filter()->unique());

        $eventTypes = Cache::remember('activity_log:event_types', 600, fn () => DB::table('activity_logs')->distinct()->pluck('event')
            ->merge(DB::table('audit_logs')->distinct()->pluck('action'))->filter()->unique()->sort()->values()->all());

        $logUserIds = Cache::remember('activity_log:user_ids', 600, fn () => DB::table('activity_logs')->whereNotNull('user_id')->distinct()->pluck('user_id')
            ->merge(DB::table('audit_logs')->whereNotNull('user_id')->distinct()->pluck('user_id'))->unique()->values()->all());
        $logUsers = User::whereIn('id', $logUserIds)->orderBy('name')->get(['id', 'name', 'role']);

        // Today's headline numbers (Dhaka day), whatever the filters — each card links to that
        // event filtered to today. Cheap: (event, created_at) is indexed.
        $today = now()->setTimezone($tz)->startOfDay()->utc();
        $stats = Cache::remember('activity_log:stats:' . $today->timestamp, 60, fn () => [
            'logins_today'            => ActivityLog::where('event', 'login')->where('created_at', '>=', $today)->count(),
            'product_views_today'     => ActivityLog::where('event', 'product.view')->where('created_at', '>=', $today)->count(),
            'product_views_today_bot' => ActivityLog::where('event', 'product.view')->where('is_bot', true)->where('created_at', '>=', $today)->count(),
            'cart_adds_today'         => ActivityLog::where('event', 'cart.add')->where('created_at', '>=', $today)->count(),
            'orders_placed_today'     => ActivityLog::where('event', 'order.place')->where('created_at', '>=', $today)->count(),
            'order_views_today'       => ActivityLog::where('event', 'order.view')->where('created_at', '>=', $today)->count(),
        ]);

        $activeFilters = $this->activeFilters($request, $logUsers);

        return view('admin.activity-logs.index', compact('logs', 'eventTypes', 'logUsers', 'stats', 'ipLocations', 'activeFilters'));
    }

    private function activityQuery()
    {
        return DB::table('activity_logs')
            ->leftJoin('users', 'users.id', '=', 'activity_logs.user_id')
            ->select([
                'activity_logs.id',
                DB::raw("'activity' as source"),
                'activity_logs.user_id',
                'users.name as user_name',
                'users.role as user_role',
                'users.phone as user_phone',
                'activity_logs.event as event_type',
                'activity_logs.description',
                'activity_logs.subject_type',
                'activity_logs.subject_id',
                // order.place stores the shipping phone as it was AT THAT ORDER (the view
                // prefers it over users.phone, which can change later)
                'activity_logs.properties',
                'activity_logs.url',
                'activity_logs.referrer',
                'activity_logs.ip_address',
                'activity_logs.device',
                'activity_logs.browser',
                'activity_logs.platform',
                'activity_logs.is_bot',
                'activity_logs.created_at',
            ]);
    }

    private function auditQuery()
    {
        return DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->select([
                'audit_logs.id',
                DB::raw("'audit' as source"),
                'audit_logs.user_id',
                'users.name as user_name',
                'users.role as user_role',
                'users.phone as user_phone',
                'audit_logs.action as event_type',
                'audit_logs.description',
                'audit_logs.model_type as subject_type',
                'audit_logs.model_id as subject_id',
                DB::raw('NULL as properties'),
                DB::raw('NULL as url'),
                DB::raw('NULL as referrer'),
                'audit_logs.ip_address',
                DB::raw('NULL as device'),
                DB::raw('NULL as browser'),
                DB::raw('NULL as platform'),
                DB::raw('0 as is_bot'), // an admin action behind a login, never a bot
                'audit_logs.created_at',
            ]);
    }

    /** The request's filters applied to one table ($table, whose event column is $eventColumn). */
    private function filtered($query, Request $request, ?Carbon $start, ?Carbon $end, string $table, string $eventColumn)
    {
        if ($request->filled('search')) {
            $query->where("{$table}.description", 'like', '%' . addcslashes(trim($request->search), '%_\\') . '%');
        }
        if ($request->filled('event_type')) {
            $query->where("{$table}.{$eventColumn}", $request->event_type);
        }
        if ($request->input('role') === 'guest') {
            $query->whereNull("{$table}.user_id");
        } elseif ($request->filled('role')) {
            $query->where('users.role', $request->role);
        }
        if ($request->filled('user_id')) {
            $query->where("{$table}.user_id", (int) $request->user_id);
        }
        if ($table === 'activity_logs' && $request->input('traffic') === 'humans') {
            $query->where('activity_logs.is_bot', false);
        } elseif ($table === 'activity_logs' && $request->input('traffic') === 'bots') {
            $query->where('activity_logs.is_bot', true);
        }
        if ($start) {
            $query->where("{$table}.created_at", '>=', $start);
        }
        if ($end) {
            $query->where("{$table}.created_at", '<=', $end);
        }

        return $query;
    }

    /**
     * [start, end] in UTC for the Period filter, computed on the admin's own (Dhaka) calendar —
     * created_at is stored in UTC, so a plain whereDate() would be up to 6 hours off. "custom"
     * (or no period) uses the From/To dates with the same Dhaka-aware day boundaries.
     */
    private function periodRange(Request $request, string $tz): array
    {
        $period = $request->input('period');
        if ($period && $period !== 'custom') {
            $now = now()->setTimezone($tz);
            [$start, $end] = match ($period) {
                'today'      => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
                'yesterday'  => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
                'last_7'     => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
                'this_week'  => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
                'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
                'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
                'this_year'  => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
                default      => [null, null],
            };

            return [$start?->copy()->utc(), $end?->copy()->utc()];
        }

        $start = $end = null;
        try {
            if ($request->filled('from')) {
                $start = Carbon::parse($request->from, $tz)->startOfDay()->utc();
            }
            if ($request->filled('to')) {
                $end = Carbon::parse($request->to, $tz)->endOfDay()->utc();
            }
        } catch (\Throwable) {
            // an unparseable date is simply ignored
        }

        return [$start, $end];
    }

    /** Human-readable chips for the filters in use, each with the URL that removes it. */
    private function activeFilters(Request $request, $logUsers): array
    {
        $periods = ['today' => 'Today', 'yesterday' => 'Yesterday', 'last_7' => 'Last 7 days', 'this_week' => 'This week', 'this_month' => 'This month', 'last_month' => 'Last month', 'this_year' => 'This year'];
        $labels = [
            'search'     => fn ($v) => "“{$v}”",
            'event_type' => fn ($v) => "Event: {$v}",
            'role'       => fn ($v) => 'Role: ' . ($v === 'guest' ? 'Guest' : User::roleLabel($v)),
            'user_id'    => fn ($v) => 'User: ' . ($logUsers->firstWhere('id', (int) $v)?->name ?? "#{$v}"),
            'traffic'    => fn ($v) => $v === 'bots' ? 'Bots only' : 'Real users only',
            'source'     => fn ($v) => $v === 'audit' ? 'Admin actions only' : 'Site activity only',
            'period'     => fn ($v) => $periods[$v] ?? null,
            'from'       => fn ($v) => "From {$v}",
            'to'         => fn ($v) => "To {$v}",
        ];
        $chips = [];
        foreach ($labels as $key => $label) {
            if ($request->filled($key) && ($text = $label($request->input($key)))) {
                $chips[] = ['label' => $text, 'remove' => $request->fullUrlWithQuery([$key => null, 'page' => null])];
            }
        }

        return $chips;
    }

    /**
     * City/country for this page's IPs (city level only — never a street address). Only what's
     * already cached is used for this page; IPs not looked up yet are fetched from ip-api.com's
     * free batch endpoint AFTER the response has been sent and cached for a month, so they show
     * on the next load — an outside service never makes this page wait.
     *
     * @param  \Illuminate\Support\Collection<int, string>  $ips
     * @return array<string, string> ip => "City, Country"
     */
    private function batchIpLocations($ips): array
    {
        $result = [];
        $uncached = [];

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                continue; // private/reserved/loopback has no public geolocation
            }
            $cached = Cache::get("ip_location:{$ip}", '__miss__');
            if ($cached === '__miss__') {
                $uncached[] = $ip;
            } elseif ($cached !== null) {
                $result[$ip] = $cached;
            }
        }

        if ($uncached) {
            after_response(function () use ($uncached) {
                $response = Http::timeout(5)->post('http://ip-api.com/batch?fields=status,city,country,query', array_values(array_slice($uncached, 0, 100)));
                if (!$response->ok()) {
                    return;
                }
                foreach ($response->json() ?: [] as $entry) {
                    if (!($ip = $entry['query'] ?? null)) {
                        continue;
                    }
                    $location = ($entry['status'] ?? null) === 'success'
                        ? (collect([$entry['city'] ?? null, $entry['country'] ?? null])->filter()->implode(', ') ?: null)
                        : null;
                    Cache::put("ip_location:{$ip}", $location, now()->addMonth());
                }
            });
        }

        return $result;
    }
}
