@extends('layouts.admin')
@section('title', 'CRM · Analytics')

@php
    use App\Support\CrmUi as Ui;
    $rNames = [5 => 'Very recent', 4 => 'Recent', 3 => 'Middling', 2 => 'Lapsing', 1 => 'Long gone'];
    $heatMax = max(1, collect($heat)->flatten()->max() ?? 1);
    $stageOrder = \App\Models\Crm\CrmLead::STAGES;
@endphp

@section('content')
@include('admin.crm._tabs')

<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
    @foreach([
        ['Customers', number_format($summary['customers'])],
        ['Avg lifetime value', Ui::money($summary['avg_ltv'])],
        ['Avg orders / customer', $summary['avg_orders']],
        ['Typical reorder gap', $summary['avg_gap'] ? $summary['avg_gap'] . ' days' : '—'],
        ['Top 10% share of revenue', $summary['top10_share'] !== null ? $summary['top10_share'] . '%' : '—'],
    ] as [$l, $v])
        <div class="bg-white rounded-2xl shadow-sm p-4"><div class="text-[11px] uppercase tracking-wide text-gray-400 font-medium">{{ $l }}</div><div class="text-xl font-bold text-gray-800 mt-1">{{ $v }}</div></div>
    @endforeach
</div>

<div class="grid lg:grid-cols-3 gap-5 mb-5">
    <div class="bg-white rounded-2xl shadow-sm p-5 lg:col-span-2">
        <h3 class="font-semibold text-gray-800 mb-1">Revenue: new vs returning customers</h3>
        <p class="text-xs text-gray-400 mb-3">A healthy shop grows the returning (green) share over time — it's cheaper than finding new buyers.</p>
        <div class="h-64"><canvas id="revChart"></canvas></div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-semibold text-gray-800 mb-1">How many orders per customer</h3>
        <p class="text-xs text-gray-400 mb-3">The big first bar is your second-purchase opportunity.</p>
        <div class="h-64"><canvas id="distChart"></canvas></div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm p-5 mb-5 overflow-x-auto">
    <h3 class="font-semibold text-gray-800 mb-1">RFM heat-map</h3>
    <p class="text-xs text-gray-400 mb-4">Rows: how recently they bought. Columns: how often. Darker = more customers. Top-right is your best people; bottom-left has drifted away.</p>
    <table class="text-sm mx-auto">
        <thead><tr><th></th>@foreach(range(1, 5) as $f)<th class="px-2 pb-2 text-xs font-medium text-gray-500">Freq {{ $f }}</th>@endforeach</tr></thead>
        <tbody>
            @foreach([5, 4, 3, 2, 1] as $r)
                <tr>
                    <th class="pr-3 text-right text-xs font-medium text-gray-500 whitespace-nowrap">{{ $rNames[$r] }} <span class="text-gray-300">R{{ $r }}</span></th>
                    @foreach(range(1, 5) as $f)
                        @php $n = $heat[$r][$f] ?? 0; $alpha = $n ? 0.15 + 0.85 * ($n / $heatMax) : 0; @endphp
                        <td class="p-1"><a href="{{ route('admin.crm.contacts.index', ['sort' => 'spent']) }}" class="w-20 h-12 rounded-lg flex items-center justify-center font-semibold {{ $alpha > .55 ? 'text-white' : 'text-gray-600' }}" style="background: rgba(234,88,12,{{ $alpha ?: 0.04 }})" title="Recency {{ $r }} · Frequency {{ $f }}">{{ $n ?: '' }}</a></td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="grid lg:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Where contacts come from</h3>
        <div class="space-y-2.5">
            @php $maxC = max(1, (int) $bySource->max('c')); @endphp
            @forelse($bySource as $s)
                <div>
                    <div class="flex justify-between text-sm mb-0.5"><span class="text-gray-700 capitalize">{{ str_replace('_', ' ', $s->source) }}</span><span class="text-xs text-gray-500">{{ $s->c }} people · {{ Ui::money($s->v) }}</span></div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full rounded-full bg-indigo-500" style="width: {{ max(3, $s->c / $maxC * 100) }}%"></div></div>
                </div>
            @empty<p class="text-sm text-gray-400">No data.</p>@endforelse
        </div>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <h3 class="font-semibold text-gray-800 mb-3">Top cities by revenue</h3>
        <div class="space-y-2.5">
            @php $maxV = max(1, (float) $byCity->max('v')); @endphp
            @forelse($byCity as $c)
                <div>
                    <div class="flex justify-between text-sm mb-0.5"><a href="{{ route('admin.crm.contacts.index', ['city' => $c->city]) }}" class="text-gray-700 hover:text-orange-600">{{ $c->city }}</a><span class="text-xs text-gray-500">{{ $c->c }} customers · {{ Ui::money($c->v) }}</span></div>
                    <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full rounded-full bg-teal-500" style="width: {{ max(3, $c->v / $maxV * 100) }}%"></div></div>
                </div>
            @empty<p class="text-sm text-gray-400">No city data on orders yet.</p>@endforelse
        </div>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm p-5">
    <div class="flex items-center justify-between mb-3"><h3 class="font-semibold text-gray-800">Lead pipeline</h3>@if($leadStats['win_rate'] !== null)<span class="text-sm text-gray-500">Win rate <b class="text-gray-800">{{ $leadStats['win_rate'] }}%</b></span>@endif</div>
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-2 mb-4">
        @foreach($stageOrder as $key => $meta)
            <a href="{{ route('admin.crm.leads.index') }}" class="rounded-xl p-3 text-center {{ Ui::badge($meta['color']) }}"><div class="text-xl font-bold">{{ $leadStats['by_stage'][$key]->c ?? 0 }}</div><div class="text-[11px] font-medium">{{ $meta['label'] }}</div><div class="text-[10px] opacity-70">{{ Ui::money($leadStats['by_stage'][$key]->v ?? 0) }}</div></a>
        @endforeach
    </div>
    <div class="grid md:grid-cols-2 gap-5 text-sm">
        <div><div class="text-xs uppercase tracking-wide text-gray-400 mb-1.5">Won by source</div>
            @forelse($leadStats['by_source'] as $s)<div class="flex justify-between py-0.5"><span class="text-gray-600 capitalize">{{ str_replace('_', ' ', $s->source) }}</span><span class="text-gray-500">{{ $s->won }} won / {{ $s->c }}</span></div>@empty<p class="text-gray-400">No leads yet.</p>@endforelse</div>
        <div><div class="text-xs uppercase tracking-wide text-gray-400 mb-1.5">Why leads are lost</div>
            @forelse($leadStats['lost_reasons'] as $s)<div class="flex justify-between py-0.5"><span class="text-gray-600">{{ $s->lost_reason }}</span><span class="text-gray-500">{{ $s->c }}</span></div>@empty<p class="text-gray-400">No lost leads recorded.</p>@endforelse</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;
    var dark = document.documentElement.classList.contains('dark');
    var grid = dark ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)', tick = dark ? '#9ca3af' : '#6b7280';
    var rev = @json($revenue);
    new Chart(document.getElementById('revChart'), {
        type: 'bar',
        data: { labels: rev.map(function (x) { return x.label; }), datasets: [
            { label: 'New customers (৳)', data: rev.map(function (x) { return x.new; }), backgroundColor: '#f97316', borderRadius: 4 },
            { label: 'Returning customers (৳)', data: rev.map(function (x) { return x.repeat; }), backgroundColor: '#22c55e', borderRadius: 4 } ] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: tick, boxWidth: 12 } } }, scales: { x: { stacked: true, ticks: { color: tick }, grid: { display: false } }, y: { stacked: true, beginAtZero: true, ticks: { color: tick }, grid: { color: grid } } } }
    });
    var dist = @json($dist);
    var keys = [1, 2, 3, 4, 5];
    new Chart(document.getElementById('distChart'), {
        type: 'bar',
        data: { labels: ['1 order', '2', '3', '4', '5+'], datasets: [{ data: keys.map(function (k) { return dist[k] || 0; }), backgroundColor: ['#f97316', '#fb923c', '#fdba74', '#fed7aa', '#ffedd5'], borderRadius: 6 }] },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { ticks: { color: tick }, grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0, color: tick }, grid: { color: grid } } } }
    });
});
</script>
@endsection
