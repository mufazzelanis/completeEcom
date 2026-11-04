@extends('layouts.admin')
@section('title', 'Edit Flash Sale')

@section('content')
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $items = $flashSale->products->filter(fn ($fp) => $fp->product);
    $unitsSold = (int) $items->sum('sold_count');
    $revenue = $items->sum(fn ($fp) => $fp->sale_price * $fp->sold_count);
    $rows = $items->map(fn ($fp) => [
        'id' => $fp->id,
        'name' => $fp->product->name,
        'category' => $fp->product->category?->name,
        'image' => $fp->product->image ? image_thumb($fp->product->image, 96) : null,
        'url' => route('products.show', $fp->product->slug),
        'base' => $fp->base_price,
        'stockNow' => (int) $fp->product->stock,
        'type' => $fp->discount_type,
        'value' => (float) $fp->discount_value,
        'stock' => $fp->stock_limit,
        'per' => $fp->per_order_limit,
        'sort' => $fp->sort_order,
        'featured' => $fp->is_featured,
        'sold' => $fp->sold_count,
        'remove' => false,
    ])->values();
    $field = 'w-full rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2 py-1.5 text-xs text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-400';
@endphp

<script>
window.flashDeals = function (rows, searchUrl) {
    const price = (base, type, value) => Math.max(0, type === 'percentage' ? base * (1 - Math.min(99, +value || 0) / 100) : base - (+value || 0));
    return {
        rows, searchUrl,
        q: '', results: [], loading: false, picked: [], pickedInfo: {}, onlyInStock: false, seq: 0,
        add: { type: 'percentage', value: 10, stock: '', per: '' },
        bulk: { type: 'percentage', value: 15 },
        dirty: false,
        money(n) { return '৳' + Math.round(n).toLocaleString(); },
        flash(r) { return price(r.base, r.type, r.value); },
        pct(r) { return r.base > 0 ? Math.round((1 - this.flash(r) / r.base) * 100) : 0; },
        warn(r) {
            if (!(+r.value > 0)) return 'Enter a discount';
            if (this.flash(r) <= 0) return 'Price would be ৳0';
            if (r.type === 'percentage' && +r.value > 90) return 'Over 90% off — sure?';
            if (+r.stock > 0 && +r.stock > r.stockNow) return 'Cap is above real stock (' + r.stockNow + ')';
            return '';
        },
        init() {
            this.search();
            this.$watch('q', () => { clearTimeout(this.timer); this.timer = setTimeout(() => this.search(), 250); });
            this.$watch('onlyInStock', () => this.search());
        },
        async search() {
            const mine = ++this.seq; // drop responses that arrive after a newer search
            this.loading = true;
            const url = this.searchUrl + '?' + new URLSearchParams({ q: this.q.trim(), in_stock: this.onlyInStock ? 1 : 0 });
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = res.ok ? await res.json() : [];
                if (mine === this.seq) this.results = data;
            } finally {
                if (mine === this.seq) this.loading = false;
            }
        },
        get available() { return this.results; },
        toggle(p) {
            if (this.picked.includes(p.id)) { this.picked = this.picked.filter(x => x !== p.id); return; }
            this.picked.push(p.id); this.pickedInfo[p.id] = p;
        },
        pickAll() {
            const all = this.available.every(p => this.picked.includes(p.id));
            this.available.forEach(p => { const on = this.picked.includes(p.id); if (all ? on : !on) this.toggle(p); });
        },
        get pickedProducts() { return this.picked.map(id => this.pickedInfo[id]).filter(Boolean); },
        applyAll() { this.rows.forEach(r => { r.type = this.bulk.type; r.value = this.bulk.value; }); this.dirty = true; },
        get removing() { return this.rows.filter(r => r.remove).length; },
    };
};
</script>

<div x-data="flashDeals(@js($rows), @js(route('admin.flash-sales.products.search', $flashSale)))">
{{-- Header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-4 min-w-0">
        <a href="{{ route('admin.flash-sales.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2 shrink-0">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
        </a>
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-gray-800 truncate">{{ $flashSale->name }}</h1>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $flashSale->status_badge }}">{{ $flashSale->status === 'inactive' ? 'PAUSED' : strtoupper($flashSale->status) }}</span>
            </div>
            <p class="text-xs text-gray-500">{{ $flashSale->starts_at->copy()->timezone($tz)->format('M d, g:i A') }} → {{ $flashSale->ends_at->copy()->timezone($tz)->format('M d, Y g:i A') }} · {{ $tz }}</p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ $flashSale->url }}" target="_blank" class="rounded-xl border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">View on site ↗</a>
        <form action="{{ route('admin.flash-sales.duplicate', $flashSale) }}" method="POST">@csrf
            <button class="rounded-xl border border-gray-200 px-3.5 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Duplicate</button>
        </form>
        <form action="{{ route('admin.flash-sales.toggle', $flashSale) }}" method="POST">@csrf @method('PATCH')
            <button class="rounded-xl px-3.5 py-2 text-sm font-bold text-white {{ $flashSale->is_active ? 'bg-amber-500 hover:bg-amber-600' : 'bg-emerald-600 hover:bg-emerald-700' }}">{{ $flashSale->is_active ? 'Pause sale' : 'Activate sale' }}</button>
        </form>
    </div>
