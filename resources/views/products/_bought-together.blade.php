{{-- "Frequently bought together" bundle box (Personalization::frequentlyBoughtTogether):
     this product + up to two companions, each with a tick box, a live total, and one button
     that puts all ticked ones in the cart without leaving the page (CartController::addMany).
     Page-local fbt-* CSS, light + dark. --}}
@php
    $fbtAll = collect([$product])->merge($fbt);
    $fbtItems = $fbtAll->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->final_price, 'on' => true, 'main' => $p->id === $product->id])->values();
@endphp
<style>
    .fbt { border: 1px solid #eef0f4; border-radius: 1.25rem; padding: 1.25rem; background: linear-gradient(180deg, rgba(99,102,241,.05), transparent 60%); }
    .dark .fbt { border-color: #263044; background: linear-gradient(180deg, rgba(99,102,241,.10), transparent 60%); }
    .fbt-row { display: flex; align-items: center; gap: .5rem; flex-wrap: wrap; }
    .fbt-pic { position: relative; overflow: hidden; width: 5.25rem; height: 5.25rem; border-radius: 1rem; background: #fff; border: 2px solid #e5e7eb; padding: .3rem; transition: opacity .2s, border-color .2s; flex-shrink: 0; }
    .dark .fbt-pic { background: #1f2937; border-color: #374151; }
    .fbt-pic.is-on { border-color: #6366f1; }
    .fbt-pic.is-off { opacity: .35; }
    .fbt-pic img { width: 100%; height: 100%; object-fit: contain; }
    .fbt-plus { font-size: 1.4rem; font-weight: 300; color: #9ca3af; }
    .fbt-line { display: flex; align-items: center; gap: .6rem; padding: .45rem 0; font-size: .875rem; cursor: pointer; }
    .fbt-line input { width: 1.1rem; height: 1.1rem; accent-color: #6366f1; flex-shrink: 0; }
    .fbt-line .n { flex: 1; min-width: 0; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .dark .fbt-line .n { color: #e5e7eb; }
    .fbt-line .p { font-weight: 700; color: #111827; white-space: nowrap; }
    .dark .fbt-line .p { color: #f9fafb; }
    .fbt-btn { display: inline-flex; align-items: center; justify-content: center; gap: .5rem; padding: .8rem 1.25rem; border-radius: .9rem; font-weight: 800; font-size: .92rem; color: #fff; background: linear-gradient(135deg, #4f46e5, #7c3aed); box-shadow: 0 10px 22px -12px #4f46e5; transition: transform .15s, opacity .15s; }
    .fbt-btn:hover { transform: translateY(-1px); }
    .fbt-btn:disabled { opacity: .6; cursor: wait; transform: none; }
    .fbt-btn.is-done { background: linear-gradient(135deg, #059669, #10b981); }
</style>
<section class="fbt mb-8" aria-labelledby="fbt-title"
         x-data="{
            items: @js($fbtItems),
            busy: false, done: false,
            get picked() { return this.items.filter(i => i.on) },
            get total() { return this.picked.reduce((s, i) => s + i.price, 0) },
            money(v) { return '৳' + Math.round(v).toLocaleString('en-IN') },
            async addAll() {
                if (this.done) { window.location = @js(route('cart.index')); return; }
                if (!this.picked.length) return;
                this.busy = true;
                try {
                    const r = await fetch(@js(route('cart.add-many')), {
                        method: 'POST', credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                        body: JSON.stringify({ product_ids: this.picked.map(i => i.id) }),
                    });
                    const d = await r.json();
                    if (!r.ok) { window.showToast ? showToast(d.message || 'Could not add to cart', 'error') : alert(d.message); return; }
                    this.done = true;
                    window.showToast && showToast(d.message);
                    ['header-cart-count', 'bottom-nav-cart-count'].forEach(id => {
                        const b = document.getElementById(id); if (!b) return;
                        b.textContent = d.cart_count > 99 ? '99+' : d.cart_count; b.classList.toggle('hidden', d.cart_count <= 0);
                        b.classList.remove('pulse-badge'); void b.offsetWidth; b.classList.add('pulse-badge');
                    });
                    const value = d.items.reduce((s, i) => s + i.price, 0);
                    window.mvTrack && window.mvTrack('add_to_cart', { value: value, items: d.items });
                    if (typeof fbq === 'function') fbq('track', 'AddToCart', { content_ids: d.items.map(i => i.item_id), content_type: 'product', value: value, currency: @js(setting('currency_code', 'BDT')) });
                } catch (e) { window.showToast ? showToast('Something went wrong. Please try again.', 'error') : alert('Something went wrong.'); }
                finally { this.busy = false; }
            },
         }">
    <h2 id="fbt-title" class="text-lg font-bold text-gray-900 dark:text-gray-100">Frequently bought together</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Customers who bought this also picked these</p>

    <div class="flex flex-col lg:flex-row lg:items-center gap-5">
        <div class="fbt-row">
            @foreach($fbtAll as $i => $p)
                @if($i > 0)<span class="fbt-plus" aria-hidden="true">+</span>@endif
                @if($p->id === $product->id)
                    {{-- this product itself — not a link to the page you're already on --}}
                    <span class="fbt-pic" :class="items[{{ $i }}].on ? 'is-on' : 'is-off'">
                        @if($p->image)<img src="{{ image_thumb($p->image, 200) }}" alt="" loading="lazy" width="84" height="84">@endif
                    </span>
                @else
                    <a href="{{ route('products.show', $p) }}" class="fbt-pic" :class="items[{{ $i }}].on ? 'is-on' : 'is-off'" aria-label="{{ $p->name }}">
                        @if($p->image)<img src="{{ image_thumb($p->image, 200) }}" alt="" loading="lazy" width="84" height="84">@endif
                    </a>
                @endif
            @endforeach
        </div>

        <div class="flex-1 min-w-0">
            <template x-for="(item, i) in items" :key="item.id">
                <label class="fbt-line">
                    <input type="checkbox" x-model="item.on" :disabled="item.main || done">
                    <span class="n"><span x-show="item.main" class="font-semibold">This item: </span><span x-text="item.name"></span></span>
                    <span class="p" x-text="money(item.price)"></span>
                </label>
            </template>
        </div>

        <div class="lg:text-right flex-shrink-0">
            <p class="text-sm text-gray-500 dark:text-gray-400">Total for <span x-text="picked.length"></span> items</p>
            <p class="text-2xl font-extrabold text-gray-900 dark:text-white mb-2" x-text="money(total)">৳{{ number_format($fbtAll->sum('final_price')) }}</p>
            <button type="button" class="fbt-btn" :class="done && 'is-done'" @click="addAll()" :disabled="busy || !picked.length">
                <span x-show="!busy && !done">🛒 Add <span x-text="picked.length"></span> to cart</span>
                <span x-show="busy" x-cloak>Adding…</span>
                <span x-show="done" x-cloak>✓ Added — view cart</span>
            </button>
        </div>
    </div>
</section>
