{{-- Out of stock → "Pre-order": book it now by paying only the delivery charge by bKash /
     Nagad; the rest is paid on delivery. Delivery charges come live from Settings → Shipping
     (Inside / Outside Dhaka, or the flat rate).
     "Pre-order now" opens a two-step sheet right here (details → pay the advance), placed via
     CheckoutController::placePreorder → store(), so it is a real order with every checkout rule.
     A problem brings the customer back with the sheet reopened on what to fix. When checkout
     needs an SMS code (Settings → Orders → Phone check) the button goes to checkout instead.
     "Ask on WhatsApp" is there for anyone who'd rather talk first. --}}
@php
    $poMethodModels = \App\Models\Product::preorderPaymentMethods();
    $poMethods = $poMethodModels->pluck('name')->implode(' / ');
    $poZones = \App\Services\ShippingCalculator::usesZones();
    $poRates = [
        'dhaka' => \App\Http\Controllers\CheckoutController::preorderAdvanceBase('dhaka'),
        'outside_dhaka' => \App\Http\Controllers\CheckoutController::preorderAdvanceBase('outside_dhaka'),
        'flat' => \App\Http\Controllers\CheckoutController::preorderAdvanceBase(null),
    ];
    $poFree = setting('free_shipping_enabled', '0') === '1' ? (float) setting('free_shipping_min', 999) : null;
    $poPrice = (float) $product->final_price;
    $poBrand = fn ($slug) => match (true) {
        str_contains($slug, 'bkash') => ['#e2136e', '#fdf2f8'],
        str_contains($slug, 'nagad') => ['#ec5a24', '#fff7ed'],
        str_contains($slug, 'rocket') => ['#8b2b8f', '#faf5ff'],
        str_contains($slug, 'upay') => ['#0b5ea8', '#eff6ff'],
        default => ['#7c3aed', '#f5f3ff'],
    };
    // advance per method per area: the area's delivery charge + that method's own charge
    $poPay = $poMethodModels->map(fn ($m) => [
        'slug' => $m->slug,
        'name' => $m->name,
        'number' => (string) $m->account_number,
        'holder' => (string) $m->account_name,
        'logo' => $m->logo ? image_thumb($m->logo, 96) : null,
        'note' => (string) $m->instructions,
        'color' => $poBrand($m->slug)[0],
        'tint' => $poBrand($m->slug)[1],
        'amount' => collect($poRates)->map(fn ($base) => round($base + $m->calculateCharge($base), 2))->all(),
    ])->filter(fn ($m) => trim($m['number']) !== '')->values();   // a method without its number set can't be paid in the sheet
    $poSheet = $poPay->isNotEmpty() && ! \App\Http\Controllers\CheckoutPhoneController::required();
    $poField = fn ($k, $d = 'required') => setting("checkout_field_{$k}", $d);
    $poReopen = (bool) old('_po_sheet');
    $poPayErrors = $poReopen && $errors->hasAny(['payment_method', 'transaction_id', 'sender_number']);
    $poWa = whatsapp_chat_url();
@endphp
<style>
    .po-zone { flex: 1; text-align: left; border-radius: .85rem; padding: .55rem .75rem; border: 2px solid #e5e7eb; background: #fff; transition: border-color .15s, background-color .15s; }
    .dark .po-zone { background: #111827; border-color: #374151; }
    .po-zone.is-on { border-color: #7c3aed; background: rgba(124,58,237,.07); }
    .po-qty { display: inline-flex; align-items: center; border-radius: .85rem; border: 1px solid #e5e7eb; background: #f9fafb; overflow: hidden; flex-shrink: 0; }
    .dark .po-qty { background: #1f2937; border-color: #374151; }
    .po-qty button { width: 2.6rem; height: 3rem; font-size: 1.25rem; font-weight: 700; color: #4b5563; }
    .dark .po-qty button { color: #d1d5db; }
    .po-qty button:disabled { opacity: .35; }
    .po-qty input { width: 2.75rem; height: 3rem; padding: 0; border: 0; background: transparent; text-align: center; font-size: 1.05rem; font-weight: 800; color: #111827; -moz-appearance: textfield; appearance: textfield; }
    .dark .po-qty input { color: #f9fafb; }
    .po-qty input::-webkit-outer-spin-button, .po-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .po-qty input:focus { outline: none; box-shadow: none; }
    .po-cta { background: linear-gradient(135deg, #7c3aed, #db2777); color: #fff; font-weight: 800; border-radius: .9rem; transition: filter .15s, transform .15s; }
    .po-cta:hover { filter: brightness(1.08); }
    .po-cta:active { transform: scale(.98); }
    .po-cta:disabled { opacity: .6; }
    .po-wa { display: flex; align-items: center; justify-content: center; gap: .45rem; width: 100%; padding: .65rem; border-radius: .9rem; font-size: .85rem; font-weight: 700; color: #15803d; background: #f0fdf4; border: 1px solid #bbf7d0; }
    .po-wa:hover { background: #dcfce7; }
    .dark .po-wa { background: rgba(22,163,74,.12); border-color: rgba(22,163,74,.35); color: #4ade80; }

    /* the sheet */
    .pos-ov { position: fixed; inset: 0; z-index: 120; background: rgba(15,23,42,.55); display: flex; align-items: center; justify-content: center; padding: 1rem; backdrop-filter: blur(2px); }
    .pos { width: 100%; max-width: 28rem; max-height: 92vh; display: flex; flex-direction: column; background: #fff; border-radius: 1.4rem; overflow: hidden; box-shadow: 0 30px 80px rgba(0,0,0,.35); }
    .dark .pos { background: #0f172a; }
    .pos-top { background: linear-gradient(135deg, #7c3aed, #db2777); color: #fff; padding: 1rem 1.1rem .9rem; }
    .pos-x { width: 2.1rem; height: 2.1rem; border-radius: 999px; background: rgba(255,255,255,.18); display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .pos-x:hover { background: rgba(255,255,255,.3); }
    .pos-steps { display: flex; gap: .4rem; margin-top: .8rem; }
    .pos-steps span { flex: 1; height: .3rem; border-radius: 999px; background: rgba(255,255,255,.3); transition: background-color .25s; }
    .pos-steps span.is-on { background: #fff; }
    .pos-body { flex: 1; overflow-y: auto; overscroll-behavior: contain; padding: 1rem 1.1rem; }
    .pos-l { display: block; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; margin-bottom: .3rem; }
    .pos-in { width: 100%; border: 1.5px solid #e5e7eb; border-radius: .8rem; padding: .7rem .85rem; font-size: 1rem; background: #fff; color: #111827; transition: border-color .15s, box-shadow .15s; }
    .pos-in:focus { outline: none; border-color: #8b5cf6; box-shadow: 0 0 0 3px rgba(139,92,246,.18); }
    .pos-in.is-bad { border-color: #ef4444; }
    .dark .pos-in { background: #1e293b; border-color: #334155; color: #f1f5f9; }
    .pos-err { color: #dc2626; font-size: .75rem; margin-top: .25rem; }
    .pos-alert { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; border-radius: .8rem; padding: .6rem .8rem; font-size: .82rem; }
    .pos-tabs { display: grid; grid-auto-flow: column; grid-auto-columns: 1fr; gap: .5rem; }
    .pos-tab { display: flex; align-items: center; justify-content: center; gap: .4rem; padding: .65rem .5rem; border-radius: .9rem; border: 2px solid #e5e7eb; font-weight: 800; font-size: .92rem; background: #fff; transition: all .15s; }
    .dark .pos-tab { background: #1e293b; border-color: #334155; color: #e2e8f0; }
    .pos-tab img { width: 1.6rem; height: 1.6rem; object-fit: contain; }
    .pos-pay { border-radius: 1.1rem; padding: 1rem; margin-top: .75rem; border: 1.5px dashed; }
    .pos-big { font-size: 1.55rem; font-weight: 900; letter-spacing: .01em; line-height: 1.15; font-variant-numeric: tabular-nums; }
    .pos-copy { display: inline-flex; align-items: center; gap: .3rem; font-size: .75rem; font-weight: 800; padding: .4rem .7rem; border-radius: .65rem; color: #fff; flex-shrink: 0; }
    .pos-how { list-style: none; margin: .8rem 0 0; padding: 0; display: grid; gap: .4rem; font-size: .82rem; color: #374151; }
    .dark .pos-how { color: #cbd5e1; }
    .pos-how li { display: flex; gap: .55rem; align-items: flex-start; }
    .pos-how b.n { width: 1.25rem; height: 1.25rem; border-radius: 999px; color: #fff; font-size: .68rem; display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; margin-top: .05rem; }
    .pos-sum { display: grid; grid-template-columns: 1fr 1fr; gap: .5rem; margin-top: .9rem; }
    .pos-sum div { border-radius: .9rem; padding: .6rem .75rem; background: #f8fafc; }
    .dark .pos-sum div { background: #1e293b; }
    .pos-sum span { display: block; font-size: .66rem; text-transform: uppercase; font-weight: 700; color: #64748b; letter-spacing: .04em; }
    .pos-sum b { font-size: 1.05rem; font-weight: 900; color: #0f172a; }
    .dark .pos-sum b { color: #f8fafc; }
    .pos-foot { padding: .85rem 1.1rem calc(.85rem + env(safe-area-inset-bottom)); border-top: 1px solid #f1f5f9; background: #fff; }
    .dark .pos-foot { background: #0f172a; border-color: #1e293b; }
    .pos-trust { display: flex; flex-wrap: wrap; justify-content: center; gap: .3rem .9rem; margin-top: .55rem; font-size: .7rem; color: #64748b; }
    @media (max-width: 640px) {
        .pos-ov { align-items: flex-end; padding: 0; }
        .pos { max-width: none; border-radius: 1.4rem 1.4rem 0 0; max-height: 94vh; }
    }
</style>
<div class="mb-4 rounded-2xl overflow-hidden" style="border: 1px solid rgba(124,58,237,.3);"
     x-data="{
        q: {{ (int) old('quantity', 1) }}, zone: @js(old('zone', $poZones ? 'dhaka' : null) ?: 'dhaka'), zones: {{ $poZones ? 'true' : 'false' }},
        rates: @js($poRates), price: {{ $poPrice }}, freeMin: @js($poFree),
        pay: @js($poPay), method: @js(old('payment_method', $poPay[0]['slug'] ?? null)),
        sheet: {{ $poReopen ? 'true' : 'false' }}, step: {{ $poPayErrors ? 2 : 1 }}, sending: false,
        f: {
            shipping_name: @js((string) old('shipping_name', auth()->user()?->name)),
            shipping_phone: @js((string) old('shipping_phone', auth()->user()?->phone)),
            shipping_address: @js((string) old('shipping_address')),
            shipping_city: @js((string) old('shipping_city')),
            notes: @js((string) old('notes')),
            sender_number: @js((string) old('sender_number')),
            transaction_id: @js((string) old('transaction_id')),
        },
        bad: {},
        money(v) { return '৳' + Math.round(v).toLocaleString('en-IN') },
        get rate() { return this.zones ? this.rates[this.zone] : this.rates.flat },
        get subtotal() { return this.price * this.q },
        get delivery() { return this.freeMin !== null && this.subtotal >= this.freeMin ? 0 : this.rate },
        get total() { return this.subtotal + this.delivery },
        get later() { return Math.max(0, this.total - this.rate) },
        get pm() { return this.pay.find(m => m.slug === this.method) || this.pay[0] || {} },
        get advance() { return this.pm.amount ? this.pm.amount[this.zones ? this.zone : 'flat'] : this.rate },
        set(n) { this.q = Math.min(10, Math.max(1, parseInt(n) || 1)) },
        open() {
            // fill in what this customer typed at a previous checkout (same memory as checkout)
            try {
                const m = JSON.parse(localStorage.getItem('mv_checkout_v1') || 'null') || {};
                ['shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city'].forEach(k => { if (!this.f[k] && m[k]) this.f[k] = m[k]; });
                if (!@js($poReopen) && m.shipping_zone && this.zones) this.zone = m.shipping_zone;
            } catch (e) {}
            this.sheet = true; document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => { const el = this.$root.querySelector('[data-first]'); el && !el.value && el.focus(); });
        },
        close() { if (this.sending) return; this.sheet = false; document.documentElement.style.overflow = ''; },
        init() { if (this.sheet) document.documentElement.style.overflow = 'hidden'; },
        // Turbo keeps <html> across pages — never leave the page locked after leaving it
        destroy() { document.documentElement.style.overflow = ''; },
        phoneOk(v) { const d = String(v || '').replace(/[০-৯]/g, c => '০১২৩৪৫৬৭৮৯'.indexOf(c)).replace(/\D/g, '').replace(/^(?:00)?88/, ''); return /^01[3-9]\d{8}$/.test(d); },
        next() {
            this.bad = {};
            if (this.f.shipping_name.trim().length < 2) this.bad.shipping_name = 'Please write your name.';
            if (!this.phoneOk(this.f.shipping_phone)) this.bad.shipping_phone = 'Enter a valid mobile number (01XXXXXXXXX).';
            @if($poField('address') === 'required') if (this.f.shipping_address.trim().length < 5) this.bad.shipping_address = 'Please write your full address.'; @endif
            @if($poField('city') === 'required') if (!this.f.shipping_city.trim()) this.bad.shipping_city = 'Please write your city / district.'; @endif
            if (Object.keys(this.bad).length) return;
            if (!this.f.sender_number) this.f.sender_number = this.f.shipping_phone;
            this.step = 2; this.$refs.body && (this.$refs.body.scrollTop = 0);
        },
        submit(e) {
            this.bad = {};
            if (!this.phoneOk(this.f.sender_number)) this.bad.sender_number = 'The number you sent the money from.';
            if (this.f.transaction_id.trim().length < 6) this.bad.transaction_id = 'Paste the Transaction ID (TrxID) from the SMS.';
            if (Object.keys(this.bad).length) { e.preventDefault(); return; }
            this.sending = true;
            try {
                const m = JSON.parse(localStorage.getItem('mv_checkout_v1') || 'null') || {};
                ['shipping_name', 'shipping_phone', 'shipping_address', 'shipping_city'].forEach(k => { if (this.f[k].trim()) m[k] = this.f[k].trim(); });
                m.shipping_zone = this.zone; m.saved_at = Date.now();
                localStorage.setItem('mv_checkout_v1', JSON.stringify(m));
            } catch (err) {}
        },
        copy(text, el) {
            const done = () => { const o = el.innerHTML; el.innerHTML = '✓ Copied'; setTimeout(() => el.innerHTML = o, 1400); };
            if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(String(text)).then(done);
            const t = document.createElement('textarea'); t.value = String(text); t.style.cssText = 'position:fixed;opacity:0'; document.body.appendChild(t); t.select(); document.execCommand('copy'); t.remove(); done();
        },
        waLink() {
            const area = this.zones ? (this.zone === 'outside_dhaka' ? 'Outside Dhaka' : 'Inside Dhaka') : '';
            const msg = 'Assalamu alaikum, I want to pre-order:\n' + @js($product->name) + '\nQuantity: ' + this.q + (area ? '\nArea: ' + area : '') + '\nPrice: ' + this.money(this.subtotal) + '\n' + @js(route('products.show', $product->slug));
            const base = @js($poWa);
            return base ? base + (base.includes('?') ? '&' : '?') + 'text=' + encodeURIComponent(msg) : '#';
        },
     }" @keydown.escape.window="close()">
    <div class="px-4 py-3 text-white" style="background: linear-gradient(135deg, #7c3aed, #db2777);">
        <p class="font-extrabold flex items-center gap-2">📦 Pre-order available</p>
        <p class="text-sm opacity-90">Out of stock right now — book yours and get it as soon as the new stock lands.</p>
    </div>
    <form action="{{ route('checkout.preorder') }}" method="POST" class="p-4 bg-white dark:bg-gray-900 space-y-4"
          @if($poSheet) @submit.prevent="open()" @else data-show-loader data-loader-message="Preparing your pre-order&hellip;" @endif>
        @csrf
        <input type="hidden" name="product_id" value="{{ $product->id }}">
        <input type="hidden" name="zone" :value="zone">

        @if($poZones)
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-1.5">Delivery area</p>
                <div class="flex gap-2">
                    <button type="button" class="po-zone" :class="zone === 'dhaka' && 'is-on'" @click="zone = 'dhaka'">
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Inside Dhaka</span>
                        <span class="block font-extrabold text-gray-900 dark:text-white">৳{{ number_format($poRates['dhaka']) }}</span>
                    </button>
                    <button type="button" class="po-zone" :class="zone === 'outside_dhaka' && 'is-on'" @click="zone = 'outside_dhaka'">
                        <span class="block text-xs text-gray-500 dark:text-gray-400">Outside Dhaka</span>
                        <span class="block font-extrabold text-gray-900 dark:text-white">৳{{ number_format($poRates['outside_dhaka']) }}</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- what's paid when, live --}}
        <div class="grid grid-cols-2 gap-2 text-center">
            <div class="rounded-xl p-3" style="background: rgba(124,58,237,.08);">
                <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">Pay now</p>
                <p class="text-xl font-extrabold" style="color:#7c3aed;" x-text="money(rate)">৳{{ number_format($poZones ? $poRates['dhaka'] : $poRates['flat']) }}</p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400">delivery charge · {{ $poMethods }}</p>
            </div>
            <div class="rounded-xl p-3 bg-gray-50 dark:bg-gray-800">
                <p class="text-[11px] uppercase tracking-wide text-gray-500 dark:text-gray-400">On delivery</p>
                <p class="text-xl font-extrabold text-gray-900 dark:text-white" x-text="money(later)">৳{{ number_format($poPrice) }}</p>
                <p class="text-[11px] text-gray-500 dark:text-gray-400" x-text="q + ' × ' + money(price) + (delivery === 0 ? ' · free delivery' : '')"></p>
            </div>
        </div>

        <div class="flex items-stretch gap-3">
            <div class="po-qty" role="group" aria-label="Quantity">
                <button type="button" @click="set(q - 1)" :disabled="q <= 1" aria-label="Decrease quantity">−</button>
                <input type="number" name="quantity" x-model.number="q" @change="set(q)" min="1" max="10" inputmode="numeric" aria-label="Quantity" value="1">
                <button type="button" @click="set(q + 1)" :disabled="q >= 10" aria-label="Increase quantity">+</button>
            </div>
            <button type="submit" class="po-cta flex-1">Pre-order now →</button>
        </div>

        @if($poWa)
            <a :href="waLink()" target="_blank" rel="noopener" class="po-wa">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.25-.46-2.38-1.47-.88-.79-1.47-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.5h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35zM12.05 21.8h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.88 9.9-9.88 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.44 9.88-9.89 9.88zm8.41-18.3A11.82 11.82 0 0012.05 0C5.5 0 .16 5.34.16 11.9c0 2.1.55 4.14 1.6 5.95L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.9 0-3.18-1.24-6.17-3.48-8.4z"/></svg>
                Prefer to talk? Pre-order on WhatsApp
            </a>
        @endif

        <p class="text-xs text-gray-500 dark:text-gray-400">🚚 {{ setting('preorder_note', 'We ship as soon as the new stock arrives — usually within 7–15 days. We\'ll call you to confirm.') }}</p>
    </form>

    @if($poSheet)
    {{-- ───────────── the pre-order sheet ───────────── --}}
    <template x-teleport="body">
        <div class="pos-ov" x-show="sheet" x-cloak x-transition.opacity @click.self="close()" role="dialog" aria-modal="true" aria-label="Pre-order {{ $product->name }}">
            <form class="pos" method="POST" action="{{ route('checkout.preorder.place') }}" @submit="submit($event)" novalidate
                  x-show="sheet" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-6">
                @csrf
                <input type="hidden" name="_po_sheet" value="1">
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <input type="hidden" name="quantity" :value="q">
                <input type="hidden" name="zone" :value="zone">
                <input type="hidden" name="payment_method" :value="method">

                <div class="pos-top">
                    <div class="flex items-start gap-3">
                        <div class="w-12 h-12 rounded-xl bg-white overflow-hidden flex-shrink-0 flex items-center justify-center">
                            @if($product->image)<img src="{{ image_thumb($product->image, 96) }}" alt="" class="max-w-full max-h-full object-contain">@endif
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-[11px] font-bold uppercase tracking-wider opacity-80">Pre-order · step <span x-text="step"></span> of 2</p>
                            <p class="font-extrabold leading-snug line-clamp-2">{{ $product->name }}</p>
                            <p class="text-xs opacity-90 mt-0.5" x-text="q + ' × ' + money(price) + ' = ' + money(subtotal)"></p>
                        </div>
                        <button type="button" class="pos-x" @click="close()" aria-label="Close">
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <div class="pos-steps"><span class="is-on"></span><span :class="step === 2 && 'is-on'"></span></div>
                </div>

                <div class="pos-body" x-ref="body">
                    @if($poReopen && $errors->any())
                        <div class="pos-alert mb-3">⚠️ {{ $errors->first() }}</div>
                    @endif

                    {{-- Step 1 — where to deliver --}}
                    <div x-show="step === 1" class="space-y-3">
                        <div>
                            <label class="pos-l" for="po-name">Your name</label>
                            <input id="po-name" data-first name="shipping_name" x-model="f.shipping_name" class="pos-in" :class="bad.shipping_name && 'is-bad'" autocomplete="name" maxlength="255">
                            <p class="pos-err" x-show="bad.shipping_name" x-text="bad.shipping_name"></p>
                        </div>
                        <div>
                            <label class="pos-l" for="po-phone">Mobile number</label>
                            <input id="po-phone" name="shipping_phone" x-model="f.shipping_phone" class="pos-in" :class="bad.shipping_phone && 'is-bad'" type="tel" inputmode="tel" autocomplete="tel" placeholder="01XXXXXXXXX" maxlength="20">
                            <p class="pos-err" x-show="bad.shipping_phone" x-text="bad.shipping_phone"></p>
                        </div>
                        @if($poField('address') !== 'hidden')
                        <div>
                            <label class="pos-l" for="po-addr">Full address</label>
                            <textarea id="po-addr" name="shipping_address" x-model="f.shipping_address" rows="2" class="pos-in" :class="bad.shipping_address && 'is-bad'" autocomplete="street-address" placeholder="House, road, area" maxlength="500"></textarea>
                            <p class="pos-err" x-show="bad.shipping_address" x-text="bad.shipping_address"></p>
                        </div>
                        @endif
                        @if($poField('city') !== 'hidden')
                        <div>
                            <label class="pos-l" for="po-city">City / district</label>
                            <input id="po-city" name="shipping_city" x-model="f.shipping_city" class="pos-in" :class="bad.shipping_city && 'is-bad'" autocomplete="address-level2" placeholder="Dhaka" maxlength="100">
                            <p class="pos-err" x-show="bad.shipping_city" x-text="bad.shipping_city"></p>
                        </div>
                        @endif
                        @if($poZones)
                        <div>
                            <span class="pos-l">Delivery area</span>
                            <div class="flex gap-2">
                                <button type="button" class="po-zone" :class="zone === 'dhaka' && 'is-on'" @click="zone = 'dhaka'"><span class="block text-xs text-gray-500">Inside Dhaka</span><span class="block font-extrabold">৳{{ number_format($poRates['dhaka']) }}</span></button>
                                <button type="button" class="po-zone" :class="zone === 'outside_dhaka' && 'is-on'" @click="zone = 'outside_dhaka'"><span class="block text-xs text-gray-500">Outside Dhaka</span><span class="block font-extrabold">৳{{ number_format($poRates['outside_dhaka']) }}</span></button>
                            </div>
                        </div>
                        @endif
                        @if($poField('notes', 'optional') !== 'hidden')
                        <div>
                            <label class="pos-l" for="po-notes">Note <span class="normal-case font-medium">(optional)</span></label>
                            <input id="po-notes" name="notes" x-model="f.notes" class="pos-in" placeholder="Anything we should know?" maxlength="1000">
                        </div>
                        @endif
                    </div>

                    {{-- Step 2 — send the advance --}}
                    <div x-show="step === 2" x-cloak>
                        <span class="pos-l">Pay the advance with</span>
                        <div class="pos-tabs">
                            <template x-for="m in pay" :key="m.slug">
                                <button type="button" class="pos-tab" @click="method = m.slug"
                                        :style="method === m.slug ? 'border-color:' + m.color + ';background:' + m.tint + ';color:' + m.color : ''">
                                    <img x-show="m.logo" :src="m.logo" alt=""><span x-text="m.name"></span>
                                </button>
                            </template>
                        </div>

                        <div class="pos-pay" :style="'border-color:' + pm.color + ';background:' + pm.tint">
                            <div class="flex items-end justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-wider" :style="'color:' + pm.color">Send this amount</p>
                                    <p class="pos-big" :style="'color:' + pm.color" x-text="money(advance)"></p>
                                </div>
                                <button type="button" class="pos-copy" :style="'background:' + pm.color" @click="copy(Math.round(advance), $el)">📋 Copy</button>
                            </div>
                            <div class="flex items-end justify-between gap-3 mt-3 pt-3" style="border-top: 1px dashed rgba(0,0,0,.12)">
                                <div class="min-w-0">
                                    <p class="text-[11px] font-bold uppercase tracking-wider text-gray-500">To this <span x-text="pm.name"></span> number</p>
                                    <p class="pos-big text-gray-900 dark:text-white" x-text="pm.number || 'Number not set yet'"></p>
                                    <p class="text-xs text-gray-500" x-show="pm.holder" x-text="pm.holder"></p>
                                </div>
                                <button type="button" class="pos-copy" x-show="pm.number" :style="'background:' + pm.color" @click="copy(pm.number, $el)">📋 Copy</button>
                            </div>
                            <ol class="pos-how">
                                <li><b class="n" :style="'background:' + pm.color">1</b><span>Open your <b x-text="pm.name"></b> app and choose <b>Send Money</b>.</span></li>
                                <li><b class="n" :style="'background:' + pm.color">2</b><span>Send <b x-text="money(advance)"></b> to the number above.</span></li>
                                <li><b class="n" :style="'background:' + pm.color">3</b><span>Write the <b>TrxID</b> from the confirmation SMS below.</span></li>
                            </ol>
                            <p class="text-xs text-gray-500 mt-2" x-show="pm.note" x-text="pm.note"></p>
                        </div>

                        <div class="grid grid-cols-1 gap-3 mt-4">
                            <div>
                                <label class="pos-l" for="po-sender">Sent from number</label>
                                <input id="po-sender" name="sender_number" x-model="f.sender_number" class="pos-in" :class="bad.sender_number && 'is-bad'" type="tel" inputmode="tel" placeholder="01XXXXXXXXX" maxlength="20">
                                <p class="pos-err" x-show="bad.sender_number" x-text="bad.sender_number"></p>
                            </div>
                            <div>
                                <label class="pos-l" for="po-trx">Transaction ID (TrxID)</label>
                                <input id="po-trx" name="transaction_id" x-model="f.transaction_id" @input="f.transaction_id = f.transaction_id.toUpperCase().replace(/\s+/g, '')" class="pos-in" :class="bad.transaction_id && 'is-bad'" style="letter-spacing:.08em;font-weight:700" placeholder="e.g. 9A7B6C5D4E" autocomplete="off" autocapitalize="characters" maxlength="100">
                                <p class="pos-err" x-show="bad.transaction_id" x-text="bad.transaction_id"></p>
                            </div>
                        </div>

                        <div class="pos-sum">
                            <div><span>Advance now</span><b x-text="money(advance)"></b></div>
                            <div><span>Pay on delivery</span><b x-text="money(later)"></b></div>
                        </div>
                    </div>
                </div>

                <div class="pos-foot">
                    <template x-if="step === 1">
                        <button type="button" class="po-cta w-full py-3.5 text-base" @click="next()">Continue to payment →</button>
                    </template>
                    <template x-if="step === 2">
                        <div class="flex gap-2">
                            <button type="button" class="px-4 rounded-xl font-bold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-800" @click="step = 1">←</button>
                            <button type="submit" class="po-cta flex-1 py-3.5 text-base" :disabled="sending" x-text="sending ? 'Placing your pre-order…' : 'Confirm pre-order ✓'"></button>
                        </div>
                    </template>
                    <div class="pos-trust">
                        <span>🔒 Secure order</span><span>💸 Advance is deducted from your total</span><span>📞 We call to confirm</span>
                    </div>
                </div>
            </form>
        </div>
    </template>
    @endif
</div>