</div>

@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 mb-5 text-sm">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm">{{ $errors->first() }}</div>
@endif

{{-- Performance --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
    <div class="bg-white rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">Deals</p><p class="text-xl font-extrabold text-gray-900">{{ $items->count() }}</p></div>
    <div class="bg-white rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">Units sold</p><p class="text-xl font-extrabold text-gray-900">{{ number_format($unitsSold) }}</p></div>
    <div class="bg-white rounded-2xl shadow-sm p-4"><p class="text-xs text-gray-500">Est. revenue</p><p class="text-xl font-extrabold text-gray-900">৳{{ number_format($revenue) }}</p></div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500">{{ $flashSale->status === 'upcoming' ? 'Starts in' : ($flashSale->status === 'live' ? 'Time left' : 'Timeline') }}</p>
        @if(in_array($flashSale->status, ['live', 'upcoming']))
            <p class="text-xl font-extrabold text-gray-900">{{ ($flashSale->status === 'live' ? $flashSale->ends_at : $flashSale->starts_at)->diffForHumans(['parts' => 2, 'short' => true, 'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE]) }}</p>
        @else
            <p class="text-xl font-extrabold text-gray-400">{{ $flashSale->status === 'ended' ? 'Ended' : 'Paused' }}</p>
        @endif
        <div class="mt-1.5 h-1 rounded-full bg-gray-100 overflow-hidden"><div class="h-full rounded-full bg-gradient-to-r from-orange-500 to-rose-500" style="width: {{ $flashSale->elapsed_percent }}%"></div></div>
    </div>
</div>

{{-- Add deals --}}
<div class="bg-white rounded-2xl shadow-sm p-5 mb-5">
    <div class="flex flex-wrap items-end justify-between gap-2 mb-3">
        <div>
            <h2 class="font-bold text-gray-800">Add deals</h2>
            <p class="text-xs text-gray-500">Search, tick as many products as you like, and give them all the same deal. You can fine-tune each one below.</p>
        </div>
        <label class="flex items-center gap-2 text-xs text-gray-600"><input type="checkbox" x-model="onlyInStock" class="rounded text-orange-500"> In-stock only</label>
    </div>
    <div class="grid lg:grid-cols-[minmax(0,1fr)_20rem] gap-4">
        <div>
            <div class="flex gap-2 mb-2">
                <input type="search" x-model="q" placeholder="Search products…" class="flex-1 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                <button type="button" @click="pickAll()" class="rounded-xl border border-gray-200 px-3 text-xs font-semibold text-gray-600 hover:bg-gray-50">Select shown</button>
            </div>
            <div class="max-h-72 overflow-y-auto rounded-xl border border-gray-100 dark:border-gray-800 divide-y divide-gray-100">
                <template x-for="p in available" :key="p.id">
                    <label class="flex items-center gap-3 px-3 py-2 cursor-pointer hover:bg-gray-50" :class="picked.includes(p.id) && 'bg-orange-50/60'">
                        <input type="checkbox" :checked="picked.includes(p.id)" @change="toggle(p)" class="rounded text-orange-500">
                        <img :src="p.image || ''" x-show="p.image" class="w-8 h-8 rounded-lg object-cover bg-gray-100" alt="">
                        <span class="flex-1 min-w-0 text-sm text-gray-800 truncate" x-text="p.name"></span>
                        <span class="text-xs text-gray-500" x-text="money(p.price)"></span>
                        <span class="text-[10px] font-semibold rounded-full px-1.5 py-0.5" :class="p.stock > 0 ? 'bg-gray-100 text-gray-500' : 'bg-red-50 text-red-500'" x-text="p.stock > 0 ? p.stock + ' in stock' : 'out'"></span>
                    </label>
                </template>
                <p x-show="loading && !available.length" class="px-3 py-8 text-center text-sm text-gray-400">Searching…</p>
                <p x-show="!loading && !available.length" class="px-3 py-8 text-center text-sm text-gray-400">No matching products (already-added ones are hidden).</p>
                <p x-show="available.length === 30" class="px-3 py-2 text-center text-[11px] text-gray-400">Showing the first 30 — type to narrow down.</p>
            </div>
        </div>
        <form action="{{ route('admin.flash-sales.products.add', $flashSale) }}" method="POST" class="rounded-xl bg-gray-50 dark:bg-gray-800 p-4 space-y-3 self-start">
            @csrf
            <template x-for="id in picked" :key="id"><input type="hidden" name="product_ids[]" :value="id"></template>
            <p class="text-sm font-bold text-gray-800"><span x-text="picked.length"></span> selected</p>
            <div class="grid grid-cols-2 gap-2">
                <select name="discount_type" x-model="add.type" class="{{ $field }}">
                    <option value="percentage">% off</option>
                    <option value="fixed">৳ off</option>
                </select>
                <input type="number" name="discount_value" x-model="add.value" step="0.01" min="0.01" required class="{{ $field }}" placeholder="Value">
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-0.5">Stock cap</label>
                    <input type="number" name="stock_limit" x-model="add.stock" min="0" class="{{ $field }}" placeholder="∞">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-gray-500 uppercase mb-0.5">Max / order</label>
                    <input type="number" name="per_order_limit" x-model="add.per" min="0" class="{{ $field }}" placeholder="∞">
                </div>
            </div>
            <div x-show="pickedProducts.length" class="max-h-28 overflow-y-auto space-y-1 text-xs">
                <template x-for="p in pickedProducts.slice(0, 20)" :key="p.id">
                    <div class="flex justify-between gap-2"><span class="truncate text-gray-600" x-text="p.name"></span>
                        <span class="shrink-0"><s class="text-gray-400" x-text="money(p.price)"></s> <b class="text-gray-900" x-text="money(Math.max(0, add.type === 'percentage' ? p.price * (1 - Math.min(99, +add.value || 0) / 100) : p.price - (+add.value || 0)))"></b></span>
                    </div>
                </template>
            </div>
            <button type="submit" :disabled="!picked.length" class="w-full rounded-xl py-2 text-sm font-bold text-white bg-gradient-to-r from-orange-500 to-rose-500 disabled:opacity-40 disabled:cursor-not-allowed">
                Add <span x-text="picked.length || ''"></span> to sale
            </button>
        </form>
    </div>
</div>

{{-- Deals table --}}
<form action="{{ route('admin.flash-sales.products.update', $flashSale) }}" method="POST" class="bg-white rounded-2xl shadow-sm mb-5" @input="dirty = true">
    @csrf @method('PUT')
    <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-bold text-gray-800">Deals in this sale (<span x-text="rows.length"></span>)</h2>
        <div class="flex items-center gap-2 text-xs" x-show="rows.length">
            <span class="text-gray-500 font-semibold">Apply to all:</span>
            <select x-model="bulk.type" class="rounded-lg border border-gray-200 px-2 py-1.5 text-xs"><option value="percentage">% off</option><option value="fixed">৳ off</option></select>
            <input type="number" x-model="bulk.value" min="0.01" step="0.01" class="w-20 rounded-lg border border-gray-200 px-2 py-1.5 text-xs">
            <button type="button" @click="applyAll()" class="rounded-lg bg-gray-900 text-white px-3 py-1.5 font-semibold">Apply</button>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50"><tr class="text-[11px] text-gray-500 uppercase tracking-wider">
                <th class="px-4 py-2.5 text-left">Product</th>
                <th class="px-3 py-2.5 text-left w-44">Discount</th>
                <th class="px-3 py-2.5 text-right">Flash price</th>
                <th class="px-3 py-2.5 text-center w-24">Stock cap</th>
                <th class="px-3 py-2.5 text-center w-24">Max/order</th>
                <th class="px-3 py-2.5 text-left w-36">Sold</th>
                <th class="px-3 py-2.5 text-center w-16">Order</th>
                <th class="px-3 py-2.5 text-center">Top</th>
                <th class="px-3 py-2.5 text-center">Remove</th>
            </tr></thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="r in rows" :key="r.id">
                    <tr :class="r.remove ? 'opacity-40 bg-red-50/40' : 'hover:bg-gray-50'">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5 min-w-[12rem]">
                                <img :src="r.image || ''" x-show="r.image" class="w-9 h-9 rounded-lg object-cover bg-gray-100" alt="">
                                <div class="min-w-0">
                                    <a :href="r.url" target="_blank" class="block font-medium text-gray-800 text-xs truncate max-w-[14rem] hover:text-indigo-600" x-text="r.name"></a>
                                    <p class="text-[11px] text-gray-400"><span x-text="money(r.base)"></span> · <span x-text="r.stockNow + ' in stock'"></span></p>
                                </div>
                            </div>
                        </td>
                        <td class="px-3 py-3">
                            <div class="flex gap-1">
                                <select :name="`items[${r.id}][discount_type]`" x-model="r.type" class="{{ $field }} !w-16"><option value="percentage">%</option><option value="fixed">৳</option></select>
                                <input type="number" :name="`items[${r.id}][discount_value]`" x-model="r.value" step="0.01" min="0.01" class="{{ $field }}">
                            </div>
                        </td>
                        <td class="px-3 py-3 text-right whitespace-nowrap">
                            <p class="font-extrabold text-gray-900 text-sm" x-text="money(flash(r))"></p>
                            <p class="text-[11px] font-semibold text-emerald-600" x-text="'-' + pct(r) + '%'"></p>
                            <p class="text-[10px] font-semibold text-amber-600" x-show="warn(r)" x-text="warn(r)"></p>
                        </td>
                        <td class="px-3 py-3"><input type="number" :name="`items[${r.id}][stock_limit]`" x-model="r.stock" min="0" placeholder="∞" class="{{ $field }} text-center"></td>
                        <td class="px-3 py-3"><input type="number" :name="`items[${r.id}][per_order_limit]`" x-model="r.per" min="0" placeholder="∞" class="{{ $field }} text-center"></td>
                        <td class="px-3 py-3">
                            <p class="text-xs font-semibold text-gray-700"><span x-text="r.sold"></span><span class="text-gray-400" x-text="+r.stock > 0 ? ' / ' + r.stock : ' sold'"></span></p>
                            <div class="mt-1 h-1.5 rounded-full bg-gray-100 overflow-hidden" x-show="+r.stock > 0">
                                <div class="h-full rounded-full bg-gradient-to-r from-orange-500 to-rose-500" :style="'width:' + Math.min(100, r.sold / r.stock * 100) + '%'"></div>
                            </div>
                        </td>
                        <td class="px-3 py-3"><input type="number" :name="`items[${r.id}][sort_order]`" x-model="r.sort" min="0" class="{{ $field }} text-center"></td>
                        <td class="px-3 py-3 text-center">
                            <input type="hidden" :name="`items[${r.id}][is_featured]`" :value="r.featured ? 1 : 0">
                            <button type="button" @click="r.featured = !r.featured; dirty = true" :class="r.featured ? 'text-amber-500' : 'text-gray-300 hover:text-gray-400'" :title="r.featured ? 'Top deal (shown first)' : 'Mark as top deal'">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            </button>
                        </td>
                        <td class="px-3 py-3 text-center">
                            <input type="checkbox" name="remove[]" :value="r.id" x-model="r.remove" class="rounded text-red-500 focus:ring-red-400">
                        </td>
                    </tr>
                </template>
                <tr x-show="!rows.length"><td colspan="9" class="px-4 py-12 text-center text-gray-400">No deals yet — add products above.</td></tr>
            </tbody>
        </table>
    </div>
    <div x-show="rows.length" class="sticky bottom-0 flex items-center justify-end gap-3 px-5 py-3 border-t border-gray-100 bg-white/95 backdrop-blur rounded-b-2xl">
        <span class="text-xs text-gray-500" x-show="dirty || removing">
            <span x-show="dirty">Unsaved changes</span><span x-show="removing"> · <span x-text="removing"></span> will be removed</span>
        </span>
        <button type="submit" class="rounded-xl px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700">Save deals</button>
    </div>
</form>
</div>

{{-- Settings --}}
<details class="group" @if($errors->hasAny(['name', 'slug', 'starts_at', 'ends_at'])) open @endif>
    <summary class="cursor-pointer list-none bg-white rounded-2xl shadow-sm px-5 py-4 flex items-center justify-between">
        <span>
            <span class="font-bold text-gray-800">Sale settings</span>
            <span class="block text-xs text-gray-500">Name, schedule, theme, layout and visibility</span>
        </span>
        <svg class="w-5 h-5 text-gray-400 transition group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </summary>
    <form action="{{ route('admin.flash-sales.update', $flashSale) }}" method="POST" class="mt-4">
        @csrf @method('PUT')
        @include('admin.flash-sales._form')
    </form>
</details>
@endsection
