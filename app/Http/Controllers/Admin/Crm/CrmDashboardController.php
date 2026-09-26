<?php

namespace App\Http\Controllers\Admin\Crm;

use App\Http\Controllers\Controller;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmContact;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmTag;
use App\Models\Crm\CrmTask;
use App\Models\Setting;
use App\Services\Crm\CrmMetrics;
use App\Services\Crm\CrmSync;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends Controller
{
    public function index(Request $request)
    {
        $s = CrmMetrics::settings();
        $lifecycle = CrmContact::query()->select('lifecycle_stage', DB::raw('COUNT(*) c'), DB::raw('SUM(total_spent) v'))->groupBy('lifecycle_stage')->get()->keyBy('lifecycle_stage');
        $customers = CrmContact::where('orders_count', '>', 0);

        $open = CrmLead::whereNotIn('stage', ['won', 'lost']);
        $weighted = 0;
        foreach ((clone $open)->select('stage', DB::raw('SUM(value) v'))->groupBy('stage')->get() as $row) {
            $weighted += (float) $row->v * (CrmLead::STAGES[$row->stage]['probability'] ?? 0) / 100;
        }

        $kpis = [
            'contacts' => CrmContact::count(),
            'customers' => (clone $customers)->count(),
            'new_30' => CrmContact::where('created_at', '>=', now()->subDays(30))->count(),
            'vip' => CrmContact::where('is_vip', true)->count(),
            'ltv' => (float) (clone $customers)->avg('total_spent'),
            'repeat_rate' => ($c = (clone $customers)->count()) ? round(CrmContact::where('orders_count', '>=', 2)->count() / $c * 100) : 0,
            'revenue_at_risk' => (float) CrmContact::where('lifecycle_stage', 'at_risk')->sum('total_spent'),
            'at_risk' => (int) ($lifecycle['at_risk']->c ?? 0),
            'open_leads' => (clone $open)->count(),
            'pipeline' => (float) (clone $open)->sum('value'),
            'weighted' => round($weighted),
            'tasks_overdue' => CrmTask::open()->where('due_at', '<', now())->count(),
            'tasks_today' => CrmTask::open()->whereBetween('due_at', [now(), now()->endOfDay()])->count(),
        ];

        // New contacts per week, last 12 weeks
        $weeks = collect(range(11, 0))->map(fn ($i) => now()->startOfWeek()->subWeeks($i));
        $newByWeek = CrmContact::where('created_at', '>=', $weeks->first())
            ->get(['created_at'])
            ->groupBy(fn ($c) => $c->created_at->copy()->startOfWeek()->toDateString());
        $growth = $weeks->map(fn ($w) => ['label' => $w->format('d M'), 'n' => $newByWeek->get($w->toDateString(), collect())->count()]);

        $rfm = CrmContact::whereNotNull('rfm_segment')->select('rfm_segment', DB::raw('COUNT(*) c'), DB::raw('SUM(total_spent) v'))->groupBy('rfm_segment')->orderByDesc('v')->get();

        return view('admin.crm.dashboard', [
            'kpis' => $kpis,
            'lifecycle' => $lifecycle,
            'growth' => $growth,
            'rfm' => $rfm,
            'topCustomers' => CrmContact::where('orders_count', '>', 0)->orderByDesc('total_spent')->limit(6)->get(),
            'atRisk' => CrmContact::whereIn('lifecycle_stage', ['at_risk', 'active'])->where('churn_risk', '>=', 60)->where('status', 'active')->orderByDesc('total_spent')->limit(6)->get(),
            'reorderSoon' => CrmContact::whereBetween('predicted_next_order_on', [now()->toDateString(), now()->addDays(10)->toDateString()])->where('status', 'active')->orderBy('predicted_next_order_on')->limit(6)->get(),
            'birthdays' => CrmContact::whereNotNull('birthday')->get(['id', 'name', 'birthday', 'phone'])
                ->map(function ($c) {
                    $next = $c->birthday->copy()->year(now()->year);
                    if ($next->lt(now()->startOfDay())) {
                        $next->addYear();
                    }
                    $c->next_birthday = $next;

                    return $c;
                })->filter(fn ($c) => $c->next_birthday->lte(now()->addDays(14)))->sortBy('next_birthday')->take(6),
            'myTasks' => CrmTask::open()->where('assigned_to', $request->user()->id)->with('contact:id,name')->orderByRaw('due_at IS NULL')->orderBy('due_at')->limit(6)->get(),
            'recent' => CrmActivity::with(['contact:id,name', 'user:id,name'])->whereNotNull('contact_id')->where(fn ($q) => $q->whereNull('meta')->orWhere('meta', 'not like', '%system%'))->latest('occurred_at')->limit(8)->get(),
            'settings' => $s,
        ]);
    }

    public function analytics()
    {
        $start = now()->subMonths(11)->startOfMonth();

        // Revenue split: first-ever purchase vs repeat purchase, per month. An order counts as "new"
        // when it is the customer's earliest valid order.
        $orders = DB::table('orders')
            ->whereNotNull('crm_contact_id')->whereNotIn('status', CrmMetrics::EXCLUDED_STATUSES)
            ->orderBy('created_at')->get(['id', 'crm_contact_id', 'total', 'created_at']);
        $seen = [];
        $monthly = [];
        foreach ($orders as $o) {
            $m = substr($o->created_at, 0, 7);
            $isNew = ! isset($seen[$o->crm_contact_id]);
            $seen[$o->crm_contact_id] = true;
            $monthly[$m]['new'] = ($monthly[$m]['new'] ?? 0) + ($isNew ? (float) $o->total : 0);
            $monthly[$m]['repeat'] = ($monthly[$m]['repeat'] ?? 0) + ($isNew ? 0 : (float) $o->total);
            $monthly[$m]['new_customers'] = ($monthly[$m]['new_customers'] ?? 0) + ($isNew ? 1 : 0);
        }
        $months = collect(range(0, 11))->map(fn ($i) => $start->copy()->addMonths($i));
        $revenue = $months->map(fn ($m) => [
            'label' => $m->format('M y'),
            'new' => round($monthly[$m->format('Y-m')]['new'] ?? 0),
            'repeat' => round($monthly[$m->format('Y-m')]['repeat'] ?? 0),
            'new_customers' => $monthly[$m->format('Y-m')]['new_customers'] ?? 0,
        ]);

        // RFM heat-map: recency (rows) × frequency (columns)
        $grid = CrmContact::whereNotNull('rfm_r')->select('rfm_r', 'rfm_f', DB::raw('COUNT(*) c'))->groupBy('rfm_r', 'rfm_f')->get();
        $heat = [];
        foreach ($grid as $g) {
            $heat[$g->rfm_r][$g->rfm_f] = (int) $g->c;
        }

        $customers = CrmContact::where('orders_count', '>', 0);
        $bySource = CrmContact::select('source', DB::raw('COUNT(*) c'), DB::raw('SUM(total_spent) v'))->groupBy('source')->orderByDesc('c')->get();
        $byCity = (clone $customers)->whereNotNull('city')->where('city', '!=', '')->select('city', DB::raw('COUNT(*) c'), DB::raw('SUM(total_spent) v'))->groupBy('city')->orderByDesc('v')->limit(8)->get();

        // Order-count distribution → how many people stop after one purchase
        $dist = (clone $customers)->select(DB::raw('LEAST(orders_count, 5) as k'), DB::raw('COUNT(*) c'))->groupBy('k')->orderBy('k')->pluck('c', 'k');

        $leadStats = [
            'by_stage' => CrmLead::select('stage', DB::raw('COUNT(*) c'), DB::raw('SUM(value) v'))->groupBy('stage')->get()->keyBy('stage'),
            'by_source' => CrmLead::select('source', DB::raw('COUNT(*) c'), DB::raw("SUM(CASE WHEN stage = 'won' THEN 1 ELSE 0 END) won"))->groupBy('source')->get(),
            'lost_reasons' => CrmLead::where('stage', 'lost')->whereNotNull('lost_reason')->select('lost_reason', DB::raw('COUNT(*) c'))->groupBy('lost_reason')->orderByDesc('c')->limit(6)->get(),
        ];
        $closed = CrmLead::whereIn('stage', ['won', 'lost'])->count();
        $leadStats['win_rate'] = $closed ? round(CrmLead::where('stage', 'won')->count() / $closed * 100) : null;

        return view('admin.crm.analytics', [
            'revenue' => $revenue,
            'heat' => $heat,
            'bySource' => $bySource,
            'byCity' => $byCity,
            'dist' => $dist,
            'leadStats' => $leadStats,
            'summary' => [
                'customers' => (clone $customers)->count(),
                'avg_ltv' => (float) (clone $customers)->avg('total_spent'),
                'avg_orders' => round((float) (clone $customers)->avg('orders_count'), 1),
                'avg_gap' => (int) round((float) CrmContact::whereNotNull('avg_days_between_orders')->avg('avg_days_between_orders')),
                'top10_share' => $this->topShare(),
            ],
        ]);
    }

    private function topShare(): ?int
    {
        $total = (float) CrmContact::sum('total_spent');
        $n = CrmContact::where('orders_count', '>', 0)->count();
        if ($total <= 0 || $n === 0) {
            return null;
        }
        $top = (float) CrmContact::where('orders_count', '>', 0)->orderByDesc('total_spent')->limit(max(1, (int) ceil($n * 0.1)))->get(['total_spent'])->sum('total_spent');

        return (int) round($top / $total * 100);
    }

    public function settings()
    {
        return view('admin.crm.settings', [
            'settings' => CrmMetrics::settings() + ['auto_tasks' => (bool) Setting::get('crm_auto_tasks', 1)],
            'tags' => CrmTag::withCount('contacts')->orderBy('name')->get(),
            'colors' => array_merge(CrmTag::COLORS),
            'lastRun' => CrmContact::max('metrics_refreshed_at'),
        ]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'active_days' => 'required|integer|min:7|max:365',
            'lost_days' => 'required|integer|min:30|max:1000|gt:active_days',
            'vip_spend' => 'required|numeric|min:0|max:100000000',
        ]);
        Setting::setMany([
            'crm_active_days' => $data['active_days'],
            'crm_lost_days' => $data['lost_days'],
            'crm_vip_spend' => $data['vip_spend'],
            'crm_auto_tasks' => $request->boolean('auto_tasks') ? 1 : 0,
        ], 'crm');

        app(CrmMetrics::class)->refreshAll();

        return back()->with('success', 'CRM settings saved and every customer recalculated.');
    }

    public function sync(CrmSync $sync)
    {
        $st = $sync->run();

        return back()->with('success', "Sync done — {$st['orders']} orders linked, {$st['users']} accounts and {$st['subscribers']} subscribers added, {$st['refreshed']} contacts recalculated.");
    }

    public function saveTag(Request $request, ?CrmTag $tag = null)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', \Illuminate\Validation\Rule::unique('crm_tags', 'name')->ignore($tag?->id)],
            'color' => ['required', \Illuminate\Validation\Rule::in(CrmTag::COLORS)],
        ]);
        $tag ? $tag->update($data) : CrmTag::create($data);

        return back()->with('success', 'Tag saved.');
    }

    public function deleteTag(CrmTag $tag)
    {
        $tag->delete();

        return back()->with('success', 'Tag deleted.');
    }
}
