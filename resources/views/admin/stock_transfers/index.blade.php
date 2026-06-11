@extends('layouts.admin')
@section('title', 'Stock Transfers')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Stock Transfers</h1>
        <p class="text-sm text-gray-500 mt-1">Move inventory between warehouses</p>
    </div>
    <a href="{{ route('admin.stock-transfers.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Transfer
    </a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Reference #…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-44">
        <select name="warehouse_id" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            @foreach(['draft'=>'Draft','pending'=>'Pending','in_transit'=>'In Transit','completed'=>'Completed','cancelled'=>'Cancelled'] as $val=>$label)
            <option value="{{ $val }}" {{ request('status') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700">Filter</button>
        @if(request()->hasAny(['search','warehouse_id','status']))<a href="{{ route('admin.stock-transfers.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
            <tr>
                <th class="px-6 py-3 text-left">Reference</th>
                <th class="px-6 py-3 text-left">From → To</th>
                <th class="px-6 py-3 text-center">Items</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-left">Date</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($transfers as $transfer)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4">
                    <span class="font-mono text-sm font-semibold text-indigo-700">{{ $transfer->reference_no }}</span>
                    @if($transfer->notes)<p class="text-xs text-gray-400 mt-0.5 truncate max-w-32">{{ $transfer->notes }}</p>@endif
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2 text-sm text-gray-700">
                        <span class="font-medium">{{ $transfer->fromWarehouse->name }}</span>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        <span class="font-medium">{{ $transfer->toWarehouse->name }}</span>
                    </div>
                </td>
                <td class="px-6 py-4 text-center text-sm text-gray-600">{{ $transfer->items_count }}</td>
                <td class="px-6 py-4 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $transfer->statusBadge() }}">{{ $transfer->statusLabel() }}</span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">{{ $transfer->created_at->format('M d, Y') }}</td>
                <td class="px-6 py-4 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.stock-transfers.show', $transfer) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                        @if(!in_array($transfer->status, ['completed','cancelled']))
                        <form action="{{ route('admin.stock-transfers.cancel', $transfer) }}" method="POST" onsubmit="return confirm('Cancel this transfer?')">
                            @csrf @method('PATCH')
                            <button class="text-red-500 hover:text-red-700 text-sm">Cancel</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-6 py-16 text-center text-gray-400 text-sm">No transfers yet.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($transfers->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $transfers->links() }}</div>@endif
</div>
@endsection
