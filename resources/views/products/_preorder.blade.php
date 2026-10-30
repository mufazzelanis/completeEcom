{{-- Out of stock → "Pre-order": book it now by paying only the delivery charge by bKash /
     Nagad; the rest is paid on delivery (CheckoutController::preorder, checkout flags it).
     Delivery charges come live from Settings → Shipping (Inside / Outside Dhaka, or the flat
     rate); "Pay now" / "On delivery" update as the area and quantity change. --}}
@php
    $poMethods = \App\Models\Product::preorderPaymentMethods()->pluck('name')->implode(' / ');
    $poZones = \App\Services\ShippingCalculator::usesZones();
    $poRates = [
        'dhaka' => \App\Http\Controllers\CheckoutController::preorderAdvanceBase('dhaka'),
        'outside_dhaka' => \App\Http\Controllers\CheckoutController::preorderAdvanceBase('outside_dhaka'),
        'flat' => \App\Http\Controllers\CheckoutController::preorderAdvanceBase(null),
    ];
    $poFree = setting('free_shipping_enabled', '0') === '1' ? (float) setting('free_shipping_min', 999) : null;
    $poPrice = (float) $product->final_price;
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
    /* the number itself — explicit size/colour/padding so no theme or form reset can hide it */
    .po-qty input { width: 2.75rem; height: 3rem; padding: 0; border: 0; background: transparent; text-align: center; font-size: 1.05rem; font-weight: 800; color: #111827; -moz-appearance: textfield; appearance: textfield; }
    .dark .po-qty input { color: #f9fafb; }
    .po-qty input::-webkit-outer-spin-button, .po-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .po-qty input:focus { outline: none; box-shadow: none; }
</style>
<div class="mb-4 rounded-2xl overflow-hidden" style="border: 1px solid rgba(124,58,237,.3);"
     x-data="{
        q: 1, zone: 'dhaka', zones: {{ $poZones ? 'true' : 'false' }},
        rates: @js($poRates), price: {{ $poPrice }}, freeMin: @js($poFree),
        money(v) { return '৳' + Math.round(v).toLocaleString('en-IN') },
        get rate() { return this.zones ? this.rates[this.zone] : this.rates.flat },
        get subtotal() { return this.price * this.q },
        get delivery() { return this.freeMin !== null && this.subtotal >= this.freeMin ? 0 : this.rate },
        get total() { return this.subtotal + this.delivery },
        get later() { return Math.max(0, this.total - this.rate) },
        set(n) { this.q = Math.min(10, Math.max(1, parseInt(n) || 1)) },
     }">
    <div class="px-4 py-3 text-white" style="background: linear-gradient(135deg, #7c3aed, #db2777);">
        <p class="font-extrabold flex items-center gap-2">📦 Pre-order available</p>
        <p class="text-sm opacity-90">Out of stock right now — book yours and get it as soon as the new stock lands.</p>
    </div>
    <form action="{{ route('checkout.preorder') }}" method="POST" class="p-4 bg-white dark:bg-gray-900 space-y-4" data-show-loader data-loader-message="Preparing your pre-order&hellip;">
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
            <button type="submit" class="flex-1 rounded-xl font-bold text-white transition hover:brightness-110" style="background: linear-gradient(135deg, #7c3aed, #db2777);">
                Pre-order now →
            </button>
        </div>

        <p class="text-xs text-gray-500 dark:text-gray-400">🚚 {{ setting('preorder_note', 'We ship as soon as the new stock arrives — usually within 7–15 days. We\'ll call you to confirm.') }}</p>
    </form>
</div>
