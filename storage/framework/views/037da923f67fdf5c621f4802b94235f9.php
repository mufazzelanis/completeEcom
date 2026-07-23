
<div class="hidden md:flex flex-1 max-w-2xl">
    <div class="w-full relative" x-data="{
        query: '<?php echo e(addslashes(request('search', ''))); ?>',
        results: { products: [], categories: [] },
        open: false,
        async fetchSuggestions() {
            if (this.query.length < 2) { this.open = false; return; }
            try {
                const res = await fetch('/search/suggest?q=' + encodeURIComponent(this.query));
                this.results = await res.json();
                this.open = this.results.products.length > 0 || this.results.categories.length > 0;
            } catch(e) {}
        }
    }" @click.outside="open = false">
        <form action="<?php echo e(route('shop.index')); ?>" method="GET" class="w-full flex" @submit="open = false">
            <input type="text" name="search"
                x-model="query"
                @input.debounce.300ms="fetchSuggestions()"
                @focus="query.length > 1 && fetchSuggestions()"
                @keydown.escape="open = false"
                placeholder="<?php echo e(t('header.search_placeholder', 'Search in :site', ['site' => $siteName], 'header')); ?>"
                class="w-full border-2 border-orange-400 rounded-l-md px-4 py-2 focus:outline-none focus:border-orange-500 text-sm bg-orange-50/50"
                autocomplete="off">
            <button type="submit" class="bg-orange-500 text-white px-6 py-2 rounded-r-md hover:bg-orange-600 flex-shrink-0 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
        
        <div x-show="open" x-cloak x-transition
             class="absolute top-full left-0 right-0 bg-white dark:bg-gray-800 rounded-b-lg shadow-2xl border border-gray-100 dark:border-gray-700 z-[200] overflow-hidden fade-in">
            <template x-if="results.categories && results.categories.length > 0">
                <div class="border-b border-gray-100 dark:border-gray-700">
                    <p class="px-4 pt-3 pb-1 text-[10px] font-bold text-orange-400 uppercase tracking-wider"><?php echo e(t('header.categories', 'Categories', [], 'header')); ?></p>
                    <template x-for="cat in results.categories" :key="cat.url">
                        <a :href="cat.url" @click="open = false"
                           class="flex items-center px-4 py-2 hover:bg-orange-50 dark:hover:bg-gray-700 gap-2 text-sm text-gray-700 dark:text-gray-200 hover:text-orange-600 transition">
                            <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                            <span x-text="cat.name"></span>
                        </a>
                    </template>
                </div>
            </template>
            <template x-if="results.products && results.products.length > 0">
                <div>
                    <p class="px-4 pt-3 pb-1 text-[10px] font-bold text-orange-400 uppercase tracking-wider"><?php echo e(t('header.products', 'Products', [], 'header')); ?></p>
                    <template x-for="product in results.products" :key="product.url">
                        <a :href="product.url" @click="open = false"
                           class="flex items-center px-4 py-2.5 hover:bg-orange-50 dark:hover:bg-gray-700 gap-3 transition">
                            <div class="w-10 h-10 bg-gray-100 rounded overflow-hidden flex-shrink-0 flex items-center justify-center">
                                <img x-show="product.image" :src="product.image" :alt="product.name" class="w-full h-full object-cover">
                                <svg x-show="!product.image" class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate" x-text="product.name"></p>
                                <p class="text-xs font-bold text-orange-500" x-text="product.price"></p>
                            </div>
                        </a>
                    </template>
                    <div class="px-4 py-2.5 border-t border-gray-100 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <a :href="'<?php echo e(route('shop.index')); ?>?search=' + encodeURIComponent(query)"
                           class="text-xs text-orange-500 hover:text-orange-700 font-semibold">
                            <?php echo e(t('header.see_all_results', 'See all results for', [], 'header')); ?> "<span x-text="query"></span>" &rarr;
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
<?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/partials/storefront/header-search.blade.php ENDPATH**/ ?>