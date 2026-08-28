@extends('layouts.app')
@section('title', 'Order Placed!')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div class="bg-white rounded-2xl shadow-sm p-12">
        <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Order Placed Successfully!</h1>
        <p class="text-gray-500 mb-2">Thank you for your order.</p>
        <p class="text-orange-700 font-bold text-lg mb-6">{{ $order->order_number }}</p>

        @if($accountCreated ?? false)
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-left">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div>
                        <p class="text-sm font-semibold text-green-700">Account Created!</p>
                        <p class="text-sm text-green-600">Your account has been created with phone number <strong>{{ auth()->user()->phone }}</strong>.</p>
                        <p class="text-xs text-green-500 mt-1">Next time, just login with your phone number to track orders and manage your account.</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-gray-50 rounded-xl p-6 text-left mb-8">
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500">Payment Method</p>
                    <p class="font-semibold capitalize text-gray-800">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method) }}</p>
                </div>
                <div>
                    <p class="text-gray-500">Order Total</p>
                    <p class="font-semibold text-gray-800">৳{{ number_format($order->total) }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-gray-500">Shipping to</p>
                    <p class="font-semibold text-gray-800">{{ $order->shipping_name }}, {{ $order->shipping_address }}, {{ $order->shipping_city }}</p>
                </div>
            </div>
        </div>

        @php $digitalItems = $order->items->filter(fn ($item) => $item->product?->isDigital()); @endphp
        @if($digitalItems->isNotEmpty())
        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-6 text-left mb-8">
            <h3 class="font-semibold text-indigo-800 mb-3 text-sm">Your digital downloads are ready</h3>
            <div class="space-y-2">
                @foreach($digitalItems as $item)
                    <a href="{{ route('orders.download', [$order, $item]) }}" class="flex items-center justify-between bg-white rounded-lg px-4 py-3 hover:bg-indigo-50/50 transition">
                        <span class="text-sm text-gray-700 dark:text-gray-700">{{ $item->product_name }}</span>
                        <span class="inline-flex items-center gap-1 text-indigo-600 text-xs font-semibold">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-8-3V4m0 12l-4-4m4 4l4-4"/></svg>
                            Download
                        </span>
                    </a>
                @endforeach
            </div>
            <p class="text-xs text-indigo-400 mt-3">If your payment needs manual verification, downloads unlock once it's confirmed.</p>
        </div>
        @endif

        <div class="flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-4 justify-center">
            <a href="{{ route('orders.show', $order->id) }}" class="bg-orange-500 text-white px-6 py-3 rounded-xl font-bold hover:bg-orange-600 transition">
                View Order Details
            </a>
            <a href="{{ route('shop.index') }}" class="border border-gray-200 text-gray-700 px-6 py-3 rounded-xl font-bold hover:bg-gray-50 transition">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

@if($shouldTrackPurchase ?? false)
@php
    $currencyCode = setting('currency_code', 'BDT');
    $trackedItems = $order->items->map(fn ($item) => [
        'id'       => $item->product?->sku ?: (string) $item->product_id,
        'name'     => $item->product_name,
        'price'    => (float) $item->price,
        'quantity' => $item->quantity,
    ])->values();

    // Re-derived here rather than reused from layouts/app.blade.php — @extends
    // evaluates this @section before the parent layout's own @php block runs,
    // so those variables aren't in scope here. setting() is request-cached
    // (see Setting::allFresh()), so this costs nothing extra.
    $pixelId = setting('facebook_pixel_id', '');
    $pixelOn = $pixelId && setting('facebook_pixel_enabled', $pixelId ? '1' : '0') == '1';
    $fbAdvancedMatchingOn = setting('facebook_advanced_matching_enabled', '0') == '1';
    $googleEnhancedOn = setting('google_enhanced_conversions_enabled', '0') == '1';
    $adsConversionId = setting('google_ads_conversion_id', '');
    $adsPurchaseLabel = setting('google_ads_purchase_label', '');

    // The order's own shipping details — richer than the general logged-in-user
    // profile the layout defaults to, and the only source at all for a guest
    // checkout (whose email/phone only became known by placing this order).
    $orderTrackingData = pixel_advanced_matching_data(
        $order->shipping_name, $order->user?->email, $order->shipping_phone,
        $order->shipping_city, $order->shipping_state, $order->shipping_zip, $order->shipping_country
    );
@endphp
<script>
// Fires once per order (guarded server-side) so GA/GTM/Meta each get exactly one
// Purchase conversion — duplicate fires would inflate revenue and skew ad optimization.
(function () {
    var items = @json($trackedItems);
    var orderId = @json($order->order_number);
    var value = {{ (float) $order->total }};
    var currency = @json($currencyCode);

    @if($googleEnhancedOn && $orderTrackingData['google'])
    if (typeof gtag === 'function') {
        gtag('set', 'user_data', {!! Js::from($orderTrackingData['google']) !!});
    }
    @endif

    if (typeof gtag === 'function') {
        gtag('event', 'purchase', {
            transaction_id: orderId,
            value: value,
            currency: currency,
            items: items.map(function (i) {
                return { item_id: i.id, item_name: i.name, price: i.price, quantity: i.quantity };
            })
        });
        @if($adsConversionId && $adsPurchaseLabel)
        gtag('event', 'conversion', {
            send_to: {!! Js::from($adsConversionId . '/' . $adsPurchaseLabel) !!},
            value: value,
            currency: currency,
            transaction_id: orderId,
        });
        @endif
    }

    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({
        event: 'purchase',
        ecommerce: {
            transaction_id: orderId,
            value: value,
            currency: currency,
            items: items
        }
    });

    @if($pixelOn)
    if (typeof fbq === 'function') {
        @if($fbAdvancedMatchingOn && $orderTrackingData['fb'])
        // Refresh Advanced Matching with this order's shipping details right
        // before the highest-value event fires — see the @php block above.
        fbq('init', {!! Js::from($pixelId) !!}, {!! Js::from($orderTrackingData['fb']) !!});
        @endif
        fbq('track', 'Purchase', {
            value: value,
            currency: currency,
            content_type: 'product',
            content_ids: items.map(function (i) { return i.id; })
        });
    }
    @endif
})();
</script>
@endif
@endsection
