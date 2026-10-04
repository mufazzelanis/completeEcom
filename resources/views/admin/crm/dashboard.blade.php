@extends('layouts.admin')
@section('title', 'CRM')

@php use App\Support\CrmUi as Ui; @endphp

@section('content')
@include('admin.crm._tabs')

{{-- KPI strip --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4 mb-5">
    @php
        $cards = [
            ['Contacts', number_format($kpis['contacts']), $kpis['customers'] . ' have ordered', 'indigo', route('admin.crm.contacts.index')],
            ['New (30 days)', number_format($kpis['new_30']), 'new people in CRM', 'green', route('admin.crm.contacts.index', ['sort' => 'recent'])],
            ['Avg lifetime value', Ui::money($kpis['ltv']), $kpis['repeat_rate'] . '% buy again', 'orange', route('admin.crm.analytics')],
            ['Revenue at risk', Ui::money($kpis['revenue_at_risk']), $kpis['at_risk'] . ' slipping away', 'red', route('admin.crm.contacts.index', ['lifecycle_stage' => 'at_risk'])],
            ['VIP customers', number_format($kpis['vip']), 'flagged or high spend', 'amber', route('admin.crm.contacts.index', ['vip' => 1])],
            ['Open leads', number_format($kpis['open_leads']), Ui::money($kpis['pipeline']) . ' pipeline', 'purple', route('admin.crm.leads.index')],
            ['Weighted pipeline', Ui::money($kpis['weighted']), 'probability-adjusted', 'teal', route('admin.crm.leads.index')],
            ['Tasks due', $kpis['tasks_overdue'] + $kpis['tasks_today'], $kpis['tasks_overdue'] . ' overdue · ' . $kpis['tasks_today'] . ' today', $kpis['tasks_overdue'] ? 'red' : 'blue', route('admin.crm.tasks.index', ['view' => $kpis['tasks_overdue'] ? 'overdue' : 'today'])],
        ];
    @endphp
    @foreach($cards as [$label, $value, $sub, $color, $href])
        <a href="{{ $href }}" class="bg-white rounded-2xl shadow-sm p-4 hover:shadow-md transition group">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</span>
                <span class="w-2 h-2 rounded-full {{ Ui::dot($color) }}"></span>
            </div>
            <div class="mt-2 text-2xl font-bold text-gray-800 group-hover:text-orange-600 transition">{{ $value }}</div>
            <div class="text-xs text-gray-400 mt-0.5 truncate">{{ $sub }}</div>
        </a>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-5">
    <div class="bg-white rounded-2xl shadow-sm p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800">Contact growth <span class="text-xs font-normal text-gray-400">· last 12 weeks</span></h3>
        </div>
        <div class="h-56"><canvas id="growthChart"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Customer lifecycle</h3>
        <div class="h-40"><canvas id="lifecycleChart"></canvas></div>
        <div class="mt-3 space-y-1.5">
            @foreach(\App\Models\Crm\CrmContact::LIFECYCLE as $key => $meta)
                <a href="{{ route('admin.crm.contacts.index', ['lifecycle_stage' => $key]) }}" class="flex items-center justify-between text-sm hover:bg-gray-50 rounded px-1.5 py-0.5">
                    <span class="flex items-center gap-2 text-gray-600"><span class="w-2.5 h-2.5 rounded-full {{ Ui::dot($meta['color']) }}"></span>{{ $meta['label'] }}</span>
                    <span class="font-medium text-gray-800">{{ number_format($lifecycle[$key]->c ?? 0) }}</span>
                </a>
            @endforeach
        </div>
    </div>
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-5">
    {{-- RFM --}}
    <div class="bg-white rounded-2xl shadow-sm p-5 lg:col-span-2">
        <div class="flex items-center justify-between mb-1">
            <h3 class="font-semibold text-gray-800">RFM segments</h3>
            <a href="{{ route('admin.crm.analytics') }}" class="text-xs text-indigo-600 hover:underline">Heat-map →</a>
        </div>
        <p class="text-xs text-gray-400 mb-3">Customers scored 1-5 on Recency, Frequency and Monetary value. Click a segment to see who is in it.</p>
        @php $maxV = max(1, (float) $rfm->max('v')); @endphp
        <div class="space-y-2">
            @forelse($rfm as $row)
                @php $info = \App\Services\Crm\CrmMetrics::SEGMENT_INFO[$row->rfm_segment] ?? ['color' => 'gray', 'tip' => '']; @endphp
                <a href="{{ route('admin.crm.contacts.index', ['rfm_segment' => $row->rfm_segment]) }}" class="block group" title="{{ $info['tip'] }}">
                    <div class="flex items-center justify-between text-sm mb-0.5">
                        <span class="font-medium text-gray-700 group-hover:text-orange-600">{{ $row->rfm_segment }}</span>
                        <span class="text-xs text-gray-500">{{ $row->c }} · {{ Ui::money($row->v) }}</span>
                    </div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full rounded-full {{ Ui::dot($info['color']) }}" style="width: {{ max(3, $row->v / $maxV * 100) }}%"></div></div>
                </a>
            @empty
                <p class="text-sm text-gray-400 py-6 text-center">No customers with orders yet.</p>
            @endforelse
        </div>
    </div>

    {{-- My tasks --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex items-center justify-between mb-3">
            <h3 class="font-semibold text-gray-800">My tasks</h3>
            <a href="{{ route('admin.crm.tasks.index') }}" class="text-xs text-indigo-600 hover:underline">All →</a>
        </div>
        <div class="space-y-2">
            @forelse($myTasks as $t)
                <div class="flex items-start gap-2 text-sm">
                    <span class="mt-1.5 w-2 h-2 rounded-full flex-shrink-0 {{ $t->isOverdue() ? 'bg-red-500' : 'bg-blue-500' }}"></span>
                    <div class="min-w-0">
                        <div class="text-gray-700 truncate">{{ $t->title }}</div>
                        <div class="text-xs {{ $t->isOverdue() ? 'text-red-500' : 'text-gray-400' }}">
                            {{ $t->due_at ? $t->due_at->format('d M, h:i A') : 'No due date' }}@if($t->contact) · {{ $t->contact->name }}@endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-6 text-center">Nothing assigned to you.</p>
            @endforelse
        </div>
    </div>
</div>

<div class="grid md:grid-cols-2 xl:grid-cols-4 gap-5 mb-5">
    @php
        $lists = [
            ['Top customers', $topCustomers, fn ($c) => Ui::money($c->total_spent), fn ($c) => $c->orders_count . ' orders', 'text-gray-800'],
            ['Needs a win-back call', $atRisk, fn ($c) => $c->churn_risk . '% risk', fn ($c) => Ui::money($c->total_spent) . ' lifetime', 'text-red-600'],
            ['Likely to reorder soon', $reorderSoon, fn ($c) => optional($c->predicted_next_order_on)->format('d M'), fn ($c) => 'every ~' . $c->avg_days_between_orders . ' days', 'text-green-600'],
            ['Birthdays (14 days)', $birthdays, fn ($c) => $c->next_birthday->format('d M'), fn ($c) => $c->next_birthday->isToday() ? 'Today 🎂' : 'in ' . (int) now()->startOfDay()->diffInDays($c->next_birthday) . ' days', 'text-pink-600'],
        ];
    @endphp
    @foreach($lists as [$title, $rows, $right, $sub, $rightClass])
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-3">{{ $title }}</h3>
            <div class="space-y-2.5">
                @forelse($rows as $c)
                    <a href="{{ route('admin.crm.contacts.show', $c) }}" class="flex items-center gap-2.5 hover:bg-gray-50 rounded-lg p-1 -m-1 transition">
                        <span class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 {{ Ui::avatar($c->name) }}">{{ $c->initials }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm text-gray-700 truncate">{{ $c->name }}</span>
                            <span class="block text-xs text-gray-400 truncate">{{ $sub($c) }}</span>
                        </span>
                        <span class="text-sm font-semibold {{ $rightClass }}">{{ $right($c) }}</span>
                    </a>
                @empty
                    <p class="text-sm text-gray-400 py-4 text-center">Nobody here right now.</p>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

<div class="bg-white rounded-2xl shadow-sm p-5">
    <h3 class="font-semibold text-gray-800 mb-3">Recent team activity</h3>
    <div class="divide-y divide-gray-50">
        @forelse($recent as $a)
            <div class="py-2.5 flex items-start gap-3 text-sm">
                <span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 mt-0.5">{{ $a->type }}</span>
                <div class="min-w-0 flex-1">
                    <a href="{{ route('admin.crm.contacts.show', $a->contact_id) }}" class="font-medium text-gray-700 hover:text-orange-600">{{ $a->contact?->name }}</a>
                    <span class="text-gray-500"> — {{ \Illuminate\Support\Str::limit($a->subject ?: $a->body, 90) }}</span>
                </div>
                <span class="text-xs text-gray-400 whitespace-nowrap">{{ $a->user?->name }} · {{ $a->occurred_at->diffForHumans() }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400 py-4 text-center">No calls or notes logged yet. Open a customer and log your first interaction.</p>
        @endforelse
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;
    var dark = document.documentElement.classList.contains('dark');
    var grid = dark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)';
    var tick = dark ? '#9ca3af' : '#6b7280';
    new Chart(document.getElementById('growthChart'), {
        type: 'line',
        data: { labels: @json($growth->pluck('label')), datasets: [{ label: 'New contacts', data: @json($growth->pluck('n')), borderColor: '#ea580c', backgroundColor: 'rgba(234,88,12,.12)', fill: true, tension: .35, pointRadius: 3 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0, color: tick }, grid: { color: grid } }, x: { ticks: { color: tick }, grid: { display: false } } } }
    });
    var lc = @json(collect(\App\Models\Crm\CrmContact::LIFECYCLE)->keys()->map(fn ($k) => (int) ($lifecycle[$k]->c ?? 0)));
    new Chart(document.getElementById('lifecycleChart'), {
        type: 'doughnut',
        data: { labels: @json(collect(\App\Models\Crm\CrmContact::LIFECYCLE)->pluck('label')->values()), datasets: [{ data: lc, backgroundColor: ['#9ca3af', '#3b82f6', '#22c55e', '#f59e0b', '#ef4444'], borderWidth: 0 }] },
        options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } }
    });
});
</script>
@endsection
