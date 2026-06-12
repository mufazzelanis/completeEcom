@extends('admin.reports.layout')
@section('report-title', 'Payment Report')

@section('report-content')

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form action="{{ route('admin.reports.payments') }}" method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <div class="flex gap-2 ml-auto">
            @foreach([['30d','30 Days'],['90d','90 Days'],['ytd','This Year']] as [$p,$l])
            <a href="{{ route('admin.reports.payments', ['from' => match($p){ '30d'=>now()->subDays(29)->toDateString(), '90d'=>now()->subDays(89)->toDateString(), 'ytd'=>now()->startOfYear()->toDateString() }, 'to' => now()->toDateString()]) }}"
               class="px-3 py-2 text-xs rounded-xl border border-gray-200 text-gray-600 hover:bg-gray-50 transition">{{ $l }}</a>
            @endforeach
        </div>
    </form>
</div>

{{-- Summary Cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php
    $cards = [
        ['Total Collected', '$'.number_format($summary->total_collected ?? 0,2), 'text-gray-800', 'bg-gray-50'],
        ['Paid', '$'.number_format($summary->paid_amount ?? 0,2), 'text-green-700', 'bg-green-50'],
        ['Pending', '$'.number_format($summary->pending_amount ?? 0,2), 'text-orange-700', 'bg-orange-50'],
        ['Failed / Refunded', '$'.number_format(($summary->failed_amount ?? 0)+($summary->refunded_amount ?? 0),2), 'text-red-700', 'bg-red-50'],
    ];
    @endphp
    @foreach($cards as [$label,$value,$tc,$bg])
    <div class="bg-white rounded-2xl shadow-sm p-5 border border-gray-100">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $tc }}">{{ $value }}</p>
    </div>
    @endforeach
</div>

