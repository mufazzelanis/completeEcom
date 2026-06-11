@extends('layouts.admin')
@section('title', 'Batch / Lot Management')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Batch / Lot Management</h1>
        <p class="text-sm text-gray-500 mt-1">Track product lots with manufacture and expiry dates</p>
    </div>
    <a href="{{ route('admin.batches.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        Add Batch
    </a>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 gap-4 mb-6">
    <div class="bg-red-50 border border-red-200 rounded-2xl p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </div>
        <div><p class="text-xl font-bold text-red-700">{{ $expiredCount }}</p><p class="text-sm text-red-600">Expired Batches</p></div>
    </div>
    <div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-4 flex items-center gap-4">
        <div class="w-10 h-10 bg-yellow-100 rounded-xl flex items-center justify-center">
            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div><p class="text-xl font-bold text-yellow-700">{{ $expiringCount }}</p><p class="text-sm text-yellow-600">Expiring Soon (30 days)</p></div>
    </div>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Lot number…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-44">
        <select name="product_id" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Products</option>
            @foreach($products as $p)
            <option value="{{ $p->id }}" {{ request('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
            @endforeach
        </select>
        <select name="warehouse_id" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Warehouses</option>
            @foreach($warehouses as $wh)
            <option value="{{ $wh->id }}" {{ request('warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
            @endforeach
        </select>
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            <option value="expired" {{ request('status')==='expired' ? 'selected' : '' }}>Expired</option>
            <option value="expiring" {{ request('status')==='expiring' ? 'selected' : '' }}>Expiring Soon</option>
            <option value="active" {{ request('status')==='active' ? 'selected' : '' }}>Active</option>
        </select>
        <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
        @if(request()->hasAny(['search','product_id','warehouse_id','status']))<a href="{{ route('admin.batches.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600">Clear</a>@endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100 text-xs text-gray-500 uppercase tracking-wider">
            <tr>
                <th class="px-6 py-3 text-left">Lot Number</th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-left">Warehouse</th>
                <th class="px-6 py-3 text-center">Qty</th>
                <th class="px-6 py-3 text-left">Mfg Date</th>
                <th class="px-6 py-3 text-left">Expiry</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($batches as $batch)
            <tr class="hover:bg-gray-50 transition {{ $batch->isExpired() ? 'bg-red-50' : ($batch->isExpiringSoon() ? 'bg-yellow-50' : '') }}">
                <td class="px-6 py-3">
                    <span class="font-mono text-sm font-semibold text-gray-800">{{ $batch->lot_number }}</span>
                </td>
                <td class="px-6 py-3 text-sm text-gray-700">{{ $batch->product->name }}</td>
                <td class="px-6 py-3 text-sm text-gray-500">{{ $batch->warehouse?->name ?? '—' }}</td>
                <td class="px-6 py-3 text-center text-sm font-semibold text-gray-700">{{ $batch->quantity }}</td>
                <td class="px-6 py-3 text-sm text-gray-500">{{ $batch->manufacture_date?->format('M d, Y') ?? '—' }}</td>
                <td class="px-6 py-3 text-sm {{ $batch->isExpired() ? 'text-red-600 font-semibold' : ($batch->isExpiringSoon() ? 'text-yellow-600 font-semibold' : 'text-gray-500') }}">
                    {{ $batch->expiry_date?->format('M d, Y') ?? '—' }}
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $batch->statusBadge() }}">{{ $batch->statusLabel() }}</span>
                </td>
                <td class="px-6 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.batches.edit', $batch) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">Edit</a>
                        <form action="{{ route('admin.batches.destroy', $batch) }}" method="POST" onsubmit="return confirm('Delete this batch?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:text-red-700 text-sm">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="px-6 py-16 text-center text-gray-400 text-sm">No batches found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($batches->hasPages())<div class="px-6 py-4 border-t border-gray-100">{{ $batches->links() }}</div>@endif
</div>
@endsection
