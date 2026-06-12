@extends('layouts.admin')
@section('title', 'Edit Flash Sale')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.flash-sales.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-xl font-bold text-gray-800">{{ $flashSale->name }}</h1>
    <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $flashSale->status_badge }}">{{ strtoupper($flashSale->status) }}</span>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-6 text-sm">{{ session('success') }}</div>
@endif

<div class="grid grid-cols-3 gap-6">
    {{-- Settings --}}
    <div class="col-span-1">
        <form action="{{ route('admin.flash-sales.update', $flashSale) }}" method="POST" class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            @csrf @method('PUT')

            <h2 class="font-semibold text-gray-800 mb-2">Sale Settings</h2>

            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Name</label>
                <input type="text" name="name" value="{{ old('name', $flashSale->name) }}" required
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Banner Text</label>
                <input type="text" name="banner_text" value="{{ old('banner_text', $flashSale->banner_text) }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Banner Color</label>
                <input type="color" name="banner_color" value="{{ old('banner_color', $flashSale->banner_color) }}" class="h-9 w-full border border-gray-200 rounded-xl cursor-pointer">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Starts At</label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $flashSale->starts_at->format('Y-m-d\TH:i')) }}" required
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Ends At</label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $flashSale->ends_at->format('Y-m-d\TH:i')) }}" required
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex items-center gap-2">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ $flashSale->is_active ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded">
                <label for="is_active" class="text-sm text-gray-700">Active</label>
            </div>
            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save Settings</button>
        </form>

        {{-- Countdown --}}
        @if($flashSale->isLive())
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 mt-4 text-center" x-data="countdown('{{ $flashSale->ends_at->toISOString() }}')" x-init="start()">
            <p class="text-xs font-semibold text-red-500 uppercase tracking-wider mb-2">⚡ Live — Ends In</p>
            <div class="flex justify-center gap-3">
                <div class="text-center"><p class="text-2xl font-bold text-red-700" x-text="hours">00</p><p class="text-xs text-red-400">HRS</p></div>
                <span class="text-2xl font-bold text-red-400 self-start mt-1">:</span>
                <div class="text-center"><p class="text-2xl font-bold text-red-700" x-text="minutes">00</p><p class="text-xs text-red-400">MIN</p></div>
                <span class="text-2xl font-bold text-red-400 self-start mt-1">:</span>
                <div class="text-center"><p class="text-2xl font-bold text-red-700" x-text="seconds">00</p><p class="text-xs text-red-400">SEC</p></div>
            </div>
        </div>
        @endif
    </div>

    {{-- Products --}}
    <div class="col-span-2 space-y-6">
        {{-- Add Product --}}
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Add Product to Sale</h2>
            <form action="{{ route('admin.flash-sales.add-product', $flashSale) }}" method="POST" class="grid grid-cols-4 gap-3">
                @csrf
                <div class="col-span-2">
                    <select name="product_id" required class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Select product...</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}">{{ $p->name }} (৳{{ number_format($p->sale_price ?? $p->price) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="discount_type" class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none">
                        <option value="percentage">% Off</option>
                        <option value="fixed">৳ Off</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <input type="number" name="discount_value" placeholder="Value" step="0.01" min="0.01" required
                        class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="flex gap-2 items-center">
                    <input type="number" name="stock_limit" placeholder="Stock limit (0=∞)" min="0"
                        class="flex-1 border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-3"></div>
                <button type="submit" class="bg-indigo-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Add</button>
            </form>
        </div>

        {{-- Current Products --}}
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Products in Sale ({{ $flashSale->products->count() }})</h2>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
                    <th class="px-4 py-3 text-left">Product</th>
                    <th class="px-4 py-3 text-center">Original</th>
                    <th class="px-4 py-3 text-center">Discount</th>
                    <th class="px-4 py-3 text-center">Flash Price</th>
                    <th class="px-4 py-3 text-center">Stock Limit</th>
                    <th class="px-4 py-3 text-center">Sold</th>
                    <th class="px-4 py-3 text-center">Action</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($flashSale->products as $fp)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                @if($fp->product->image)
                                <img src="{{ Storage::url($fp->product->image) }}" class="w-8 h-8 rounded-lg object-cover">
                                @endif
                                <span class="font-medium text-gray-800 text-xs">{{ $fp->product->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-500 text-xs">৳{{ number_format($fp->product->sale_price ?? $fp->product->price) }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-red-100 text-red-600 px-2 py-0.5 rounded-full text-xs font-medium">
                                {{ $fp->discount_type === 'percentage' ? $fp->discount_value.'%' : '৳'.$fp->discount_value }} off
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-green-600 text-xs">৳{{ number_format($fp->sale_price) }}</td>
                        <td class="px-4 py-3 text-center text-xs text-gray-500">{{ $fp->stock_limit ?: '∞' }}</td>
                        <td class="px-4 py-3 text-center text-xs text-gray-600">{{ $fp->sold_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <form action="{{ route('admin.flash-sales.remove-product', [$flashSale, $fp]) }}" method="POST">
                                @csrf @method('DELETE')
                                <button class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">No products added yet</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function countdown(endTime) {
    return {
        hours: '00', minutes: '00', seconds: '00', timer: null,
        start() {
            this.tick();
            this.timer = setInterval(() => this.tick(), 1000);
        },
        tick() {
            const diff = Math.max(0, new Date(endTime) - new Date());
            const h = Math.floor(diff / 3600000);
            const m = Math.floor((diff % 3600000) / 60000);
            const s = Math.floor((diff % 60000) / 1000);
            this.hours   = String(h).padStart(2, '0');
            this.minutes = String(m).padStart(2, '0');
            this.seconds = String(s).padStart(2, '0');
            if (diff === 0) clearInterval(this.timer);
        }
    };
}
</script>
@endsection
