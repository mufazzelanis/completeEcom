{{-- "Products in this article" + the editor's 🛍️ Product picker (one Alpine component, so both share
     one list). Attached products feed the post's end-of-article shop, the desktop sidebar and the
     sticky buy bar; "Insert" drops a product block into the text at the cursor (App\Support\BlogShop).
     $items: rows from BlogPostController::productRow() --}}
@php
    if (is_array(old('products'))) {
        // a failed save: rebuild the list from what was submitted, not what's stored
        $old = collect(old('products'))->filter(fn ($r) => !empty($r['id']));
        $found = \App\Models\Product::whereIn('id', $old->pluck('id'))->get(['id', 'name', 'slug', 'price', 'sale_price', 'image', 'stock'])->keyBy('id');
        $items = $old->map(fn ($r) => $found->has($r['id']) ? \App\Http\Controllers\Admin\BlogPostController::productRow($found[$r['id']], $r['note'] ?? null) : null)->filter()->values()->all();
    }
@endphp
<script>
window.blogProducts = function (init, searchUrl) {
    const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    const search = (q) => fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } }).then((r) => r.json());
    return {
        items: init,
        q: '', results: [], loading: false, open: false, seq: 0, timer: null,
        picker: false, target: 'content', pq: '', presults: [], ploading: false, pseq: 0, ptimer: null,
        picked: [], layout: 'spotlight', layoutTouched: false,
        layouts: [
            { k: 'spotlight', t: 'Spotlight', d: 'One big card per product, with your note' },
            { k: 'grid', t: 'Grid', d: '2–4 compact cards side by side' },
            { k: 'carousel', t: 'Swipe row', d: 'A row shoppers swipe through' },
            { k: 'list', t: 'Ranked list', d: '#1, #2, #3 — for “best of” posts' },
        ],
        money(v) { return '৳' + Math.round(v || 0).toLocaleString('en-US'); },
        has(id) { return this.items.some((i) => i.id === id); },
        find(q, done) {
            const s = ++this[done === 'p' ? 'pseq' : 'seq'];
            this[done === 'p' ? 'ploading' : 'loading'] = true;
            search(q).then((rows) => {
                if (done === 'p' && s === this.pseq) this.presults = rows;
                if (done !== 'p' && s === this.seq) { this.results = rows; this.open = true; }
            }).catch(() => {}).finally(() => {
                if (done === 'p' ? s === this.pseq : s === this.seq) this[done === 'p' ? 'ploading' : 'loading'] = false;
            });
        },
        search() { clearTimeout(this.timer); this.timer = setTimeout(() => this.find(this.q), 220); },
        psearch() { clearTimeout(this.ptimer); this.ptimer = setTimeout(() => this.find(this.pq, 'p'), 220); },
        add(p) { if (!this.has(p.id)) this.items.push(Object.assign({ note: '' }, p)); },
        remove(i) { this.items.splice(i, 1); },
        move(i, d) {
            const j = i + d;
            if (j < 0 || j >= this.items.length) return;
            const it = this.items.splice(i, 1)[0];
            this.items.splice(j, 0, it);
        },
        get stickyId() { const p = this.items.find((i) => i.stock > 0) || this.items[0]; return p ? p.id : null; },
        embedHtml(list, layout) {
            const label = (this.layouts.find((l) => l.k === layout) || {}).t || 'Grid';
            return '<div class="blog-embed blog-embed--' + layout + ' bp-ids-' + list.map((p) => p.id).join('-') + '">🛍️ '
                + esc(label) + ': ' + list.map((p) => esc(p.name)).join(' · ') + '</div><p><br></p>';
        },
        insert(list, layout) {
            if (!list.length) return;
            window.dispatchEvent(new CustomEvent('blog-embed-insert', { detail: { target: this.target, html: this.embedHtml(list, layout) } }));
            list.forEach((p) => this.add(p));
        },
        openPicker(target) {
            this.target = target || 'content';
            this.picked = []; this.layout = 'spotlight'; this.layoutTouched = false; this.pq = '';
            this.picker = true;
            this.find('', 'p');
            this.$nextTick(() => this.$refs.pq && this.$refs.pq.focus());
        },
        isPicked(id) { return this.picked.some((p) => p.id === id); },
        togglePick(p) {
            const i = this.picked.findIndex((x) => x.id === p.id);
            i >= 0 ? this.picked.splice(i, 1) : this.picked.push(p);
            if (!this.layoutTouched) this.layout = this.picked.length > 1 ? 'grid' : 'spotlight';
        },
        confirmPick() {
            // keep the notes already written for products that are on the list
            this.insert(this.picked.map((p) => this.items.find((i) => i.id === p.id) || p), this.layout);
            this.picker = false;
        },
    };
};
</script>

