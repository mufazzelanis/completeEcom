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
                'activity_logs.event as event_type',
                'activity_logs.description',
                'activity_logs.subject_type',
                'activity_logs.subject_id',
                'activity_logs.ip_address',
                'activity_logs.device',
                'activity_logs.browser',
                'activity_logs.platform',
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
                'audit_logs.action as event_type',
                'audit_logs.description',
                'audit_logs.model_type as subject_type',
                'audit_logs.model_id as subject_id',
                'audit_logs.ip_address',
                DB::raw('NULL as device'),
                DB::raw('NULL as browser'),
                DB::raw('NULL as platform'),
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

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        $eventTypes = DB::query()->fromSub($this->baseUnion(), 'logs')
            ->distinct()->orderBy('event_type')->pluck('event_type');

        $logUsers = User::whereIn('id', DB::table('activity_logs')->distinct()->pluck('user_id')->filter()
            ->merge(DB::table('audit_logs')->distinct()->pluck('user_id')->filter()))
            ->orderBy('name')->get();

        $today = now()->startOfDay();
        $stats = [
            'logins_today'        => ActivityLog::where('event', 'login')->where('created_at', '>=', $today)->count(),
            'product_views_today' => ActivityLog::where('event', 'product.view')->where('created_at', '>=', $today)->count(),
            'cart_adds_today'     => ActivityLog::where('event', 'cart.add')->where('created_at', '>=', $today)->count(),
            'order_views_today'   => ActivityLog::where('event', 'order.view')->where('created_at', '>=', $today)->count(),
        ];

        return view('admin.activity-logs.index', compact('logs', 'eventTypes', 'logUsers', 'stats'));
    }
}
