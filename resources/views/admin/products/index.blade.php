@extends('layouts.admin')
@section('title', 'Products')

@section('content')
@php
    // name lookups for the active-filter chips, and parent → sub-categories for the quick-edit panel
    $catNames = [];
    $subsByParent = [];
    foreach ($categories as $cat) {
        $catNames[$cat->id] = $cat->name;
        foreach ($cat->children ?? [] as $child) {
            $catNames[$child->id] = $cat->name . ' › ' . $child->name;
            $subsByParent[$cat->id][] = ['id' => $child->id, 'name' => $child->name];
        }
    }
    $brandNames = $brands->pluck('name', 'id')->all();
    // a chip's ✕ link: the same list without that one filter (never an empty query — that
    // would bring the remembered filters back, see ProductController::index())
    $without = function (string $key) {
        $q = array_filter(request()->except([$key, 'page']), fn ($v) => $v !== null && $v !== '');
        return route('admin.products.index', $q ?: ['sort_by' => 'latest']);
    };
    $chips = array_filter([
        'search' => request('search') ? '“' . request('search') . '”' : null,
        'category' => request('category') ? ($catNames[request('category')] ?? 'Category') : null,
        'brand' => request('brand') ? (request('brand') === 'none' ? 'No brand' : ($brandNames[request('brand')] ?? 'Brand')) : null,
        'type' => request('type') ? ucfirst(request('type')) : null,
        'status' => request('status') ? ucfirst(request('status')) : null,
        'approval_status' => request('approval_status') ? ucfirst(request('approval_status')) . ' approval' : null,
        'seller' => request('seller') ? 'Seller #' . request('seller') : null,
        'stock_status' => request('stock_status') ? ['in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock', 'marked' => 'Switched off'][request('stock_status')] ?? request('stock_status') : null,
        'on_sale' => request('on_sale') ? 'On sale' : null,
        'featured' => request('featured') ? 'Featured' : null,
    ]);
    $moreCount = count(array_intersect_key($chips, array_flip(['type', 'status', 'approval_status', 'seller', 'stock_status', 'on_sale', 'featured', 'brand'])));
@endphp

