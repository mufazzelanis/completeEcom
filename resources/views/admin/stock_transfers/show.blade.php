@extends('layouts.admin')
@section('title', 'Transfer '.$stockTransfer->reference_no)

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.stock-transfers.index') }}" class="text-indigo-600 text-sm flex items-center gap-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Transfers
    </a>

    @if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <div class="flex items-start justify-between">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-xl font-bold text-gray-900 font-mono">{{ $stockTransfer->reference_no }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $stockTransfer->statusBadge() }}">{{ $stockTransfer->statusLabel() }}</span>
                </div>
                <div class="flex items-center gap-3 text-sm text-gray-600">
                    <span class="font-medium">{{ $stockTransfer->fromWarehouse->name }}</span>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    <span class="font-medium">{{ $stockTransfer->toWarehouse->name }}</span>
                </div>
                @if($stockTransfer->notes)<p class="text-sm text-gray-500 mt-2">{{ $stockTransfer->notes }}</p>@endif
            </div>
            <div class="text-right text-sm text-gray-400">
                <p>Created {{ $stockTransfer->created_at->format('M d, Y H:i') }}</p>
                <p>By {{ $stockTransfer->creator?->name ?? 'System' }}</p>
                @if($stockTransfer->completed_at)<p class="text-green-600">Completed {{ $stockTransfer->completed_at->format('M d, Y H:i') }}</p>@endif
            </div>
        </div>

        @if(in_array($stockTransfer->status, ['pending', 'draft']))
        <div class="mt-4 pt-4 border-t border-gray-100 flex gap-3">
            <form action="{{ route('admin.stock-transfers.dispatch', $stockTransfer) }}" method="POST">
                @csrf @method('PATCH')
                <button class="bg-blue-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700">Mark In Transit</button>
            </form>
        </div>
        @endif
    </div>

    {{-- Items --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Transfer Items</h3>
        </div>
        <table class="w-full">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-6 py-3 text-left">Product</th>
                    <th class="px-6 py-3 text-center">Requested</th>
                    <th class="px-6 py-3 text-center">Transferred</th>
                    @if($stockTransfer->status === 'in_transit')<th class="px-6 py-3 text-center">Actual Qty</th>@endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @if($stockTransfer->status === 'in_transit')
                <form id="complete-form" action="{{ route('admin.stock-transfers.complete', $stockTransfer) }}" method="POST">
                    @csrf @method('PATCH')
                @endif
                @foreach($stockTransfer->items as $item)
                <tr>
                    <td class="px-6 py-3">
                        <p class="text-sm font-medium text-gray-800">{{ $item->product->name }}</p>
                        <p class="text-xs font-mono text-gray-400">{{ $item->product->sku ?? '—' }}</p>
                    </td>
                    <td class="px-6 py-3 text-center text-sm text-gray-700">{{ $item->quantity_requested }}</td>
                    <td class="px-6 py-3 text-center text-sm {{ $item->quantity_transferred > 0 ? 'text-green-600 font-semibold' : 'text-gray-400' }}">
                        {{ $item->quantity_transferred > 0 ? $item->quantity_transferred : '—' }}
                    </td>
                    @if($stockTransfer->status === 'in_transit')
                    <td class="px-6 py-3 text-center">
                        <input type="number" name="quantities[{{ $item->id }}]" value="{{ $item->quantity_requested }}" min="0"
                            class="w-20 border border-gray-200 rounded-lg px-2 py-1.5 text-sm text-center focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </td>
                    @endif
                </tr>
                @endforeach
                @if($stockTransfer->status === 'in_transit')
                </form>
                @endif
            </tbody>
        </table>

        @if($stockTransfer->status === 'in_transit')
        <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
            <button form="complete-form" type="submit" class="bg-green-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-green-700">Complete Transfer & Update Stock</button>
        </div>
        @endif
    </div>
</div>
@endsection
