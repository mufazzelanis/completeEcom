{{-- One Products-list row — also returned by ProductController::quickUpdate() so a saved
     quick edit replaces just this row in place (filters, page and scroll stay put).
     Click the category / brand / price / stock to open the quick-edit panel on that field. --}}
@php
    $thumb = image_thumb($product->image, 96);
    $rowData = [
        'id' => $product->id,
        'name' => $product->name,
        'type' => $product->type ?? 'simple',
        'image' => $thumb,
        'category_id' => $product->category_id,
        'subcategory_id' => $product->subcategory_id,
        'brand_id' => $product->brand_id,
        'price' => (float) $product->price,
        'sale_price' => $product->sale_price !== null ? (float) $product->sale_price : null,
        'stock' => (int) $product->stock,
        'is_active' => (bool) $product->is_active,
        'is_featured' => (bool) $product->is_featured,
        'marked_out_of_stock' => (bool) $product->marked_out_of_stock,
        'edit_url' => route('admin.products.edit', $product->id),
    ];
@endphp
<tr class="pl-row hover:bg-gray-50 transition{{ $isManualReorder ? ' cursor-move' : '' }}{{ $product->is_active ? '' : ' pl-off' }}"
    data-row="{{ $product->id }}" data-product="{{ json_encode($rowData) }}"
    @if($isManualReorder) data-product-id="{{ $product->id }}" draggable="true" @endif>
    <td class="pl-c-chk px-6 py-4">
        <input type="checkbox" value="{{ $product->id }}" aria-label="Select {{ $product->name }}"
               :checked="selected.includes({{ $product->id }})"
               @change="$event.target.checked ? selected.push({{ $product->id }}) : selected = selected.filter(i => i !== {{ $product->id }})"
               class="rounded text-indigo-600">
    </td>
    <td class="pl-c-prod px-6 py-4">
        <div class="flex items-center space-x-3">
            @if($isManualReorder)
                <span class="text-gray-300 text-lg leading-none select-none flex-shrink-0" title="Drag to reorder">⠿</span>
            @endif
            <div class="w-12 h-12 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                @if($thumb)
                    <img src="{{ $thumb }}" alt="" loading="lazy" @if($isManualReorder) draggable="false" @endif class="w-full h-full object-cover">
                @else
                    <div class="w-full h-full flex items-center justify-center text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                @endif
            </div>
            <div class="min-w-0">
                <button type="button" class="pl-name font-medium text-gray-800 text-sm text-left" title="{{ $product->name }} — click to quick edit" @click="edit($el, 'name')">{{ $product->name }}</button>
                <p class="text-xs text-gray-400">{{ $product->sku ?? 'No SKU' }}@if($product->barcode) · {{ $product->barcode }}@endif</p>
                <div class="flex flex-wrap items-center gap-1 mt-0.5">
                    <span class="text-xs px-1.5 py-0.5 rounded-full font-medium {{ $product->typeBadge() }}">{{ ucfirst($product->type ?? 'simple') }}</span>
                    @if($product->brand)
                        <button type="button" class="pl-link text-xs text-gray-500" @click="edit($el, 'brand_id')" title="Change brand">{{ $product->brand->name }}</button>
                    @else
                        <button type="button" class="pl-link text-xs" style="color:#d97706" @click="edit($el, 'brand_id')" title="Set a brand">+ brand</button>
                    @endif
                    @if($product->is_featured)<span class="text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded-full">Featured</span>@endif
                    @if($product->sale_price)<span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded-full">Sale</span>@endif
                </div>
                @if($product->seller)<p class="text-xs text-indigo-500 mt-0.5">Sold by {{ $product->seller->business_name }}</p>@endif
            </div>
        </div>
    </td>
    <td class="pl-c-cat px-6 py-4 text-sm text-gray-600" data-label="Category">
        <button type="button" class="pl-link text-left" @click="edit($el, 'category_id')" title="Change category">
            {{ $product->category?->name ?? '—' }}
            @if($product->subcategory)
                <span class="text-gray-400 text-xs block">↳ {{ $product->subcategory->name }}</span>
            @endif
        </button>
    </td>
    <td class="pl-c-price px-6 py-4 text-right" data-label="Price">
        <button type="button" class="pl-link text-right" @click="edit($el, 'price')" title="Change price">
            @if($product->sale_price)
                <span class="block font-semibold text-red-600 text-sm">৳{{ number_format($product->sale_price) }}</span>
                <span class="block text-xs text-gray-400 line-through">৳{{ number_format($product->price) }}</span>
            @else
                <span class="block font-semibold text-gray-900 text-sm">৳{{ number_format($product->price) }}</span>
            @endif
        </button>
    </td>
    <td class="pl-c-stock px-6 py-4 text-center" data-label="Stock">
        <button type="button" class="pl-link {{ $product->stock === 0 ? 'text-red-600' : ($product->isLowStock() ? 'text-yellow-600' : 'text-gray-800') }} font-semibold text-sm" @click="edit($el, 'stock')" title="Change stock">{{ $product->stock }}</button>
        {{-- Available / Out of stock switch — shoppers see "Out of stock" and can't order
             while it's off; the stock number above is left exactly as it is --}}
        <div class="mt-1.5" x-data="{ out: {{ $product->marked_out_of_stock ? 'true' : 'false' }}, busy: false,
                async flip() {
                    if (this.busy) return; this.busy = true;
                    try {
                        const r = await fetch(@js(route('admin.products.stock-switch', $product->id)), { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify({ out: !this.out }) });
                        const d = await r.json();
                        if (r.ok) { this.out = d.out; $dispatch('pl-toast', d.message); }
                    } finally { this.busy = false; }
                } }">
            <button type="button" role="switch" :aria-checked="(!out).toString()" @click="flip()" :disabled="busy"
                    class="inline-flex items-center gap-1.5 rounded-full pl-1 pr-2 py-0.5 text-[11px] font-semibold transition"
                    :style="out ? 'background:#ef44441f;color:#dc2626' : 'background:#10b9811f;color:#059669'"
                    :title="out ? 'Customers see Out of stock — click to make it available' : 'Customers can order — click to show it as Out of stock'">
                <span class="relative inline-block w-6 h-3.5 rounded-full transition" :style="out ? 'background:#fca5a5' : 'background:#6ee7b7'">
                    <span class="absolute top-0.5 w-2.5 h-2.5 rounded-full bg-white shadow transition-all" :style="out ? 'left:2px' : 'left:12px'"></span>
                </span>
                <span x-text="out ? 'Out of stock' : 'Available'">{{ $product->marked_out_of_stock ? 'Out of stock' : 'Available' }}</span>
            </button>
        </div>
    </td>
    <td class="pl-c-status px-6 py-4 text-center" data-label="Status">
        <button type="button" @click="toggle($el, 'is_active')" title="Click to {{ $product->is_active ? 'deactivate' : 'activate' }}"
                class="px-2 py-1 rounded-full text-xs font-medium transition hover:brightness-95 {{ $product->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
            {{ $product->is_active ? 'Active' : 'Inactive' }}
        </button>
    </td>
    <td class="pl-c-appr px-6 py-4 text-center">
        @php
            $approvalColor = match($product->approval_status) {
                'pending' => 'bg-yellow-100 text-yellow-700',
                'rejected' => 'bg-red-100 text-red-700',
                default => 'bg-green-100 text-green-700',
            };
        @endphp
        <span class="px-2 py-1 rounded-full text-xs font-medium {{ $approvalColor }}">{{ ucfirst($product->approval_status) }}</span>
        @if($product->approval_status === 'pending')
        <div class="flex flex-col items-center gap-1.5 mt-2">
            <form action="{{ route('admin.products.approve', $product->id) }}" method="POST" class="flex items-center gap-1">
                @csrf @method('PATCH')
                <select name="category_id" class="border border-gray-200 rounded-lg text-xs px-1 py-0.5">
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" {{ $product->category_id == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @foreach($cat->children ?? [] as $child)
                            <option value="{{ $child->id }}" {{ $product->category_id == $child->id ? 'selected' : '' }}>↳ {{ $child->name }}</option>
                        @endforeach
                    @endforeach
                </select>
                <button type="submit" class="text-green-600 hover:text-green-800 text-xs font-medium">Approve</button>
            </form>
            <form action="{{ route('admin.products.reject', $product->id) }}" method="POST"
                  onsubmit="const r = prompt('Rejection reason:'); if (!r) return false; this.querySelector('[name=rejection_reason]').value = r; return true;">
                @csrf @method('PATCH')
                <input type="hidden" name="rejection_reason">
                <button type="submit" class="text-red-500 hover:text-red-700 text-xs font-medium">Reject</button>
            </form>
        </div>
        @elseif($product->approval_status === 'rejected' && $product->rejection_reason)
        <p class="text-xs text-gray-400 mt-1 max-w-32 truncate" title="{{ $product->rejection_reason }}">{{ $product->rejection_reason }}</p>
        @endif
    </td>
    <td class="pl-c-act px-6 py-4 text-center">
        <div class="flex items-center justify-center gap-1">
            @if(request('sort_by') === 'manual' && request('category') == $product->category_id)
                <form action="{{ route('admin.products.move-up', $product->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" title="Move up"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 active:scale-90 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                </form>
                <form action="{{ route('admin.products.move-down', $product->id) }}" method="POST">
                    @csrf @method('PATCH')
                    <button type="submit" title="Move down"
                        class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 active:scale-90 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </form>
                <span class="w-px h-5 bg-gray-200 dark:bg-gray-700 mx-1"></span>
            @endif
            <button type="button" title="Quick edit — name, category, brand, price, stock" @click="edit($el)"
                class="pl-quick w-8 h-8 rounded-lg flex items-center justify-center active:scale-90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </button>
            <a href="{{ route('admin.products.edit', $product->id) }}" @if($isManualReorder) draggable="false" @endif title="Full edit"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-500/10 active:scale-90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </a>
            <form action="{{ route('admin.products.duplicate', $product->id) }}" method="POST">
                @csrf
                <button type="submit" title="Duplicate — create a copy to start a new variant from"
                    class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 active:scale-90 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </form>
            <button type="button" title="Delete" @click="remove($el)"
                class="w-8 h-8 rounded-lg flex items-center justify-center text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-500/10 active:scale-90 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    </td>
</tr>
