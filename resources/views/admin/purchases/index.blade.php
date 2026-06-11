@extends('layouts.admin')
@section('title', 'Purchase Orders')

@section('content')
<div class="flex items-center justify-between mb-6">
    <p class="text-sm text-gray-500">Manage stock purchase orders from suppliers</p>
    <a href="{{ route('admin.purchases.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center space-x-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <span>New Purchase</span>
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference no…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-44">
        <select name="supplier" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Suppliers</option>
            @foreach($suppliers as $s)
                <option value="{{ $s->id }}" {{ request('supplier') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            @foreach(['draft','ordered','partial','received','cancelled'] as $st)
                <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','supplier','status']))
            <a href="{{ route('admin.purchases.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Reference</th>
                <th class="px-6 py-3 text-left">Supplier</th>
                <th class="px-6 py-3 text-center">Items</th>
                <th class="px-6 py-3 text-right">Total</th>
                <th class="px-6 py-3 text-right">Paid</th>
                <th class="px-6 py-3 text-center">Date</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($purchases as $purchase)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <a href="{{ route('admin.purchases.show', $purchase) }}" class="text-indigo-600 hover:underline text-sm font-mono font-medium">{{ $purchase->reference_no }}</a>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700">{{ $purchase->supplier->name }}</td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $purchase->items_count }}</td>
                <td class="px-6 py-4 text-right text-sm font-medium text-gray-800">৳{{ number_format($purchase->total_amount, 2) }}</td>
                <td class="px-6 py-4 text-right text-sm text-gray-600">৳{{ number_format($purchase->paid_amount, 2) }}</td>
                <td class="px-6 py-4 text-center text-sm text-gray-500">{{ $purchase->purchased_at->format('d M Y') }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $purchase->statusBadge() }}">{{ ucfirst($purchase->status) }}</span>
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end space-x-2">
                        <a href="{{ route('admin.purchases.show', $purchase) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                        @if(!in_array($purchase->status, ['received','cancelled']))
                        <a href="{{ route('admin.purchases.edit', $purchase) }}" class="text-gray-500 hover:text-gray-700 text-sm font-medium">Edit</a>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-6 py-12 text-center text-gray-400 text-sm">No purchase orders found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($purchases->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $purchases->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
