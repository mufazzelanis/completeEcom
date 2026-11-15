{{--
    Smart search box — logic in resources/js/smart-search.js (Alpine.data('smartSearch')).
    $variant: 'header' (desktop bar, "/" focuses it), 'drawer' (mobile menu), 'bar' (mobile sticky bar).
    Placeholder types out top searches & most-viewed products; focus shows recent / trending /
    popular; typing shows highlighted suggestions; ↑ ↓ Enter Esc all work.
--}}
@php
    $variant ??= 'header';
    $isHeader = $variant === 'header';
    $placeholder = t('header.search_placeholder', 'Search in :site', ['site' => $siteName ?? setting('site_name', '')], 'header');
    $inDrawer = $variant === 'drawer';
@endphp
@once
<style>
    .ss-hl b { font-weight: 800; color: rgb(var(--brand-600)); }
    .dark .ss-hl b { color: rgb(var(--brand-400)); }
    .ss-ring { background: linear-gradient(110deg, rgb(var(--brand-500)), rgb(var(--secondary-500)), rgb(var(--brand-500))); background-size: 200% 100%; }
    .ss-ring.is-on { animation: ss-flow 3s linear infinite; }
    @keyframes ss-flow { to { background-position: 200% 0; } }
    .ss-panel { animation: ss-in .18s cubic-bezier(.2, .9, .3, 1); }
    @keyframes ss-in { from { opacity: 0; transform: translateY(-6px) scale(.985); } }
    @media (prefers-reduced-motion: reduce) { .ss-ring.is-on, .ss-panel { animation: none; } }
