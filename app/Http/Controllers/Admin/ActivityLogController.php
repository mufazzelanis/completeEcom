<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ActivityLogController extends Controller
{
    /**
     * Unifies the two behavioral/security event sources this app records —
     * ActivityLog (logins, logouts, product/order views, cart actions) and
     * AuditLog (admin data-mutation trail) — into one filterable, paginated
     * feed via a SQL UNION ALL, joined to `users` so role/name are available
     * for filtering without a second round-trip per row.
     */
    private function baseUnion()
    {
        $activity = DB::table('activity_logs')
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
                // order.place stores the shipping phone as it was AT THAT ORDER, which stays
                // accurate even if the customer's account phone changes later — preferred over
                // users.phone (the view falls back to that) for exactly this reason.
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

        $audit = DB::table('audit_logs')
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
                // An admin/staff action behind a login screen, never a bot — the
                // concept only applies to the guest-facing traffic in activity_logs.
                DB::raw('0 as is_bot'),
                'audit_logs.created_at',
            ]);

        return $activity->unionAll($audit);
    }

    public function index(Request $request)
    {
        $union = $this->baseUnion();
        $query = DB::query()->fromSub($union, 'logs');

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('event_type')) {
            $query->where('event_type', $request->event_type);
        }

        if ($request->filled('role')) {
            $query->where('user_role', $request->role);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        // Defaults to showing everything (nothing hidden by surprise) — "Humans only"
        // / "Bots only" are an explicit choice via the Traffic filter, backed by the
        // is_bot column computed once at write time (see ActivityLogger::log()).
        if ($request->input('traffic') === 'humans') {
            $query->where('is_bot', false);
        } elseif ($request->input('traffic') === 'bots') {
            $query->where('is_bot', true);
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();
        $ipLocations = $this->batchIpLocations($logs->pluck('ip_address')->filter()->unique());

        $eventTypes = DB::query()->fromSub($this->baseUnion(), 'logs')
            ->distinct()->orderBy('event_type')->pluck('event_type');

        $logUsers = User::whereIn('id', DB::table('activity_logs')->distinct()->pluck('user_id')->filter()
            ->merge(DB::table('audit_logs')->distinct()->pluck('user_id')->filter()))
            ->orderBy('name')->get();

        // now()->startOfDay() used app.timezone (UTC) — "today" started/ended 6 hours off
        // from an admin's actual Bangladesh day, so these counters could show yesterday
        // evening's Dhaka activity as "today", or cut off this morning's. setting('timezone')
        // is the same admin-configurable value every other Dhaka-local date calculation in
        // this app already uses (BlogPostController, TelegramOrderObserver, ...).
        $today = now()->setTimezone(setting('timezone', 'Asia/Dhaka'))->startOfDay()->utc();
        $stats = [
            'logins_today'            => ActivityLog::where('event', 'login')->where('created_at', '>=', $today)->count(),
            'product_views_today'     => ActivityLog::where('event', 'product.view')->where('created_at', '>=', $today)->count(),
            // Split out so the raw number above doesn't quietly include crawler/scraper
            // traffic in what reads as "real people looked at a product today" — a
            // datacenter IP hammering product pages (which is what prompted this) would
            // otherwise inflate that count with zero corresponding carts/orders to match.
            'product_views_today_bot' => ActivityLog::where('event', 'product.view')->where('is_bot', true)->where('created_at', '>=', $today)->count(),
            'cart_adds_today'         => ActivityLog::where('event', 'cart.add')->where('created_at', '>=', $today)->count(),
            'orders_placed_today'     => ActivityLog::where('event', 'order.place')->where('created_at', '>=', $today)->count(),
            'order_views_today'       => ActivityLog::where('event', 'order.view')->where('created_at', '>=', $today)->count(),
        ];

        return view('admin.activity-logs.index', compact('logs', 'eventTypes', 'logUsers', 'stats', 'ipLocations'));
    }

    /**
     * City/country for a batch of IPs, via ip-api.com's free (no API key) batch endpoint
     * (city-level only — never a street address or anything close to it; that level of
     * precision isn't available to anyone outside the ISP itself, not to this site or any
     * other). One request per PAGE of the log (not per row — a naive per-IP lookup across
     * 50 rows could mean 50 outbound requests on every page load), and each result is
     * cached for a month since an IP's rough location essentially never changes for this
     * purpose. A slow or failed lookup just leaves that IP out of the map rather than ever
     * blocking the page — this is a convenience on top of the IP column, not a dependency.
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
                continue; // private/reserved/loopback (e.g. local dev) has no public geolocation
            }
            $cached = \Illuminate\Support\Facades\Cache::get("ip_location:{$ip}", '__miss__');
            if ($cached === '__miss__') {
                $uncached[] = $ip;
            } elseif ($cached !== null) {
                $result[$ip] = $cached;
            }
        }

        if (!empty($uncached)) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(3)
                    ->post('http://ip-api.com/batch?fields=status,city,country,query', array_values($uncached));

                if ($response->ok()) {
                    foreach ($response->json() ?: [] as $entry) {
                        $ip = $entry['query'] ?? null;
                        if (!$ip) continue;
                        $location = ($entry['status'] ?? null) === 'success'
                            ? collect([$entry['city'] ?? null, $entry['country'] ?? null])->filter()->implode(', ') ?: null
                            : null;
                        \Illuminate\Support\Facades\Cache::put("ip_location:{$ip}", $location, now()->addMonth());
                        if ($location) {
                            $result[$ip] = $location;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Geolocation is a nice-to-have on this page, never a reason to break it.
            }
        }

        return $result;
    }
}
