@extends('layouts.seller')
@section('title', 'Sales Report')
@section('pageTitle', 'Sales Report')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-bold text-gray-800">Sales Report</h1>
</div>

<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">From</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500">To</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Apply</button>
        <a href="{{ route('seller.reports.download', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}"
           class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-green-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download Excel
        </a>
    </form>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">On Hold</p>
        <p class="text-2xl font-bold text-yellow-600">৳{{ number_format($summary['hold']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Available</p>
        <p class="text-2xl font-bold text-blue-600">৳{{ number_format($summary['available']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Paid Out</p>
        <p class="text-2xl font-bold text-green-600">৳{{ number_format($summary['paid']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Cancelled Items</p>
        <p class="text-2xl font-bold text-red-600">{{ $summary['cancelled'] }}</p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm p-6">
    <h3 class="font-semibold text-gray-800 mb-4">Sales Trend</h3>
    <canvas id="sellerSalesTrendChart" height="100"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const trend = @json($salesTrend);
    new Chart(document.getElementById('sellerSalesTrendChart'), {
        type: 'bar',
        data: {
            labels: trend.map(r => r.period),
            datasets: [{
                label: 'Revenue (৳)',
                data: trend.map(r => parseFloat(r.revenue)),
                backgroundColor: 'rgba(99,102,241,0.7)',
                borderRadius: 4,
            }]
        },
        options: { responsive: true, plugins: { legend: { labels: { font: { size: 11 } } } }, scales: { x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 15 } }, y: { ticks: { callback: v => '৳' + v.toLocaleString(), font: { size: 10 } } } } }
    });
});
</script>
@endsection
