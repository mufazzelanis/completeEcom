@extends('layouts.admin')
@section('title', 'Seller Payments')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Seller Payments</h1>
        <p class="text-sm text-gray-500 mt-0.5">Every seller's total sales and payout position, in one place</p>
    </div>
</div>

{{-- Platform-wide totals --}}
<div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Total Sales</p>
        <p class="text-xl font-bold text-gray-800">৳{{ number_format($totals['total_sales']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Platform Commission</p>
        <p class="text-xl font-bold text-indigo-600">৳{{ number_format($totals['total_commission']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">On Hold</p>
        <p class="text-xl font-bold text-yellow-600">৳{{ number_format($totals['hold']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Available (Owed)</p>
        <p class="text-xl font-bold text-blue-600">৳{{ number_format($totals['available']) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-2">Paid Out</p>
        <p class="text-xl font-bold text-green-600">৳{{ number_format($totals['paid']) }}</p>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap items-center gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search seller name…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="sort_by" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="total_sales" {{ request('sort_by', 'total_sales') === 'total_sales' ? 'selected' : '' }}>Sort: Total Sales</option>
            <option value="available" {{ request('sort_by') === 'available' ? 'selected' : '' }}>Sort: Available Balance</option>
            <option value="paid" {{ request('sort_by') === 'paid' ? 'selected' : '' }}>Sort: Paid Out</option>
            <option value="name" {{ request('sort_by') === 'name' ? 'selected' : '' }}>Sort: Name A–Z</option>
        </select>
        <label class="flex items-center gap-1.5 border border-gray-200 rounded-xl px-3 py-2 cursor-pointer text-sm text-gray-700 hover:bg-gray-50 select-none">
            <input type="checkbox" name="has_balance" value="1" {{ request('has_balance') ? 'checked' : '' }} class="rounded text-indigo-600">
            Has balance due
        </label>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','sort_by','has_balance']))
        <a href="{{ route('admin.vendor-payments.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Clear</a>
        @endif
    </form>
</div>

{{-- Per-seller summary --}}
<div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-8">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Seller</th>
                <th class="px-5 py-3 text-center">Products</th>
                <th class="px-5 py-3 text-right">Total Sales</th>
                <th class="px-5 py-3 text-right">Commission</th>
                <th class="px-5 py-3 text-right">Hold</th>
                <th class="px-5 py-3 text-right">Available</th>
                <th class="px-5 py-3 text-right">Paid</th>
                <th class="px-5 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($vendors as $vendor)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-5 py-3">
                    <p class="font-medium text-gray-800">{{ $vendor->business_name }}</p>
                    <span class="px-1.5 py-0.5 rounded-full text-xs font-medium {{ $vendor->statusBadge() }}">{{ ucfirst($vendor->status) }}</span>
                </td>
                <td class="px-5 py-3 text-center text-gray-600">{{ $vendor->products_count }}</td>
                <td class="px-5 py-3 text-right font-semibold text-gray-800">৳{{ number_format($vendor->total_sales ?? 0) }}</td>
                <td class="px-5 py-3 text-right text-gray-500">৳{{ number_format($vendor->total_commission ?? 0) }}</td>
                <td class="px-5 py-3 text-right text-yellow-600">৳{{ number_format($vendor->hold_amount ?? 0) }}</td>
                <td class="px-5 py-3 text-right text-blue-600 font-medium">৳{{ number_format($vendor->available_amount ?? 0) }}</td>
                <td class="px-5 py-3 text-right text-green-600">৳{{ number_format($vendor->paid_amount ?? 0) }}</td>
                <td class="px-5 py-3 text-center">
                    <a href="{{ route('admin.vendors.show', $vendor) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                        {{ ($vendor->available_amount ?? 0) > 0 ? 'Settle Payout' : 'View' }}
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-5 py-16 text-center text-gray-400">No sellers found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $vendors->links() }}</div>
</div>

{{-- Global payout history --}}
<h2 class="font-semibold text-gray-800 mb-3">Recent Payouts (All Sellers)</h2>
<div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-5 py-3 text-left">Date</th>
                <th class="px-5 py-3 text-left">Seller</th>
                <th class="px-5 py-3 text-left">Method</th>
                <th class="px-5 py-3 text-left">Reference</th>
                <th class="px-5 py-3 text-left">Processed By</th>
                <th class="px-5 py-3 text-right">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($recentPayouts as $payout)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-5 py-3 text-xs text-gray-500">{{ $payout->created_at->format('M d, Y') }}</td>
                <td class="px-5 py-3">
                    @if($payout->vendor)
                    <a href="{{ route('admin.vendors.show', $payout->vendor) }}" class="text-indigo-600 hover:underline">{{ $payout->vendor->business_name }}</a>
                    @else
                    <span class="text-gray-400">—</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-gray-600">{{ $payout->method ?: '—' }}</td>
                <td class="px-5 py-3 font-mono text-xs text-gray-500">{{ $payout->reference ?: '—' }}</td>
                <td class="px-5 py-3 text-gray-500">{{ $payout->processedBy->name ?? '—' }}</td>
                <td class="px-5 py-3 text-right font-semibold text-green-700">৳{{ number_format($payout->amount) }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-16 text-center text-gray-400">No payouts recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-5 py-4 border-t border-gray-100">{{ $recentPayouts->links() }}</div>
</div>
@endsection