<div class="grid grid-cols-3 gap-6 mb-6">
    {{-- Monthly Method Trend --}}
    <div class="col-span-2 bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Revenue by Payment Method — Last 12 Months</h3>
        <canvas id="methodTrendChart" height="130"></canvas>
    </div>

    {{-- Payment Status Pie --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h3 class="font-semibold text-gray-800 mb-4">Payment Status Split</h3>
        <canvas id="statusPieChart" height="180"></canvas>
        <div class="mt-4 space-y-1">
            @foreach($byStatus as $s)
            @php
            $sc = ['paid'=>'bg-green-400','pending'=>'bg-orange-400','failed'=>'bg-red-400','refunded'=>'bg-gray-400'];
            @endphp
            <div class="flex items-center justify-between text-sm">
                <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full {{ $sc[$s->payment_status] ?? 'bg-gray-300' }}"></span><span class="capitalize text-gray-600">{{ $s->payment_status }}</span></span>
                <span class="font-medium text-gray-800">${{ number_format($s->amount,0) }}</span>
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Payment Method Breakdown Table --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Payment Method Breakdown</h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left">Method</th>
                    <th class="px-6 py-3 text-right">Total Orders</th>
                    <th class="px-6 py-3 text-right">Total Revenue</th>
                    <th class="px-6 py-3 text-right">Paid</th>
                    <th class="px-6 py-3 text-right">Pending</th>
                    <th class="px-6 py-3 text-right">Failed</th>
                    <th class="px-6 py-3 text-right">Success Rate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($byMethod as $m)
                @php $rate = $m->count > 0 ? round(($m->paid_count / $m->count) * 100, 1) : 0; @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-3 font-medium text-gray-700 uppercase">{{ $m->payment_method }}</td>
                    <td class="px-6 py-3 text-right text-gray-600">{{ number_format($m->count) }}</td>
                    <td class="px-6 py-3 text-right font-semibold text-gray-800">${{ number_format($m->revenue,2) }}</td>
                    <td class="px-6 py-3 text-right text-green-600">{{ number_format($m->paid_count) }}</td>
                    <td class="px-6 py-3 text-right text-orange-500">{{ number_format($m->pending_count) }}</td>
                    <td class="px-6 py-3 text-right text-red-500">{{ number_format($m->failed_count) }}</td>
                    <td class="px-6 py-3 text-right">
                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $rate >= 80 ? 'bg-green-100 text-green-700' : ($rate >= 50 ? 'bg-orange-100 text-orange-700' : 'bg-red-100 text-red-700') }}">{{ $rate }}%</span>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-6 py-10 text-center text-gray-400">No payment data for this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="grid grid-cols-2 gap-6 mb-6">
    {{-- Pending Payments --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Pending Payments</h3>
            <span class="text-xs text-orange-600 font-medium bg-orange-50 px-2 py-1 rounded-full">{{ $pendingPayments->count() }} orders</span>
        </div>
        <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
            @forelse($pendingPayments as $order)
            <div class="px-6 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $order->order_number }}</p>
                    <p class="text-xs text-gray-400">{{ $order->user?->name ?? 'Guest' }} · {{ $order->created_at->diffForHumans() }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-700">${{ number_format($order->total,2) }}</p>
                    <span class="text-xs uppercase text-gray-500">{{ $order->payment_method }}</span>
                </div>
            </div>
            @empty
            <p class="px-6 py-8 text-center text-gray-400 text-sm">No pending payments.</p>
            @endforelse
        </div>
    </div>

    {{-- Failed Payments --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Failed Payments</h3>
            <span class="text-xs text-red-600 font-medium bg-red-50 px-2 py-1 rounded-full">{{ $failedPayments->count() }} orders</span>
        </div>
        <div class="divide-y divide-gray-50 max-h-72 overflow-y-auto">
            @forelse($failedPayments as $order)
            <div class="px-6 py-3 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $order->order_number }}</p>
                    <p class="text-xs text-gray-400">{{ $order->user?->name ?? 'Guest' }} · {{ $order->created_at->diffForHumans() }}</p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-red-600">${{ number_format($order->total,2) }}</p>
                    <span class="text-xs uppercase text-gray-500">{{ $order->payment_method }}</span>
                </div>
            </div>
            @empty
            <p class="px-6 py-8 text-center text-gray-400 text-sm">No failed payments.</p>
            @endforelse
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const monthly = @json($monthlyTrend);
    new Chart(document.getElementById('methodTrendChart'), {
        type: 'bar',
        data: {
            labels: monthly.map(m => m.month),
            datasets: [
                { label: 'COD',   data: monthly.map(m => parseFloat(m.cod)),   backgroundColor: 'rgba(245,158,11,0.75)', borderRadius: 3 },
                { label: 'Card',  data: monthly.map(m => parseFloat(m.card)),  backgroundColor: 'rgba(99,102,241,0.75)', borderRadius: 3 },
                { label: 'bKash', data: monthly.map(m => parseFloat(m.bkash)), backgroundColor: 'rgba(236,72,153,0.75)', borderRadius: 3 },
                { label: 'Nagad', data: monthly.map(m => parseFloat(m.nagad)), backgroundColor: 'rgba(16,185,129,0.75)', borderRadius: 3 },
            ]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size:11 } } } }, scales: { x: { stacked: true, grid: { display:false }, ticks: { font: { size:10 } } }, y: { stacked: true, ticks: { callback: v => '$'+v.toLocaleString(), font: { size:10 } } } } }
    });

    const ps = @json($byStatus);
    const psColors = { paid:'#10b981', pending:'#f59e0b', failed:'#ef4444', refunded:'#6b7280' };
    new Chart(document.getElementById('statusPieChart'), {
        type: 'doughnut',
        data: {
            labels: ps.map(s => s.payment_status.charAt(0).toUpperCase()+s.payment_status.slice(1)),
            datasets: [{ data: ps.map(s => parseFloat(s.amount)), backgroundColor: ps.map(s => psColors[s.payment_status] ?? '#94a3b8'), borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { display:false } }, cutout: '62%' }
    });
});
</script>
@endsection
