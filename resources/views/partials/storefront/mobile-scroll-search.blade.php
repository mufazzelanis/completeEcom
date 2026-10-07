{{-- Mobile Dynamic Search Bar — hidden until the customer scrolls past the first screen,
     then slides down and pins itself right under the header. Desktop already has its own
     always-visible search bar (header-search.blade.php); this exists purely so a mobile
     customer further down the page doesn't have to scroll back to the top or open the full
     menu drawer just to search. Visibility is driven by the global Alpine.store
     ('mobileSearchBar') (resources/js/app.js) — one window-level scroll listener shared
     across the whole Turbo session, not a fresh one per page (see that store's own comment
     for why a page-scoped listener would leak under Turbo).

     `top` is conditional, not a flat 0 — when the header above is itself sticky
     ($stickyHeader), this bar needs to pin BELOW it (var(--header-height), kept in sync by
     a ResizeObserver in app.js) rather than at the same top:0 offset: two sibling sticky
     elements both at top:0 don't auto-stack, they occupy the exact same spot, and <header>'s
     higher z-index (z-50 vs this bar's z-30) was winning there, hiding this bar completely
     regardless of its own visible/hidden state. When the header ISN'T sticky it scrolls
     away entirely, so this bar correctly becomes the topmost sticky element at top:0 once
     reached — no offset needed in that case. --}}
<div class="md:hidden sticky z-30" style="top: {{ $stickyHeader ? 'var(--header-height, 56px)' : '0px' }};" x-data="{
        query: '',
        results: { products: [], categories: [] },
        open: false,
        async fetchSuggestions() {
            if (this.query.length < 2) { this.open = false; return; }
            try {
                const res = await fetch('/search/suggest?q=' + encodeURIComponent(this.query));
                this.results = await res.json();
                this.open = this.results.products.length > 0 || this.results.categories.length > 0;
            } catch (e) {}
        },
    }" @click.outside="open = false">
    <div x-show="$store.mobileSearchBar.visible" x-cloak
         x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-3" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-3"
         class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-md shadow-md border-b border-gray-100 dark:border-gray-800 px-3 py-2.5">
        <form action="{{ route('shop.index') }}" method="GET" class="relative" @submit="open = false">
            <input type="text" name="search" x-model="query"
                @input.debounce.300ms="fetchSuggestions()"
                @keydown.escape="open = false"
                placeholder="{{ t('header.search_placeholder', 'Search in :site', ['site' => $siteName], 'header') }}"
                aria-label="{{ t('header.search_placeholder', 'Search in :site', ['site' => $siteName], 'header') }}"
                autocomplete="off"
                class="w-full bg-gray-100 dark:bg-gray-800 rounded-full pl-10 pr-4 py-2.5 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-orange-500 transition">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </form>

        {{-- Auto-suggest dropdown — same /search/suggest endpoint and result shape as the
             desktop bar (partials.storefront.header-search). --}}
        <div x-show="open" x-cloak x-transition
             class="absolute left-3 right-3 mt-1.5 bg-white dark:bg-gray-800 rounded-xl shadow-2xl border border-gray-100 dark:border-gray-700 z-40 overflow-hidden max-h-[60vh] overflow-y-auto">
            <template x-if="results.categories && results.categories.length > 0">
                <div class="border-b border-gray-100 dark:border-gray-700">
                    <p class="px-4 pt-3 pb-1 text-[10px] font-bold text-orange-400 uppercase tracking-wider">{{ t('header.categories', 'Categories', [], 'header') }}</p>
                    <template x-for="cat in results.categories" :key="cat.url">
                        <a :href="cat.url" @click="open = false"
                           class="flex items-center px-4 py-2 hover:bg-gray-50 dark:hover:bg-gray-700 gap-2 text-sm text-gray-700 dark:text-gray-200 hover:text-orange-600 transition">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span x-text="cat.name"></span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="results.products && results.products.length > 0">
                <div>
                    <p class="px-4 pt-3 pb-1 text-[10px] font-bold text-orange-400 uppercase tracking-wider">{{ t('header.products', 'Products', [], 'header') }}</p>
                    <template x-for="product in results.products" :key="product.url">
                        <a :href="product.url" @click="open = false"
                           class="flex items-center px-4 py-2.5 hover:bg-gray-50 dark:hover:bg-gray-700 gap-3 transition">
                            <div class="w-10 h-10 bg-gray-100 rounded overflow-hidden flex-shrink-0 flex items-center justify-center p-0.5">
                                <img x-show="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-contain">
                                <svg x-show="!product.image" class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate" x-text="product.name"></p>
                                <p class="text-xs font-bold text-orange-700" x-text="product.price"></p>
                            </div>
                        </a>
                    </template>
                    <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <a :href="'{{ route('shop.index') }}?search=' + encodeURIComponent(query)" @click="open = false"
                           class="text-xs text-orange-700 hover:text-orange-800 font-semibold">
                            {{ t('header.see_all_results', 'See all results for', [], 'header') }} "<span x-text="query"></span>" &rarr;
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
