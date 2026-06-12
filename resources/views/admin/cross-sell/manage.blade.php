@extends('layouts.admin')
@section('title', 'Recommendations')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.cross-sell.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-xl font-bold text-gray-800">{{ $product->name }}</h1>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-3 gap-6">
    {{-- Add Recommendation --}}
    <div class="col-span-1">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-1">Add Recommendation</h2>
            <p class="text-xs text-gray-400 mb-4">Products already linked are excluded from this list.</p>
            <form action="{{ route('admin.cross-sell.store', $product) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Type</label>
                    <div class="grid grid-cols-2 gap-2" x-data="{ type: 'cross_sell' }">
                        <label class="flex items-center gap-2 cursor-pointer p-3 rounded-xl border-2 transition"
                            :class="type === 'cross_sell' ? 'border-green-400 bg-green-50' : 'border-gray-200'">
                            <input type="radio" name="type" value="cross_sell" x-model="type" class="hidden">
                            <div class="w-3 h-3 rounded-full" :class="type === 'cross_sell' ? 'bg-green-500' : 'bg-gray-300'"></div>
                            <div>
                                <p class="text-xs font-semibold text-gray-700">Cross-Sell</p>
                                <p class="text-xs text-gray-400">Add-on items</p>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer p-3 rounded-xl border-2 transition"
                            :class="type === 'upsell' ? 'border-indigo-400 bg-indigo-50' : 'border-gray-200'">
                            <input type="radio" name="type" value="upsell" x-model="type" class="hidden">
                            <div class="w-3 h-3 rounded-full" :class="type === 'upsell' ? 'bg-indigo-500' : 'bg-gray-300'"></div>
                            <div>
                                <p class="text-xs font-semibold text-gray-700">Upsell</p>
                                <p class="text-xs text-gray-400">Better/pricier</p>
                            </div>
                        </label>
                    </div>
                    <input type="hidden" name="type" x-bind:value="type" x-data x-init="$el.value = 'cross_sell'">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Product</label>
                    <select name="recommended_product_id" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select product...</option>
                        @foreach($allProducts as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} (৳{{ number_format($p->price) }})</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Add</button>
            </form>
        </div>

        {{-- Guide --}}
        <div class="bg-gray-50 rounded-2xl p-4 mt-4 space-y-3 text-xs text-gray-500">
            <div class="flex items-start gap-2">
                <span class="w-2 h-2 bg-green-500 rounded-full mt-1 flex-shrink-0"></span>
                <p><strong class="text-gray-700">Cross-Sell</strong> — complementary add-ons shown in the cart ("Customers also bought")</p>
            </div>
            <div class="flex items-start gap-2">
                <span class="w-2 h-2 bg-indigo-500 rounded-full mt-1 flex-shrink-0"></span>
                <p><strong class="text-gray-700">Upsell</strong> — higher-value alternatives shown on product page ("You might also like")</p>
            </div>
        </div>
    </div>

    {{-- Current Recommendations --}}
    <div class="col-span-2 space-y-6">
        {{-- Cross-Sells --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <span class="w-3 h-3 bg-green-500 rounded-full"></span>
                <h2 class="font-semibold text-gray-800">Cross-Sell ({{ $crossSells->count() }})</h2>
                <span class="text-xs text-gray-400 ml-1">Shown in cart</span>
            </div>
            @forelse($crossSells as $rec)
            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    @if($rec->recommended->image)
                    <img src="{{ Storage::url($rec->recommended->image) }}" class="w-9 h-9 rounded-xl object-cover">
                    @endif
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $rec->recommended->name }}</p>
                        <p class="text-xs text-gray-400">৳{{ number_format($rec->recommended->sale_price ?? $rec->recommended->price) }}</p>
                    </div>
                </div>
                <form action="{{ route('admin.cross-sell.destroy', [$product, $rec]) }}" method="POST">
                    @csrf @method('DELETE')
                    <button class="text-red-500 hover:text-red-700 text-xs font-medium">Remove</button>
                </form>
            </div>
            @empty
            <p class="px-6 py-6 text-sm text-gray-400 text-center">No cross-sell products yet.</p>
            @endforelse
        </div>

        {{-- Upsells --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
                <span class="w-3 h-3 bg-indigo-500 rounded-full"></span>
                <h2 class="font-semibold text-gray-800">Upsell ({{ $upsells->count() }})</h2>
                <span class="text-xs text-gray-400 ml-1">Shown on product page</span>
            </div>
            @forelse($upsells as $rec)
            <div class="flex items-center justify-between px-6 py-3 border-b border-gray-50 last:border-0 hover:bg-gray-50">
                <div class="flex items-center gap-3">
                    @if($rec->recommended->image)
                    <img src="{{ Storage::url($rec->recommended->image) }}" class="w-9 h-9 rounded-xl object-cover">
                    @endif
                    <div>
                        <p class="font-medium text-gray-800 text-sm">{{ $rec->recommended->name }}</p>
                        <p class="text-xs text-gray-400">৳{{ number_format($rec->recommended->sale_price ?? $rec->recommended->price) }}
                            @if(($rec->recommended->price ?? 0) > $product->price)
                            <span class="text-indigo-500 ml-1">+৳{{ number_format($rec->recommended->price - $product->price) }}</span>
                            @endif
                        </p>
                    </div>
                </div>
                <form action="{{ route('admin.cross-sell.destroy', [$product, $rec]) }}" method="POST">
                    @csrf @method('DELETE')
                    <button class="text-red-500 hover:text-red-700 text-xs font-medium">Remove</button>
                </form>
            </div>
            @empty
            <p class="px-6 py-6 text-sm text-gray-400 text-center">No upsell products yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