</style>
@endonce
<div class="{{ $isHeader ? 'hidden md:flex flex-1 max-w-2xl' : 'w-full' }} relative"
     x-data="smartSearch({
        query: @js(request('search', '')),
        placeholder: @js($placeholder),
        suggestUrl: @js(route('search.suggest')),
        trendingUrl: @js(route('search.trending')),
        shopUrl: @js(route('shop.index')),
        hotkey: @js($isHeader),
        animate: @js($variant !== 'drawer'),
     })"
     @click.outside="close()" @keydown.escape.window="open && close()">
    <div class="w-full relative">
        {{-- gradient border that flows while the box is focused --}}
        <div class="ss-ring rounded-full p-[2px] transition-shadow" :class="focused ? 'is-on shadow-[0_10px_30px_-12px_rgb(var(--brand-500)/0.6)]' : ''">
            <form action="{{ route('shop.index') }}" method="GET" role="search" @submit="submit($event)"
                  class="flex items-center gap-1 rounded-full bg-white dark:bg-gray-900 {{ $isHeader ? 'pl-4 pr-1 py-1' : 'pl-3.5 pr-1 py-1' }}">
                <svg class="w-[18px] h-[18px] flex-shrink-0 transition-colors" :class="focused ? 'text-orange-500' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke-width="2.4"/><path stroke-linecap="round" stroke-width="2.4" d="M20 20l-3.5-3.5"/></svg>
                <input type="search" name="search" x-ref="input" x-model="query"
                       @input="onInput()" @focus="onFocus()" @blur="onBlur()" @keydown="onKey($event)"
                       :placeholder="placeholder" aria-label="{{ $placeholder }}" autocomplete="off" enterkeyhint="search"
                       role="combobox" :aria-expanded="open" aria-autocomplete="list"
                       class="flex-1 min-w-0 bg-transparent border-0 focus:ring-0 focus:outline-none text-sm text-gray-900 dark:text-gray-100 placeholder:text-gray-400 px-2 {{ $isHeader ? 'py-2' : 'py-2' }} [&::-webkit-search-cancel-button]:hidden">
                {{-- spinner while fetching / clear button once there's text --}}
                <span x-show="loading" x-cloak class="w-4 h-4 mr-1 rounded-full border-2 border-orange-200 border-t-orange-500 animate-spin flex-shrink-0" aria-hidden="true"></span>
                <button type="button" x-show="query && !loading" x-cloak @click="clear()" aria-label="Clear search"
                        class="w-7 h-7 flex items-center justify-center rounded-full text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-800 flex-shrink-0">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
                @if($isHeader)
                    <kbd x-show="!focused && !query" class="hidden lg:inline-flex items-center justify-center mr-1 w-6 h-6 rounded-md border border-gray-200 dark:border-gray-700 text-[11px] font-semibold text-gray-400" title="Press / to search">/</kbd>
                @endif
                <button type="submit" aria-label="Search"
                        class="flex-shrink-0 rounded-full bg-gradient-to-r from-orange-500 to-pink-500 text-white font-semibold text-sm {{ $isHeader ? 'px-5 py-2' : 'w-9 h-9 flex items-center justify-center' }} hover:brightness-110 active:scale-95 transition">
                    @if($isHeader)
                        Search
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7" stroke-width="2.6"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.6" d="M20 20l-3.5-3.5"/></svg>
                    @endif
                </button>
            </form>
        </div>

        {{-- Dropdown --}}
        <div x-show="open && (typing || recent.length || trending.searches.length || trending.products.length)" x-cloak
             class="ss-panel absolute left-0 right-0 top-full mt-2 z-[200] rounded-2xl bg-white dark:bg-gray-900 shadow-2xl ring-1 ring-gray-100 dark:ring-gray-800 overflow-hidden {{ $inDrawer ? 'max-h-[55vh]' : 'max-h-[70vh]' }} overflow-y-auto overscroll-contain"
             role="listbox">

            {{-- ── Nothing typed: recent, trending, popular ── --}}
            <template x-if="!typing">
                <div class="py-2">
                    <template x-if="recent.length">
                        <div class="px-4 pt-2 pb-1">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400">Recent searches</p>
                                <button type="button" @click="clearRecent()" class="text-[11px] font-semibold text-gray-400 hover:text-orange-600">Clear</button>
                            </div>
                            <template x-for="(r, i) in recent" :key="'r' + r">
                                <div class="group flex items-center gap-1 -mx-2 rounded-lg" :class="active === idx('recent', i) ? 'bg-orange-50 dark:bg-gray-800' : 'hover:bg-gray-50 dark:hover:bg-gray-800'" :data-ss-active="active === idx('recent', i) ? 1 : 0">
                                    <a :href="searchUrl(r)" @click.prevent="pick(r)" class="flex-1 flex items-center gap-2.5 px-2 py-2 text-sm text-gray-700 dark:text-gray-200 min-w-0">
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        <span class="truncate" x-text="r"></span>
                                    </a>
                                    <button type="button" @click="forget(r)" class="w-7 h-7 mr-1 flex items-center justify-center rounded-full text-gray-300 hover:text-gray-600 opacity-100 md:opacity-0 group-hover:opacity-100" aria-label="Remove">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>

                    <template x-if="trending.searches.length">
                        <div class="px-4 pt-3 pb-1">
                            <p class="text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-2">🔥 Trending searches</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="(s, i) in trending.searches" :key="'t' + s">
                                    <a :href="searchUrl(s)" @click.prevent="pick(s)" :data-ss-active="active === idx('trend', i) ? 1 : 0"
                                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[13px] font-medium border transition"
                                       :class="active === idx('trend', i) ? 'border-orange-400 bg-orange-50 text-orange-700 dark:bg-gray-800' : 'border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-200 hover:border-orange-300 hover:text-orange-600'">
                                        <svg class="w-3 h-3 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                                        <span x-text="s"></span>
                                    </a>
                                </template>
                            </div>
                        </div>
                    </template>

                    <template x-if="trending.products.length">
                        <div class="pt-3">
                            <p class="px-4 text-[11px] font-bold uppercase tracking-wider text-gray-400 mb-1">Popular right now</p>
                            <template x-for="(p, i) in trending.products" :key="'p' + p.url">
                                <a :href="p.url" @click="close()" :data-ss-active="active === idx('pop', i) ? 1 : 0"
                                   class="flex items-center gap-3 px-4 py-2 transition" :class="active === idx('pop', i) ? 'bg-orange-50 dark:bg-gray-800' : 'hover:bg-gray-50 dark:hover:bg-gray-800'">
                                    <span class="w-11 h-11 rounded-xl bg-white ring-1 ring-gray-100 dark:ring-gray-700 flex items-center justify-center p-1 flex-shrink-0 overflow-hidden">
                                        <img :src="p.image" alt="" class="w-full h-full object-contain" loading="lazy">
                                    </span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-sm font-medium text-gray-900 dark:text-gray-100 truncate" x-text="p.name"></span>
                                        <span class="flex items-center gap-1.5 text-xs">
                                            <b class="text-orange-600" x-text="p.price"></b>
                                            <s class="text-gray-400" x-show="p.was" x-text="p.was"></s>
                                        </span>
                                    </span>
                                    <span x-show="p.off" class="px-1.5 py-0.5 rounded-md text-[10px] font-bold text-white bg-gradient-to-r from-orange-500 to-pink-500" x-text="'-' + p.off + '%'"></span>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- ── Typing: suggestions ── --}}
            <template x-if="typing">
                <div class="ss-hl py-2">
                    <template x-if="results.categories.length">
                        <div class="px-2 pb-1">
                            <p class="px-2 pt-1 pb-1 text-[11px] font-bold uppercase tracking-wider text-gray-400">Categories</p>
                            <template x-for="(c, i) in results.categories" :key="'c' + c.url">
                                <a :href="c.url" @click="remember(query); close()" :data-ss-active="active === idx('cat', i) ? 1 : 0"
                                   class="flex items-center gap-2.5 px-2 py-2 rounded-lg text-sm text-gray-700 dark:text-gray-200 transition" :class="active === idx('cat', i) ? 'bg-orange-50 dark:bg-gray-800' : 'hover:bg-gray-50 dark:hover:bg-gray-800'">
                                    <span class="w-7 h-7 rounded-lg bg-orange-50 dark:bg-gray-800 text-orange-500 flex items-center justify-center flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M4 6h16M4 12h16M4 18h10"/></svg>
                                    </span>
                                    <span class="truncate" x-html="hl(c.name)"></span>
                                    <span class="ml-auto text-xs text-gray-400">Category</span>
                                </a>
                            </template>
                        </div>
                    </template>

                    <template x-if="results.products.length">
                        <div>
                            <p class="px-4 pt-1 pb-1 text-[11px] font-bold uppercase tracking-wider text-gray-400">Products</p>
                            <template x-for="(p, i) in results.products" :key="'s' + p.url">
                                <a :href="p.url" @click="remember(query); close()" :data-ss-active="active === idx('prod', i) ? 1 : 0"
                                   class="flex items-center gap-3 px-4 py-2 transition" :class="active === idx('prod', i) ? 'bg-orange-50 dark:bg-gray-800' : 'hover:bg-gray-50 dark:hover:bg-gray-800'">
                                    <span class="w-12 h-12 rounded-xl bg-white ring-1 ring-gray-100 dark:ring-gray-700 flex items-center justify-center p-1 flex-shrink-0 overflow-hidden">
                                        <img x-show="p.image" :src="p.image" alt="" class="w-full h-full object-contain" loading="lazy">
                                    </span>
                                    <span class="flex-1 min-w-0">
                                        <span class="block text-sm text-gray-800 dark:text-gray-100 line-clamp-2 leading-snug" x-html="hl(p.name)"></span>
                                        <span class="flex items-center gap-1.5 text-xs mt-0.5">
                                            <b class="text-orange-600 !font-bold" x-text="p.price"></b>
                                            <s class="text-gray-400" x-show="p.was" x-text="p.was"></s>
                                        </span>
                                    </span>
                                    <span x-show="p.off" class="px-1.5 py-0.5 rounded-md text-[10px] font-bold text-white bg-gradient-to-r from-orange-500 to-pink-500" x-text="'-' + p.off + '%'"></span>
                                </a>
                            </template>
                        </div>
                    </template>

                    {{-- nothing found --}}
                    <div x-show="empty" class="px-5 py-6 text-center">
                        <p class="text-3xl mb-1">🔍</p>
                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">No exact match for “<span x-text="query.trim()"></span>”</p>
                        <p class="text-xs text-gray-500 mt-0.5">Check the spelling, or try a brand or product type.</p>
                        <template x-if="trending.searches.length">
                            <div class="mt-3 flex flex-wrap justify-center gap-1.5">
                                <template x-for="s in trending.searches.slice(0, 5)" :key="'e' + s">
                                    <a :href="searchUrl(s)" @click.prevent="pick(s)" class="px-2.5 py-1 rounded-full text-xs border border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-300 hover:border-orange-300 hover:text-orange-600" x-text="s"></a>
                                </template>
                            </div>
                        </template>
                    </div>

                    <a :href="searchUrl(query.trim())" @click="remember(query); close()" :data-ss-active="active === idx('all', 0) ? 1 : 0"
                       class="mt-1 mx-2 mb-1 flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl text-sm font-semibold transition"
                       :class="active === idx('all', 0) ? 'bg-orange-500 text-white' : 'bg-gray-50 dark:bg-gray-800 text-orange-700 dark:text-orange-400 hover:bg-orange-50'">
                        <span class="truncate">See all results for “<span x-text="query.trim()"></span>”</span>
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                    </a>
                </div>
            </template>

            @if($isHeader)
                <div class="hidden lg:flex items-center gap-3 px-4 py-2 border-t border-gray-100 dark:border-gray-800 text-[11px] text-gray-400">
                    <span><kbd class="px-1 rounded border border-gray-200 dark:border-gray-700">↑</kbd> <kbd class="px-1 rounded border border-gray-200 dark:border-gray-700">↓</kbd> to move</span>
                    <span><kbd class="px-1 rounded border border-gray-200 dark:border-gray-700">Enter</kbd> to open</span>
                    <span><kbd class="px-1 rounded border border-gray-200 dark:border-gray-700">Esc</kbd> to close</span>
                </div>
            @endif
        </div>
    </div>
</div>