<style>
    /* ── Products list (page-local; the compiled Tailwind build may not carry new utilities) ── */
    .pl-sel { border: 1px solid #e5e7eb; border-radius: .75rem; padding: .5rem .75rem; font-size: .875rem; background: #fff; }
    .pl-sel:focus { outline: none; box-shadow: 0 0 0 2px #6366f1; }
    .pl-sel.is-set { border-color: #a5b4fc; background: #eef2ff; color: #3730a3; font-weight: 600; }
    .pl-chip { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .35rem .2rem .65rem; border-radius: 999px; background: #eef2ff; color: #4338ca; font-size: .75rem; font-weight: 600; }
    .pl-chip a { display: inline-flex; width: 1.1rem; height: 1.1rem; align-items: center; justify-content: center; border-radius: 999px; color: #6366f1; }
    .pl-chip a:hover { background: #c7d2fe; color: #312e81; }
    .pl-name { display: block; max-width: 16rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .pl-name:hover, .pl-link:hover { color: #4f46e5; text-decoration: underline dotted; text-underline-offset: 3px; }
    .pl-link { cursor: pointer; }
    .pl-quick { color: #a78bfa; }
    .pl-quick:hover { color: #7c3aed; background: #f5f3ff; }
    .pl-off .pl-c-prod img { opacity: .55; }
    .pl-flash { animation: plFlash 1.6s ease-out; }
    @keyframes plFlash { 0%, 30% { background: #ecfdf5; } 100% { background: transparent; } }
    .pl-bulk { position: sticky; top: 0; z-index: 30; }
    .pl-filter-more { display: contents; }
    .pl-more-btn, .pl-mobile-only { display: none; }
    .pl-bulk select { border: 1px solid #c7d2fe; border-radius: .6rem; padding: .3rem .5rem; font-size: .8rem; background: #fff; max-width: 11rem; }
    .pl-bulk .pl-bbtn { font-size: .8rem; font-weight: 600; padding: .3rem .6rem; border-radius: .6rem; transition: background-color .15s; }
    .pl-bulk .pl-bbtn:hover { background: #fff; }
    .pl-loading { opacity: .55; pointer-events: none; transition: opacity .15s; }

    /* quick-edit panel: right-hand drawer on desktop, bottom sheet on phones */
    .pl-overlay { position: fixed; inset: 0; z-index: 80; background: rgba(17,24,39,.45); display: flex; justify-content: flex-end; }
    .pl-sheet { background: #fff; width: 27rem; max-width: 100%; height: 100%; display: flex; flex-direction: column; box-shadow: -10px 0 40px rgba(0,0,0,.15); }
    .pl-sheet label { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin-bottom: .3rem; }
    .pl-in { width: 100%; border: 1px solid #e5e7eb; border-radius: .7rem; padding: .6rem .75rem; font-size: .95rem; background: #fff; color: #111827; }
    .pl-in:focus { outline: none; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99,102,241,.18); }
    .pl-in.is-bad { border-color: #ef4444; }
    .pl-in:disabled { background: #f3f4f6; color: #9ca3af; }
    .pl-err { color: #dc2626; font-size: .75rem; margin-top: .25rem; }
    .pl-step { display: flex; border: 1px solid #e5e7eb; border-radius: .7rem; overflow: hidden; }
    .pl-step button { width: 2.75rem; font-size: 1.2rem; font-weight: 700; color: #4b5563; background: #f9fafb; }
    .pl-step button:hover { background: #eef2ff; color: #4f46e5; }
    .pl-step input { flex: 1; min-width: 0; border: 0; text-align: center; font-weight: 800; font-size: 1.05rem; color: #111827; background: #fff; padding: .55rem 0; -moz-appearance: textfield; appearance: textfield; }
    .pl-step input::-webkit-outer-spin-button, .pl-step input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .pl-step input:focus { outline: none; }
    .pl-tg { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .7rem .85rem; border: 1px solid #e5e7eb; border-radius: .8rem; cursor: pointer; }
    .pl-tg + .pl-tg { margin-top: .5rem; }
    .pl-sw { position: relative; width: 2.6rem; height: 1.5rem; border-radius: 999px; background: #d1d5db; flex-shrink: 0; transition: background-color .15s; }
    .pl-sw::after { content: ''; position: absolute; top: .2rem; left: .2rem; width: 1.1rem; height: 1.1rem; border-radius: 999px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,.25); transition: left .15s; }
    .pl-sw.is-on { background: #10b981; }
    .pl-sw.is-on::after { left: 1.3rem; }
    .pl-toast { position: fixed; left: 50%; bottom: 1.5rem; transform: translateX(-50%); z-index: 90; max-width: calc(100% - 2rem); padding: .7rem 1.1rem; border-radius: .9rem; color: #fff; font-size: .875rem; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,.2); }

    .dark .pl-sel, .dark .pl-in, .dark .pl-bulk select, .dark .pl-step input { background: #111827; border-color: #374151; color: #f3f4f6; }
    .dark .pl-sheet { background: #1f2937; }
    .dark .pl-step { border-color: #374151; }
    .dark .pl-step button { background: #111827; color: #d1d5db; }
    .dark .pl-tg { border-color: #374151; }
    .dark .pl-in:disabled { background: #0b1220; }

    @media (max-width: 767px) {
        .pl-filter-more { display: none; }
        .pl-filters.is-open .pl-filter-more { display: flex; flex-wrap: wrap; gap: .5rem; width: 100%; }
        .pl-more-btn, .pl-mobile-only { display: block; }
        .pl-search { width: 100% !important; }
        .pl-search-wrap { flex: 1 1 100%; }
        .pl-filters .pl-sel { flex: 1 1 calc(50% - .5rem); min-width: 0; }

        /* table → cards */
        .pl-table thead { display: none; }
        .pl-table, .pl-table tbody { display: block; }
        .pl-table tr.pl-row { display: grid; grid-template-columns: 1.5rem 1fr 1fr; grid-template-areas: "chk prod prod" ". cat price" ". stock status" ". appr act"; gap: .55rem .75rem; padding: .9rem 1rem; border-bottom: 1px solid #f3f4f6; }
        .pl-table tr.pl-row > td { display: block; padding: 0 !important; text-align: left !important; }
        .pl-c-chk { grid-area: chk; padding-top: .9rem !important; }
        .pl-c-prod { grid-area: prod; }
        .pl-c-cat { grid-area: cat; }
        .pl-c-price { grid-area: price; }
        .pl-c-stock { grid-area: stock; }
        .pl-c-status { grid-area: status; }
        .pl-c-appr { grid-area: appr; align-self: center; }
        .pl-c-act { grid-area: act; }
        .pl-c-act > div { justify-content: flex-end !important; }
        .pl-c-price button, .pl-c-price span { text-align: left !important; }
        .pl-table td[data-label]::before { content: attr(data-label); display: block; font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #9ca3af; margin-bottom: .15rem; }
        .pl-name { max-width: 100%; white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }

        .pl-overlay { align-items: flex-end; }
        .pl-sheet { width: 100%; height: auto; max-height: 92vh; border-radius: 1.25rem 1.25rem 0 0; box-shadow: 0 -10px 40px rgba(0,0,0,.2); }
        .pl-toast { bottom: 5.5rem; }
    }
</style>

<div class="mb-6 bg-white rounded-2xl shadow-sm p-4">
    {{-- Filters apply as soon as one changes (no "Filter" click needed) and are remembered:
         after an edit, delete, duplicate or bulk action the list comes back exactly as it was. --}}
    <form action="{{ route('admin.products.index') }}" method="GET" id="admin-product-filter"
          class="pl-filters" x-data="{ more: false }" :class="more && 'is-open'"
          @change="if ($event.target.name !== 'search') $el.requestSubmit()">
        <!-- Row 1: Search + primary filters -->
        <div class="flex flex-wrap items-center gap-2 mb-3">
            <!-- Search with auto-suggest -->
            <div class="relative pl-search-wrap" x-data="{
                query: @js(request('search', '')),
                results: [],
                open: false,
                async fetchSuggestions() {
                    if (this.query.length < 2) { this.open = false; return; }
                    const res = await fetch('{{ route('admin.search.suggest') }}?q=' + encodeURIComponent(this.query));
                    const data = await res.json();
                    this.results = data.products;
                    this.open = this.results.length > 0;
                }
            }" @click.outside="open = false">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <input type="search" name="search"
                        x-model="query"
                        @input.debounce.300ms="fetchSuggestions()"
                        @focus="query.length > 1 && fetchSuggestions()"
                        @keydown.escape="open = false"
                        placeholder="Search name, SKU, barcode, #ID…"
                        class="pl-search border border-gray-200 rounded-xl pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-64"
                        autocomplete="off" enterkeyhint="search">
                </div>
                <div x-show="open" x-cloak
                     class="absolute top-full left-0 bg-white shadow-xl rounded-xl border border-gray-100 z-50 mt-1 w-80 overflow-hidden" style="max-width: calc(100vw - 3rem);">
                    <template x-for="product in results" :key="product.url">
                        <a :href="product.url" @click="open = false"
                           class="flex items-center px-3 py-2.5 hover:bg-indigo-50 gap-3">
                            <div class="w-9 h-9 bg-gray-100 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center">
                                <img x-show="product.image" :src="product.image" class="w-full h-full object-cover">
                                <svg x-show="!product.image" class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 truncate" x-text="product.name"></p>
                                <p class="text-xs text-gray-500" x-text="product.sku + ' · ' + product.price"></p>
                            </div>
                            <span :class="product.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                                  class="text-xs px-1.5 py-0.5 rounded-full flex-shrink-0"
                                  x-text="product.is_active ? 'Active' : 'Inactive'"></span>
                        </a>
                    </template>
                </div>
            </div>

            <select name="category" class="pl-sel{{ request('category') ? ' is-set' : '' }}" aria-label="Category">
                <option value="">All Categories</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @foreach($cat->children ?? [] as $child)
                        <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }}>&nbsp;&nbsp;↳ {{ $child->name }}</option>
                    @endforeach
                @endforeach
            </select>

            <button type="button" @click="more = !more" class="pl-more-btn pl-sel" style="flex: 1 1 calc(50% - .5rem); font-weight: 600;">
                <span x-text="more ? 'Hide filters' : 'More filters'">More filters</span>@if($moreCount) <span style="color:#4f46e5">({{ $moreCount }})</span>@endif
            </button>

            <div class="pl-filter-more">
                <select name="brand" class="pl-sel{{ request('brand') ? ' is-set' : '' }}" aria-label="Brand">
                    <option value="">All Brands</option>
                    <option value="none" {{ request('brand') === 'none' ? 'selected' : '' }}>— No brand set —</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}" {{ request('brand') == $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                    @endforeach
                </select>

                <select name="type" class="pl-sel{{ request('type') ? ' is-set' : '' }}" aria-label="Type">
                    <option value="">All Types</option>
                    <option value="simple"   {{ request('type') === 'simple'   ? 'selected' : '' }}>Simple</option>
                    <option value="variable" {{ request('type') === 'variable' ? 'selected' : '' }}>Variable</option>
                    <option value="bundle"   {{ request('type') === 'bundle'   ? 'selected' : '' }}>Bundle</option>
                    <option value="digital"  {{ request('type') === 'digital'  ? 'selected' : '' }}>Digital</option>
                </select>

                <select name="status" class="pl-sel{{ request('status') ? ' is-set' : '' }}" aria-label="Status">
                    <option value="">Any Status</option>
                    <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>

                <select name="stock_status" class="pl-sel{{ request('stock_status') ? ' is-set' : '' }}" aria-label="Stock">
                    <option value="">Any Stock</option>
                    <option value="in"  {{ request('stock_status') === 'in'  ? 'selected' : '' }}>In Stock</option>
                    <option value="low" {{ request('stock_status') === 'low' ? 'selected' : '' }}>Low Stock</option>
                    <option value="out" {{ request('stock_status') === 'out' ? 'selected' : '' }}>Out of Stock</option>
                    <option value="marked" {{ request('stock_status') === 'marked' ? 'selected' : '' }}>Switched off (shown out of stock)</option>
                </select>

                <select name="approval_status" class="pl-sel{{ request('approval_status') ? ' is-set' : '' }}" aria-label="Approval">
                    <option value="">Any Approval</option>
                    <option value="pending"  {{ request('approval_status') === 'pending'  ? 'selected' : '' }}>Pending Approval</option>
                    <option value="approved" {{ request('approval_status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('approval_status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>

                @if($vendors->isNotEmpty())
                <select name="seller" class="pl-sel{{ request('seller') ? ' is-set' : '' }}" aria-label="Seller">
                    <option value="">All Sellers</option>
                    @foreach($vendors as $vendor)
                        <option value="{{ $vendor->id }}" {{ request('seller') == $vendor->id ? 'selected' : '' }}>{{ $vendor->business_name }}</option>
                    @endforeach
                </select>
                @endif

                <label class="pl-sel flex items-center gap-1.5 cursor-pointer text-gray-700 select-none{{ request('on_sale') ? ' is-set' : '' }}">
                    <input type="checkbox" name="on_sale" value="1" {{ request('on_sale') ? 'checked' : '' }} class="rounded text-indigo-600">
                    On Sale
                </label>

                <label class="pl-sel flex items-center gap-1.5 cursor-pointer text-gray-700 select-none{{ request('featured') ? ' is-set' : '' }}">
                    <input type="checkbox" name="featured" value="1" {{ request('featured') ? 'checked' : '' }} class="rounded text-indigo-600">
                    Featured
                </label>
            </div>
        </div>

        <!-- Row 2: sort + actions -->
        <div class="flex flex-wrap items-center gap-2">
            <select name="sort_by" class="pl-sel" aria-label="Sort">
                <option value="latest"     {{ request('sort_by', 'latest') === 'latest'     ? 'selected' : '' }}>Newest First</option>
                <option value="oldest"     {{ request('sort_by') === 'oldest'     ? 'selected' : '' }}>Oldest First</option>
                <option value="name_asc"   {{ request('sort_by') === 'name_asc'   ? 'selected' : '' }}>Name A–Z</option>
                <option value="name_desc"  {{ request('sort_by') === 'name_desc'  ? 'selected' : '' }}>Name Z–A</option>
                <option value="price_asc"  {{ request('sort_by') === 'price_asc'  ? 'selected' : '' }}>Price Low–High</option>
                <option value="price_desc" {{ request('sort_by') === 'price_desc' ? 'selected' : '' }}>Price High–Low</option>
                <option value="stock_asc"  {{ request('sort_by') === 'stock_asc'  ? 'selected' : '' }}>Stock Low–High</option>
                <option value="stock_desc" {{ request('sort_by') === 'stock_desc' ? 'selected' : '' }}>Stock High–Low</option>
                <option value="manual"     {{ request('sort_by') === 'manual'     ? 'selected' : '' }}>Manual (Custom Order)</option>
            </select>

            <button type="submit" class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-700 transition">
                Search
            </button>
            @if($chips || (request('sort_by') && request('sort_by') !== 'latest'))
                <a href="{{ route('admin.products.index', ['reset' => 1]) }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50">Clear all</a>
            @endif

            <div class="ml-auto">
                <a href="{{ route('admin.products.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Product
                </a>
            </div>
        </div>

        <!-- Active filter chips — ✕ removes just that one -->
        @if($chips)
        <div class="flex flex-wrap items-center gap-1.5 mt-3 pt-3 border-t border-gray-100">
            <span class="text-xs text-gray-400">Filtered by:</span>
            @foreach($chips as $key => $label)
                <span class="pl-chip">{{ $label }}<a href="{{ $without($key) }}" title="Remove this filter" aria-label="Remove filter {{ $label }}">✕</a></span>
            @endforeach
            <span class="text-xs text-gray-500 ml-1 font-semibold" id="pl-count">{{ $products->total() }} result(s)</span>
            <span class="text-xs text-gray-400">· kept while you edit</span>
        </div>
        @endif

        @if(request('sort_by') === 'manual' && !request('category'))
        <div class="mt-3 pt-3 border-t border-gray-100 text-xs text-yellow-700 bg-yellow-50 rounded-lg px-3 py-2">
            Pick a single category above to drag-reorder its products — manual order is set per category.
        </div>
        @endif
    </form>
</div>

@if($isManualReorder)
<div class="mb-4 bg-indigo-50 border border-indigo-100 text-indigo-800 text-sm rounded-2xl px-4 py-3 flex items-center gap-2.5">
    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
    <span>
        Drag rows by <strong>⠿</strong> to reorder — this is the display order on
        <strong>{{ $reorderCategoryName ?? 'this category' }}</strong>'s live shop page. Every product in the
        category is shown here on one page so ordering stays consistent; changes save automatically as you drop.
    </span>
    <span id="reorder-status" class="ml-auto text-xs text-indigo-500 flex-shrink-0"></span>
</div>
@endif

@if($pendingApprovalCount > 0 && request('approval_status') !== 'pending')
<div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-xl text-sm flex items-center justify-between">
    <span>{{ $pendingApprovalCount }} vendor-submitted product(s) waiting for approval.</span>
    <a href="{{ route('admin.products.index', ['approval_status' => 'pending']) }}" class="font-semibold hover:underline">Review now →</a>
</div>
@endif

<script>
    // Products list: selection + bulk actions, the quick-edit panel, and in-place refresh.
    // Every save is a fetch — the page never reloads, so filters, page and scroll stay put.
    window.productList = function (cfg) {
        const headers = () => ({ 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content });
        const FIELDS = ['name', 'category_id', 'subcategory_id', 'brand_id', 'price', 'sale_price', 'stock', 'is_active', 'is_featured', 'marked_out_of_stock'];

        return {
            selected: [],
            pageIds: cfg.pageIds,
            totalMatching: cfg.total,
            selectingAll: false,
            busy: false,
            bulkCategory: '',
            bulkBrand: '',
            toast: { show: false, msg: '', err: false },
            ed: null, orig: null, errors: {}, saving: false,

            notify(msg, err = false) {
                this.toast = { show: true, msg, err };
                clearTimeout(this._t);
                this._t = setTimeout(() => this.toast.show = false, err ? 5000 : 2600);
            },
            subs() { return (this.ed && cfg.subs[this.ed.category_id]) || []; },
            money(v) { return '৳' + Math.round(+v || 0).toLocaleString('en-IN'); },
            get discount() {
                const p = +this.ed?.price, s = this.ed?.sale_price;
                return (s !== '' && s !== null && p > 0 && +s < p) ? Math.round((1 - s / p) * 100) : 0;
            },
            get dirty() { return this.ed ? FIELDS.filter(f => String(this.ed[f] ?? '') !== String(this.orig[f] ?? '')) : []; },

            async selectAllMatching() {
                this.selectingAll = true;
                try {
                    const r = await fetch(cfg.idsUrl + location.search, { headers: { Accept: 'application/json' } });
                    this.selected = (await r.json()).ids;
                } finally { this.selectingAll = false; }
            },

            // ── quick edit ─────────────────────────────────────────────
            edit(el, field = 'name') {
                const p = JSON.parse(el.closest('[data-row]').dataset.product);
                p.sale_price = p.sale_price ?? ''; p.subcategory_id = p.subcategory_id ?? ''; p.brand_id = p.brand_id ?? '';
                this.ed = p; this.orig = { ...p }; this.errors = {};
                this.$nextTick(() => {
                    const f = this.$refs.qe?.querySelector('[data-f="' + field + '"]');
                    if (f) { f.focus(); f.select?.(); }
                });
            },
            close() {
                if (this.saving) return;
                this.ed = null;
            },
            step(n) { this.ed.stock = Math.max(0, (parseInt(this.ed.stock) || 0) + n); },
            async save() {
                if (!this.ed || this.saving) return;
                const changed = this.dirty;
                if (!changed.length) { this.ed = null; return; }
                const body = {};
                changed.forEach(f => body[f] = this.ed[f] === '' ? null : this.ed[f]);
                // a new category always sends its sub-category (or none) along with it
                if (changed.includes('category_id')) body.subcategory_id = this.ed.subcategory_id || null;
                await this.patch(this.ed.id, body, true);
            },
            async toggle(el, field) {
                const p = JSON.parse(el.closest('[data-row]').dataset.product);
                await this.patch(p.id, { [field]: !p[field] });
            },
            async patch(id, body, fromPanel = false) {
                this.saving = true; this.errors = {};
                try {
                    const r = await fetch(cfg.quickUrl.replace('__ID__', id), { method: 'PATCH', headers: headers(), body: JSON.stringify(body) });
                    const d = await r.json().catch(() => ({}));
                    if (r.status === 422) {
                        this.errors = d.errors || {};
                        if (!fromPanel) this.notify(Object.values(this.errors)[0]?.[0] || 'Please check the values.', true);
                        return;
                    }
                    if (!r.ok) throw new Error(d.message || 'Could not save — please try again.');
                    this.replaceRow(id, d.row);
                    if (fromPanel) this.ed = null;
                    this.notify('✓ ' + d.message);
                } catch (e) {
                    this.notify(e.message || 'Network error — please try again.', true);
                } finally { this.saving = false; }
            },
            replaceRow(id, html) {
                const old = document.querySelector('[data-row="' + id + '"]');
                if (!old || !html) return;
                const t = document.createElement('tbody');
                t.innerHTML = html.trim();
                const row = t.firstElementChild;
                old.replaceWith(row);
                row.classList.add('pl-flash');
            },
            async remove(el) {
                const p = JSON.parse(el.closest('[data-row]').dataset.product);
                if (!(await showConfirmModal('Delete "' + p.name + '"? This can\'t be undone.'))) return;
                const r = await fetch(cfg.destroyUrl.replace('__ID__', p.id), { method: 'DELETE', headers: headers() });
                const d = await r.json().catch(() => ({}));
                if (!r.ok) return this.notify(d.message || 'Could not delete.', true);
                this.selected = this.selected.filter(i => i !== p.id);
                await this.refresh();
                this.notify(d.message);
            },

            // ── bulk ───────────────────────────────────────────────────
            async bulk(action, extra = {}) {
                if (!this.selected.length || this.busy) return;
                if (action === 'delete' && !(await showConfirmModal('Delete ' + this.selected.length + ' product(s)? This can\'t be undone.'))) return;
                if (action === 'set_category' && !extra.category_id) return this.notify('Pick a category first.', true);
                this.busy = true;
                try {
                    const r = await fetch(cfg.bulkUrl, { method: 'POST', headers: headers(), body: JSON.stringify({ ids: this.selected, bulk_action: action, ...extra }) });
                    const d = await r.json().catch(() => ({}));
                    if (!r.ok) throw new Error(d.message || 'Bulk update failed.');
                    if (action === 'delete') this.selected = [];
                    this.bulkCategory = ''; this.bulkBrand = '';
                    await this.refresh();
                    this.notify('✓ ' + d.message);
                } catch (e) {
                    this.notify(e.message, true);
                } finally { this.busy = false; }
            },
            // re-reads this same filtered page and swaps in the fresh rows/totals
            async refresh() {
                const box = document.getElementById('pl-list');
                box?.classList.add('pl-loading');
                try {
                    const r = await fetch(location.href, { headers: { Accept: 'text/html' }, cache: 'no-store' });
                    const doc = new DOMParser().parseFromString(await r.text(), 'text/html');
                    ['pl-body', 'pl-foot', 'pl-count'].forEach(id => {
                        const a = document.getElementById(id), b = doc.getElementById(id);
                        if (a && b) a.innerHTML = b.innerHTML;
                    });
                    const c = doc.getElementById('pl-cfg');
                    if (c) { const j = JSON.parse(c.textContent); this.pageIds = j.pageIds; this.totalMatching = j.total; }
                } finally { box?.classList.remove('pl-loading'); }
            },
        };
    };
</script>
<script type="application/json" id="pl-cfg">@json(['pageIds' => $products->pluck('id'), 'total' => $products->total()])</script>

<div x-data="productList(@js([
        'pageIds' => $products->pluck('id'),
        'total' => $products->total(),
        'subs' => (object) $subsByParent,
        'quickUrl' => route('admin.products.quick-update', '__ID__'),
        'destroyUrl' => route('admin.products.destroy', '__ID__'),
        'bulkUrl' => route('admin.products.bulk-action'),
        'idsUrl' => route('admin.products.ids'),
    ]))" @pl-toast="notify($event.detail)" @keydown.escape.window="close()">

    {{-- Bulk bar — sticks to the top while scrolling; every action updates the list in place --}}
    <div x-show="selected.length > 0" x-cloak
         class="pl-bulk mb-4 bg-indigo-50 border border-indigo-100 rounded-2xl px-4 py-3 shadow-sm" :class="busy && 'pl-loading'">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-bold text-indigo-800" x-text="selected.length + ' selected'"></span>
            <button type="button" x-show="totalMatching > pageIds.length && pageIds.every(id => selected.includes(id)) && selected.length < totalMatching"
                    x-cloak @click="selectAllMatching()" :disabled="selectingAll"
                    class="text-sm text-indigo-700 hover:text-indigo-900 font-medium underline disabled:opacity-50">
                <span x-show="!selectingAll" x-text="'Select all ' + totalMatching + ' matching'"></span>
                <span x-show="selectingAll" x-cloak>Selecting…</span>
            </button>
            <button type="button" @click="selected = []" class="text-xs text-gray-500 hover:text-gray-700 ml-auto px-2 py-1">✕ Clear</button>
        </div>
        <div class="flex flex-wrap items-center gap-1.5 mt-2">
            <button type="button" @click="bulk('activate')" class="pl-bbtn" style="color:#047857">Activate</button>
            <button type="button" @click="bulk('deactivate')" class="pl-bbtn" style="color:#4b5563">Deactivate</button>
            <button type="button" @click="bulk('mark_available')" class="pl-bbtn" style="color:#059669">Show Available</button>
            <button type="button" @click="bulk('mark_out')" class="pl-bbtn" style="color:#dc2626">Show Out of Stock</button>
            <button type="button" @click="bulk('feature')" class="pl-bbtn" style="color:#a16207">★ Featured</button>
            <button type="button" @click="bulk('unfeature')" class="pl-bbtn" style="color:#6b7280">☆ Unfeature</button>
            <span class="flex items-center gap-1">
                <select x-model="bulkCategory" aria-label="Move selected to category">
                    <option value="">Set category…</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @foreach($cat->children ?? [] as $child)
                            <option value="{{ $child->id }}">&nbsp;&nbsp;↳ {{ $child->name }}</option>
                        @endforeach
                    @endforeach
                </select>
                <button type="button" x-show="bulkCategory" x-cloak @click="bulk('set_category', { category_id: bulkCategory })" class="pl-bbtn" style="background:#4f46e5;color:#fff">Apply</button>
            </span>
            <span class="flex items-center gap-1">
                <select x-model="bulkBrand" aria-label="Set brand for selected">
                    <option value="">Set brand…</option>
                    <option value="none">— Remove brand —</option>
                    @foreach($brands as $brand)
                        <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                    @endforeach
                </select>
                <button type="button" x-show="bulkBrand" x-cloak @click="bulk('set_brand', { brand_id: bulkBrand === 'none' ? null : bulkBrand })" class="pl-bbtn" style="background:#4f46e5;color:#fff">Apply</button>
            </span>
            <button type="button" @click="bulk('delete')" class="pl-bbtn ml-auto" style="color:#dc2626">Delete</button>
        </div>
    </div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden" id="pl-list">
    <div class="overflow-x-auto">
    <table class="w-full pl-table">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">
                    <input type="checkbox"
                           :checked="pageIds.length > 0 && pageIds.every(id => selected.includes(id))"
                           @change="selected = $event.target.checked ? [...new Set([...selected, ...pageIds])] : selected.filter(id => !pageIds.includes(id))"
                           class="rounded text-indigo-600" title="Select all on this page">
                </th>
                <th class="px-6 py-3 text-left">Product</th>
                <th class="px-6 py-3 text-left">Category</th>
                <th class="px-6 py-3 text-right">Price</th>
                <th class="px-6 py-3 text-center">Stock</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Approval</th>
                <th class="px-6 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50" id="pl-body">
            @forelse($products as $product)
                @include('admin.products._row')
            @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-400">No products match these filters. <a href="{{ route('admin.products.index', ['reset' => 1]) }}" class="text-indigo-600">Clear filters</a> or <a href="{{ route('admin.products.create') }}" class="text-indigo-600">add one</a>.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
    <div class="px-4 sm:px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-center sm:justify-between gap-3" id="pl-foot">
        <span class="text-sm text-gray-500">{{ $products->total() }} product(s) · page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
        {{ $products->links() }}
    </div>
</div>

    {{-- Mobile: select-all for the cards (the table header is hidden there) --}}
    <div class="pl-mobile-only mt-3 text-center" x-show="pageIds.length" x-cloak>
        <button type="button" class="text-sm font-semibold" style="color:#4f46e5"
                @click="selected = pageIds.every(id => selected.includes(id)) ? selected.filter(id => !pageIds.includes(id)) : [...new Set([...selected, ...pageIds])]"
                x-text="pageIds.every(id => selected.includes(id)) ? 'Unselect this page' : 'Select all on this page'"></button>
    </div>

    {{-- Quick edit panel --}}
    <template x-if="ed">
        <div class="pl-overlay" @click.self="close()" role="dialog" aria-modal="true" aria-label="Quick edit product">
            <form class="pl-sheet" x-ref="qe" @submit.prevent="save()" @keydown.ctrl.enter="save()" @keydown.meta.enter="save()"
                  x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-4">
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="w-11 h-11 rounded-xl bg-gray-100 overflow-hidden flex-shrink-0">
                        <img x-show="ed.image" :src="ed.image" alt="" class="w-full h-full object-cover">
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold" style="color:#7c3aed">⚡ Quick edit · <span class="capitalize" x-text="ed.type"></span></p>
                        <p class="text-sm font-bold text-gray-900 dark:text-white truncate" x-text="orig.name"></p>
                    </div>
                    <button type="button" @click="close()" class="w-9 h-9 rounded-full flex items-center justify-center text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700" aria-label="Close">✕</button>
                </div>

                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-4">
                    <div>
                        <label for="qe-name">Product name</label>
                        <input id="qe-name" data-f="name" class="pl-in" :class="errors.name && 'is-bad'" x-model="ed.name" required maxlength="255">
                        <p class="pl-err" x-show="errors.name" x-text="errors.name?.[0]"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="qe-cat">Category</label>
                            <select id="qe-cat" data-f="category_id" class="pl-in" x-model="ed.category_id" @change="ed.subcategory_id = ''">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="qe-sub">Sub-category</label>
                            <select id="qe-sub" class="pl-in" x-model="ed.subcategory_id" :disabled="!subs().length" :class="errors.subcategory_id && 'is-bad'">
                                <option value="">— None —</option>
                                <template x-for="s in subs()" :key="s.id">
                                    <option :value="s.id" x-text="s.name" :selected="String(s.id) === String(ed.subcategory_id)"></option>
                                </template>
                            </select>
                            <p class="pl-err" x-show="errors.subcategory_id" x-text="errors.subcategory_id?.[0]"></p>
                        </div>
                    </div>

                    <div>
                        <label for="qe-brand">Brand</label>
                        <select id="qe-brand" data-f="brand_id" class="pl-in" x-model="ed.brand_id">
                            <option value="">— No brand —</option>
                            @foreach($brands as $brand)
                                <option value="{{ $brand->id }}">{{ $brand->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="qe-price">Regular price (৳)</label>
                            <input id="qe-price" data-f="price" type="number" min="0" step="0.01" inputmode="decimal" class="pl-in" :class="errors.price && 'is-bad'" x-model="ed.price" required>
                            <p class="pl-err" x-show="errors.price" x-text="errors.price?.[0]"></p>
                        </div>
                        <div>
                            <label for="qe-sale">Sale price (৳) <span x-show="discount" x-text="'−' + discount + '%'" style="color:#dc2626;text-transform:none"></span></label>
                            <input id="qe-sale" data-f="sale_price" type="number" min="0" step="0.01" inputmode="decimal" class="pl-in" :class="errors.sale_price && 'is-bad'" x-model="ed.sale_price" placeholder="No sale">
                            <p class="pl-err" x-show="errors.sale_price" x-text="errors.sale_price?.[0]"></p>
                        </div>
                    </div>

                    <div>
                        <label for="qe-stock">Stock</label>
                        <template x-if="ed.type !== 'variable'">
                            <div>
                                <div class="pl-step">
                                    <button type="button" @click="step(-1)" aria-label="Decrease stock">−</button>
                                    <input id="qe-stock" data-f="stock" type="number" min="0" inputmode="numeric" x-model.number="ed.stock">
                                    <button type="button" @click="step(1)" aria-label="Increase stock">+</button>
                                </div>
                                <p class="text-xs text-gray-400 mt-1" x-show="+ed.stock !== +orig.stock" x-text="'Was ' + orig.stock + ' → ' + (+ed.stock > +orig.stock ? '+' : '') + (ed.stock - orig.stock) + ' (logged in stock history)'"></p>
                                <p class="pl-err" x-show="errors.stock" x-text="errors.stock?.[0]"></p>
                            </div>
                        </template>
                        <template x-if="ed.type === 'variable'">
                            <p class="text-sm text-gray-500"><span class="font-bold" x-text="ed.stock"></span> — variable product: stock is set per colour/size on the <a :href="ed.edit_url" class="text-indigo-600 underline">full edit page</a>.</p>
                        </template>
                    </div>

                    <div>
                        <div class="pl-tg" @click="ed.is_active = !ed.is_active">
                            <span><span class="block text-sm font-semibold text-gray-800 dark:text-gray-100">Active</span><span class="block text-xs text-gray-500">Visible in the shop</span></span>
                            <span class="pl-sw" :class="ed.is_active && 'is-on'" role="switch" :aria-checked="ed.is_active.toString()"></span>
                        </div>
                        <div class="pl-tg" @click="ed.marked_out_of_stock = !ed.marked_out_of_stock">
                            <span><span class="block text-sm font-semibold text-gray-800 dark:text-gray-100">Available to order</span><span class="block text-xs text-gray-500">Off = shows “Out of stock” (stock number kept)</span></span>
                            <span class="pl-sw" :class="!ed.marked_out_of_stock && 'is-on'" role="switch" :aria-checked="(!ed.marked_out_of_stock).toString()"></span>
                        </div>
                        <div class="pl-tg" @click="ed.is_featured = !ed.is_featured">
                            <span><span class="block text-sm font-semibold text-gray-800 dark:text-gray-100">Featured</span><span class="block text-xs text-gray-500">Shown in featured sections</span></span>
                            <span class="pl-sw" :class="ed.is_featured && 'is-on'" role="switch" :aria-checked="ed.is_featured.toString()"></span>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2 px-5 py-3 border-t border-gray-100 dark:border-gray-700" style="padding-bottom: max(.75rem, env(safe-area-inset-bottom));">
                    <a :href="ed.edit_url" class="text-sm font-medium text-gray-500 hover:text-indigo-600">Full edit →</a>
                    <span class="ml-auto text-xs text-gray-400 hidden sm:inline" x-text="dirty.length ? dirty.length + ' change(s) · Ctrl+Enter' : 'No changes'"></span>
                    <button type="button" @click="close()" class="px-4 py-2 rounded-xl text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700">Cancel</button>
                    <button type="submit" :disabled="saving" class="px-5 py-2 rounded-xl text-sm font-bold text-white disabled:opacity-60" style="background:#4f46e5">
                        <span x-text="saving ? 'Saving…' : 'Save'">Save</span>
                    </button>
                </div>
            </form>
        </div>
    </template>

    {{-- Toast --}}
    <div x-show="toast.show" x-cloak x-transition.opacity class="pl-toast" :style="'background:' + (toast.err ? '#dc2626' : '#111827')" role="status" aria-live="polite" x-text="toast.msg"></div>
</div>
@endsection

@if($isManualReorder)
@push('scripts')
<script>
// Manual-reorder drag & drop for the admin Products list. Whole <tr> is the drag
// handle (draggable="true"), reordered live by moving DOM nodes on dragover — no
// virtual-list/Alpine state to keep in sync, so every row's own forms (Approve/
// Reject/Delete/Move) keep working untouched since they're never re-rendered,
// just relocated. The new order is saved to admin.products.reorder on drop.
document.addEventListener('DOMContentLoaded', () => {
    const tbody = document.querySelector('table tbody');
    if (!tbody) return;

    let dragging = null;

    tbody.querySelectorAll('tr[data-product-id]').forEach(row => {
        row.addEventListener('dragstart', () => {
            dragging = row;
            row.classList.add('opacity-40');
        });
        row.addEventListener('dragend', () => {
            row.classList.remove('opacity-40');
            dragging = null;
            saveOrder();
        });
        row.addEventListener('dragover', (e) => {
            e.preventDefault();
            if (!dragging || dragging === row) return;
            const rect = row.getBoundingClientRect();
            const after = (e.clientY - rect.top) > rect.height / 2;
            row.parentNode.insertBefore(dragging, after ? row.nextSibling : row);
        });
    });

    function saveOrder() {
        const ids = Array.from(tbody.querySelectorAll('tr[data-product-id]'))
            .map(tr => tr.dataset.productId);
        const status = document.getElementById('reorder-status');

        if (status) status.textContent = 'Saving…';

        fetch('{{ route('admin.products.reorder') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ ids }),
        })
        .then(r => { if (!r.ok) throw new Error(); return r.json(); })
        .then(() => { if (status) { status.textContent = 'Order saved ✓'; setTimeout(() => status.textContent = '', 2000); } })
        .catch(() => { if (status) status.textContent = 'Failed to save — please retry.'; });
    }
});
</script>
@endpush
@endif