<div x-data="blogProducts({{ Js::from($items) }}, {{ Js::from(route('admin.blog.product-search')) }})"
     @blog-embed-open.window="openPicker($event.detail.target)"
     class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
    <input type="hidden" name="products_sync" value="1">
    <template x-for="(it, i) in items" :key="'h' + it.id">
        <span>
            <input type="hidden" :name="'products[' + i + '][id]'" :value="it.id">
            <input type="hidden" :name="'products[' + i + '][note]'" :value="it.note">
        </span>
    </template>

    <div class="flex items-start justify-between gap-3">
        <div>
            <h3 class="font-semibold text-gray-800 flex items-center gap-2">
                🛍️ Products in this article
                <span class="text-xs font-bold text-orange-700 bg-orange-50 rounded-full px-2 py-0.5" x-show="items.length" x-text="items.length"></span>
            </h3>
            <p class="text-xs text-gray-500 mt-1 max-w-lg">Shown in the shop section at the end of the post, the desktop sidebar and the sticky buy bar on phones. Use <b>Insert</b> (or the 🛍️ Product button in the editor toolbar) to place cards right inside the text.</p>
        </div>
        <button type="button" @click="openPicker('content')"
                class="flex-shrink-0 inline-flex items-center gap-1.5 text-xs font-semibold text-white bg-orange-600 hover:bg-orange-700 rounded-lg px-3 py-2 transition">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            Insert block
        </button>
    </div>

    {{-- search & add --}}
    <div class="relative" @click.outside="open = false">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" x-model="q" @input="search()" @focus="results.length ? open = true : find(q)" @keydown.enter.prevent @keydown.escape="open = false"
               placeholder="Search products by name or SKU to add…"
               class="w-full border border-gray-200 rounded-xl pl-9 pr-9 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
        <svg x-show="loading" x-cloak class="w-4 h-4 text-orange-500 animate-spin absolute right-3 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
        <div x-show="open" x-cloak class="absolute z-30 mt-1.5 w-full max-h-80 overflow-y-auto bg-white rounded-xl shadow-xl ring-1 ring-gray-100 divide-y divide-gray-50">
            <template x-for="p in results" :key="'r' + p.id">
                <button type="button" @click="add(p)" :disabled="has(p.id)" class="w-full flex items-center gap-3 px-3 py-2 text-left hover:bg-orange-50/60 disabled:opacity-60 disabled:hover:bg-transparent transition">
                    <span class="w-10 h-10 rounded-lg bg-gray-50 flex-shrink-0 overflow-hidden"><img x-show="p.image" :src="p.image" alt="" class="w-full h-full object-contain"></span>
                    <span class="flex-1 min-w-0">
                        <span class="block text-sm font-medium text-gray-800 truncate" x-text="p.name"></span>
                        <span class="block text-xs text-gray-500"><span class="font-semibold text-gray-700" x-text="money(p.price)"></span> · <span :class="p.stock > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="p.stock > 0 ? p.stock + ' in stock' : 'Out of stock'"></span></span>
                    </span>
                    <span class="text-xs font-semibold" :class="has(p.id) ? 'text-emerald-600' : 'text-orange-600'" x-text="has(p.id) ? '✓ Added' : '+ Add'"></span>
                </button>
            </template>
            <p x-show="!loading && !results.length" class="px-3 py-4 text-sm text-gray-400 text-center">No products found.</p>
        </div>
    </div>

    {{-- attached list --}}
    <div x-show="items.length" class="space-y-2">
        <template x-for="(it, i) in items" :key="'i' + it.id">
            <div class="flex gap-3 p-2.5 rounded-xl ring-1 ring-gray-100 bg-gray-50/60">
                <div class="flex flex-col justify-center gap-0.5">
                    <button type="button" @click="move(i, -1)" :disabled="i === 0" class="w-6 h-6 rounded-md text-gray-400 hover:text-gray-700 hover:bg-white disabled:opacity-30 flex items-center justify-center" title="Move up"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7"/></svg></button>
                    <button type="button" @click="move(i, 1)" :disabled="i === items.length - 1" class="w-6 h-6 rounded-md text-gray-400 hover:text-gray-700 hover:bg-white disabled:opacity-30 flex items-center justify-center" title="Move down"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg></button>
                </div>
                <span class="w-14 h-14 rounded-lg bg-white ring-1 ring-gray-100 flex-shrink-0 overflow-hidden"><img x-show="it.image" :src="it.image" alt="" class="w-full h-full object-contain"></span>
                <div class="flex-1 min-w-0 space-y-1.5">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-800 truncate" x-text="it.name"></p>
                            <p class="text-xs text-gray-500">
                                <span class="font-semibold text-gray-700" x-text="money(it.price)"></span>
                                <s x-show="it.was" class="ml-1 text-gray-400" x-text="money(it.was)"></s>
                                · <span :class="it.stock > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="it.stock > 0 ? it.stock + ' in stock' : 'Out of stock'"></span>
                                <span x-show="it.id === stickyId" class="ml-1 inline-flex items-center rounded-full bg-indigo-50 text-indigo-700 px-1.5 py-px text-[10px] font-bold">Sticky bar</span>
                            </p>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button type="button" @click="insert([it], 'spotlight')" class="text-xs font-semibold text-orange-700 hover:text-white hover:bg-orange-600 border border-orange-200 rounded-lg px-2 py-1 transition" title="Insert a spotlight card for this product at the cursor">Insert</button>
                            <a :href="it.url" target="_blank" class="w-7 h-7 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-white flex items-center justify-center" title="Open product"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>
                            <button type="button" @click="remove(i)" class="w-7 h-7 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 flex items-center justify-center" title="Remove"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                    </div>
                    <input type="text" x-model="it.note" maxlength="255" @keydown.enter.prevent
                           placeholder="Why do you recommend it? (optional — shown on the spotlight card)"
                           class="w-full border border-gray-200 rounded-lg px-2.5 py-1.5 text-xs bg-white focus:outline-none focus:ring-2 focus:ring-orange-300">
                </div>
            </div>
        </template>
        <div x-show="items.length > 1" class="flex flex-wrap items-center gap-2 pt-1 text-xs">
            <span class="text-gray-500">Insert all as:</span>
            <template x-for="l in layouts.filter(l => l.k !== 'spotlight')" :key="'a' + l.k">
                <button type="button" @click="insert(items, l.k)" class="font-semibold text-gray-700 border border-gray-200 hover:border-orange-300 hover:text-orange-700 rounded-lg px-2.5 py-1 transition" x-text="l.t"></button>
            </template>
        </div>
    </div>
    <p x-show="!items.length" class="text-xs text-gray-400 border border-dashed border-gray-200 rounded-xl px-4 py-5 text-center">No products yet — search above. Without any, the post shows products matched from its title.</p>

    {{-- picker modal (editor toolbar 🛍️ Product / "Insert block") --}}
    <div x-show="picker" x-cloak @keydown.escape.window="picker = false" class="fixed inset-0 z-[9999] bg-gray-900/40 backdrop-blur-sm">
        <div class="w-full h-full flex items-center justify-center p-4">
            <div @click.outside="picker = false" class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col overflow-hidden">
                <div class="px-5 pt-5 pb-3 border-b border-gray-100">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-gray-900">🛍️ Insert products into the article</h3>
                        <button type="button" @click="picker = false" class="w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 flex items-center justify-center"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">Pick one or more — they're placed where your cursor was, in the order you pick them.</p>
                    <div class="relative mt-3">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <input type="text" x-ref="pq" x-model="pq" @input="psearch()" @keydown.enter.prevent placeholder="Search products…"
                               class="w-full border border-gray-200 rounded-xl pl-9 pr-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
                    </div>
                    <div x-show="items.length && !pq" class="mt-2 flex flex-wrap gap-1.5">
                        <span class="text-[11px] text-gray-400 self-center">This post:</span>
                        <template x-for="it in items" :key="'q' + it.id">
                            <button type="button" @click="togglePick(it)" class="text-[11px] font-medium rounded-full px-2 py-0.5 border transition max-w-[12rem] truncate"
                                    :class="isPicked(it.id) ? 'bg-orange-600 border-orange-600 text-white' : 'border-gray-200 text-gray-600 hover:border-orange-300'" x-text="it.name"></button>
                        </template>
                    </div>
                </div>

                <div class="flex-1 overflow-y-auto divide-y divide-gray-50 min-h-[10rem]">
                    <template x-for="p in presults" :key="'p' + p.id">
                        <button type="button" @click="togglePick(p)" class="w-full flex items-center gap-3 px-5 py-2.5 text-left transition" :class="isPicked(p.id) ? 'bg-orange-50' : 'hover:bg-gray-50'">
                            <span class="w-5 h-5 rounded-md border-2 flex items-center justify-center flex-shrink-0 transition" :class="isPicked(p.id) ? 'bg-orange-600 border-orange-600 text-white' : 'border-gray-300'">
                                <span x-show="isPicked(p.id)" class="text-[10px] font-bold" x-text="picked.findIndex(x => x.id === p.id) + 1"></span>
                            </span>
                            <span class="w-11 h-11 rounded-lg bg-gray-50 flex-shrink-0 overflow-hidden"><img x-show="p.image" :src="p.image" alt="" class="w-full h-full object-contain"></span>
                            <span class="flex-1 min-w-0">
                                <span class="block text-sm font-medium text-gray-800 truncate" x-text="p.name"></span>
                                <span class="block text-xs text-gray-500"><span class="font-semibold text-gray-700" x-text="money(p.price)"></span> · <span :class="p.stock > 0 ? 'text-emerald-600' : 'text-red-500'" x-text="p.stock > 0 ? p.stock + ' in stock' : 'Out of stock'"></span></span>
                            </span>
                        </button>
                    </template>
                    <p x-show="ploading && !presults.length" class="px-5 py-8 text-sm text-gray-400 text-center">Searching…</p>
                    <p x-show="!ploading && !presults.length" class="px-5 py-8 text-sm text-gray-400 text-center">No products found.</p>
                </div>

                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/70 space-y-3">
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <template x-for="l in layouts" :key="'l' + l.k">
                            <button type="button" @click="layout = l.k; layoutTouched = true"
                                    class="text-left rounded-xl border-2 px-3 py-2 transition" :class="layout === l.k ? 'border-orange-500 bg-white' : 'border-transparent bg-white/60 hover:border-gray-200'">
                                <span class="block text-xs font-bold text-gray-800" x-text="l.t"></span>
                                <span class="block text-[10px] text-gray-500 leading-snug mt-0.5" x-text="l.d"></span>
                            </button>
                        </template>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <p class="text-xs text-gray-500"><span class="font-semibold text-gray-800" x-text="picked.length"></span> selected</p>
                        <div class="flex gap-2">
                            <button type="button" @click="picker = false" class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-white transition">Cancel</button>
                            <button type="button" @click="confirmPick()" :disabled="!picked.length" class="px-4 py-2 text-sm rounded-lg bg-orange-600 text-white font-semibold hover:bg-orange-700 transition disabled:opacity-40">Insert into article</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
