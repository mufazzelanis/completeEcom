@extends('layouts.admin')
@section('title', 'New Stock Transfer')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.stock-transfers.index') }}" class="text-indigo-600 text-sm flex items-center gap-2 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to Transfers
    </a>

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
        @foreach($errors->all() as $e)<p class="text-sm text-red-600">{{ $e }}</p>@endforeach
    </div>
    @endif

    <form action="{{ route('admin.stock-transfers.store') }}" method="POST"
        x-data="{
            items: [{ product_id: '', quantity: 1 }],
            addItem() { this.items.push({ product_id: '', quantity: 1 }) },
        }">
        @csrf

        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <h3 class="font-semibold text-gray-800 mb-4">Transfer Details</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Warehouse *</label>
                    <select name="from_warehouse_id" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select source…</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ old('from_warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Warehouse *</label>
                    <select name="to_warehouse_id" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select destination…</option>
                        @foreach($warehouses as $wh)
                        <option value="{{ $wh->id }}" {{ old('to_warehouse_id') == $wh->id ? 'selected' : '' }}>{{ $wh->name }} ({{ $wh->code }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2"
                        class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800">Transfer Items</h3>
                <button type="button" @click="addItem()" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Product
                </button>
            </div>

            <div class="space-y-3">
                <template x-for="(item, i) in items" :key="i">
                    <div class="grid grid-cols-12 gap-3 items-center bg-gray-50 rounded-xl px-4 py-3">
                        <div class="col-span-8">
                            <select :name="`items[${i}][product_id]`" x-model="item.product_id" required
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Select product…</option>
                                @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}{{ $p->sku ? ' — '.$p->sku : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-span-3">
                            <input type="number" :name="`items[${i}][quantity]`" x-model="item.quantity" min="1" placeholder="Qty"
                                class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="col-span-1 flex justify-end">
                            <button type="button" @click="items.length > 1 && items.splice(i,1)" class="text-red-400 hover:text-red-600" x-show="items.length > 1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="flex-1 bg-indigo-600 text-white py-3 rounded-xl font-semibold hover:bg-indigo-700 transition">Create Transfer</button>
            <a href="{{ route('admin.stock-transfers.index') }}" class="px-6 py-3 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>
@endsection
