{{-- Out of stock → "Pre-order": book it now by paying only the delivery charge by bKash /
     Nagad; the rest is paid on delivery (CheckoutController::preorder, checkout flags it). --}}
@php
    $poMethods = \App\Models\Product::preorderPaymentMethods()->pluck('name')->implode(' / ');
    $poFrom = min(\App\Services\ShippingCalculator::calculate(0, \App\Services\ShippingCalculator::ZONE_DHAKA), \App\Services\ShippingCalculator::calculate(0, \App\Services\ShippingCalculator::ZONE_OUTSIDE_DHAKA));
@endphp
<div class="mb-4 rounded-2xl overflow-hidden" style="border: 1px solid rgba(124,58,237,.3);">
    <div class="px-4 py-3 text-white" style="background: linear-gradient(135deg, #7c3aed, #db2777);">
        <p class="font-extrabold flex items-center gap-2">📦 Pre-order available</p>
        <p class="text-sm opacity-90">Out of stock right now — book yours and get it as soon as the new stock lands.</p>
    </div>
    <div class="p-4 bg-white dark:bg-gray-900">
        <ul class="text-sm text-gray-700 dark:text-gray-300 space-y-1.5 mb-4">
            <li>✅ Pay only the <strong>delivery charge</strong> now (from ৳{{ number_format($poFrom) }}) by <strong>{{ $poMethods }}</strong></li>
            <li>💵 The rest (৳{{ number_format($product->final_price) }} per item) on delivery</li>
            <li>🚚 {{ setting('preorder_note', 'We ship as soon as the new stock arrives — usually within 7–15 days. We\'ll call you to confirm.') }}</li>
        </ul>
        <form action="{{ route('checkout.preorder') }}" method="POST" class="flex items-stretch gap-3" data-show-loader data-loader-message="Preparing your pre-order&hellip;">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <div class="inline-flex items-center bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden" x-data="{ q: 1 }">
                <button type="button" @click="q = Math.max(1, q - 1)" class="w-10 h-11 text-lg font-bold text-gray-500" aria-label="Less">−</button>
                <input type="number" name="quantity" x-model.number="q" min="1" max="10" aria-label="Quantity" class="w-10 text-center bg-transparent border-0 text-sm font-bold focus:ring-0">
                <button type="button" @click="q = Math.min(10, q + 1)" class="w-10 h-11 text-lg font-bold text-gray-500" aria-label="More">+</button>
            </div>
            <button type="submit" class="flex-1 py-3 rounded-xl font-bold text-white transition hover:brightness-110" style="background: linear-gradient(135deg, #7c3aed, #db2777);">
                Pre-order now →
            </button>
        </form>
    </div>
</div>
