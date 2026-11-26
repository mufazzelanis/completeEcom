@extends('layouts.app')
@section('title', 'Shopping Cart')

@push('meta')
{{-- Cart contents/stock can change between visits — Turbo Drive (resources/js/app.js)
     should always fetch this page fresh, never show a cached snapshot. --}}
<meta name="turbo-cache-control" content="no-cache">
@endpush

@section('content')
@php
    $itemCount = (int) $cartItems->sum('quantity');
    $cartHelpWa = whatsapp_chat_url("Hi, I need help with my order on " . setting('site_name', 'the shop') . '.');
@endphp
{{-- 2026-11 redesign — "focus mode" on phones (no bottom menu / chat bubble / pinned search,
     see .shop-focus in resources/css/app.css): the items, a clear total, and one obvious way
     forward — the sticky Checkout bar at the bottom of the screen. --}}
<div class="max-w-7xl mx-auto px-3 sm:px-4 pt-4 pb-6 md:py-8">
    <div class="flex items-end justify-between gap-3 mb-2">
        <h1 class="text-xl sm:text-2xl font-extrabold text-gray-900">Shopping Cart</h1>
        @if($itemCount)
            <span class="ct-chip">{{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}</span>
        @endif
    </div>
    @if($cartItems->isNotEmpty())
        <div class="ct-trust mb-4 md:mb-6">
            <span>🔒 Secure checkout</span><span>💵 Cash on Delivery</span><span>🚚 Fast delivery</span>
        </div>
    @endif

    @if($cartItems->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm p-8 md:p-16 text-center">
            <svg class="w-20 h-20 md:w-24 md:h-24 text-gray-200 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <h3 class="text-xl font-semibold text-gray-700 mb-3">Your cart is empty</h3>
            <p class="text-gray-500 mb-6">Browse products and add items to your cart</p>
            <a href="{{ route('shop.index') }}" class="bg-orange-500 text-white px-8 py-3 rounded-xl font-bold hover:bg-orange-600 transition inline-block">
                Start Shopping
            </a>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 lg:gap-8">
            <div class="lg:col-span-2 space-y-3">
                @foreach($cartItems as $item)
                    @php $stock = $item->product->available_stock; @endphp
                    <div class="ct-item bg-white rounded-2xl shadow-sm p-3 sm:p-4 md:p-5">
                        <div class="flex gap-3 sm:gap-4">
                            <a href="{{ route('products.show', $item->product->slug) }}" class="ct-img">
                                @if($item->product->image)
                                    <img src="{{ image_thumb($item->product->image, 200) }}" alt="{{ $item->product->name }}" loading="lazy" decoding="async" class="w-full h-full object-contain">
                                @else
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                @endif
                            </a>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start gap-2">
                                    <a href="{{ route('products.show', $item->product->slug) }}" class="flex-1 min-w-0 font-semibold text-gray-900 text-sm sm:text-base leading-snug line-clamp-2 hover:text-orange-700">{{ $item->product->name }}</a>
                                    <form action="{{ route('cart.remove', $item->id) }}" method="POST" class="flex-shrink-0">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="ct-remove" aria-label="Remove {{ $item->product->name ?? 'item' }}" title="Remove">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5 truncate">
                                    {{ $item->product->category->name ?? '' }}@if($item->combination?->label) · {{ $item->combination->label }}@endif
                                </p>
                                <p class="text-sm font-bold text-orange-700 mt-1">৳{{ number_format($item->unit_price) }} <span class="text-xs font-medium text-gray-400">each</span></p>

                                <div class="flex items-center justify-between gap-2 mt-2.5">
                                    <form action="{{ route('cart.update', $item->id) }}" method="POST" class="ct-qty">
                                        @csrf @method('PATCH')
                                        <button type="button" aria-label="One less" onclick="this.form.quantity.value = Math.max(1, parseInt(this.form.quantity.value)-1); this.form.submit();" @disabled($item->quantity <= 1)>−</button>
                                        <input type="number" name="quantity" aria-label="Quantity" value="{{ $item->quantity }}" min="1" max="{{ $stock }}" onchange="this.form.submit()">
                                        <button type="button" aria-label="One more" onclick="this.form.quantity.value = Math.min({{ $stock }}, parseInt(this.form.quantity.value)+1); this.form.submit();" @disabled($item->quantity >= $stock)>+</button>
                                    </form>
                                    <span class="text-base sm:text-lg font-extrabold text-gray-900 tabular-nums">৳{{ number_format($item->subtotal) }}</span>
                                </div>
                                @if($stock > 0 && $stock <= 5)
                                    <p class="text-[11px] font-semibold text-red-600 mt-1.5">⚡ Only {{ $stock }} left in stock</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
                <a href="{{ route('shop.index') }}" class="hidden lg:inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500 hover:text-orange-700 pt-1">← Continue shopping</a>
            </div>

            <div class="space-y-4">
                @include('partials.coupon-box')

                @auth
                <div class="bg-white rounded-2xl shadow-sm p-5 sm:p-6">
                    <h3 class="font-semibold text-gray-800 mb-4">Use Your Points</h3>
                    <p class="text-xs text-gray-400 mb-3">Balance: <span class="font-semibold text-gray-600">{{ number_format($pointsBalance) }} pts</span></p>
                    @if($pointsRedeemed > 0)
                        <div class="flex items-center justify-between bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                            <div>
                                <p class="text-sm font-semibold text-green-700">{{ number_format($pointsRedeemed) }} pts applied</p>
                                <p class="text-xs text-green-500">-৳{{ number_format($pointsDiscount) }} discount</p>
                            </div>
                            <form action="{{ route('cart.points.remove') }}" method="POST">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                            </form>
                        </div>
                    @elseif($pointsBalance > 0)
                        <form action="{{ route('cart.points') }}" method="POST" class="flex space-x-2">
                            @csrf
                            <input type="number" name="points" min="1" max="{{ $pointsBalance }}" placeholder="Points to use"
                                class="flex-1 min-w-0 border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                            <button type="submit" class="bg-orange-500 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-600 transition flex-shrink-0">Apply</button>
                        </form>
                    @else
                        <p class="text-xs text-gray-400">You don't have any points yet — earn points from purchases and referrals.</p>
                    @endif
                </div>
                @endauth

                <div class="bg-white rounded-2xl shadow-sm p-5 sm:p-6 lg:sticky lg:top-24">
                    <h3 class="font-bold text-gray-900 mb-4">Order Summary</h3>
                    @include('partials.free-delivery-progress', ['subtotal' => $subtotal, 'class' => 'mb-4'])
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal ({{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }})</span>
                            <span class="font-semibold text-gray-800">৳{{ number_format($subtotal) }}</span>
                        </div>
                        @if(($discount - $pointsDiscount) > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-৳{{ number_format($discount - $pointsDiscount) }}</span>
                            </div>
                        @endif
                        @if($pointsDiscount > 0)
                            <div class="flex justify-between text-green-600">
                                <span>Points Discount</span>
                                <span>-৳{{ number_format($pointsDiscount) }}</span>
                            </div>
                        @endif
                        @php
                            $cartZone = $shippingZone ?? \App\Services\ShippingCalculator::estimateZone();
                            $cartOther = \App\Services\ShippingCalculator::otherZone($cartZone);
                        @endphp
                        <div class="flex justify-between gap-3 text-gray-600">
                            <span>Delivery
                                @if(\App\Services\ShippingCalculator::usesZones() && $shipping > 0)
                                    <span class="block text-xs text-gray-400">{{ \App\Services\ShippingCalculator::label($cartZone) }} · {{ \App\Services\ShippingCalculator::label($cartOther) }} ৳{{ number_format(\App\Services\ShippingCalculator::calculate($subtotal, $cartOther)) }} — pick at checkout</span>
                                @endif
                            </span>
                            <span class="font-semibold {{ $shipping > 0 ? 'text-gray-800' : 'text-green-600' }} whitespace-nowrap">{{ $shipping > 0 ? '৳' . number_format($shipping) : 'Free' }}</span>
                        </div>
                        <div class="border-t border-dashed border-gray-200 pt-3 flex justify-between items-baseline">
                            <span class="font-bold text-gray-900">Total</span>
                            <span class="text-2xl font-extrabold text-gray-900 tabular-nums">৳{{ number_format($total) }}</span>
                        </div>
                    </div>

                    <a href="{{ route('checkout.index') }}" id="ct-checkout" class="ct-go mt-5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Proceed to Checkout
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                    </a>
                    <p class="text-center text-xs text-gray-500 mt-2.5">No payment now — pay cash when it arrives</p>

                    <div class="flex items-center justify-center gap-4 mt-4 pt-4 border-t border-gray-100 text-sm">
                        <a href="{{ route('shop.index') }}" class="font-semibold text-gray-500 hover:text-orange-700">← Continue shopping</a>
                        @if($cartHelpWa)
                            <span class="text-gray-200">|</span>
                            <a href="{{ $cartHelpWa }}" target="_blank" rel="noopener" class="font-semibold text-green-600 hover:text-green-700">💬 Need help?</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @if($crossSellProducts->isNotEmpty())
            <div class="mt-10">
                <h2 class="text-lg font-bold text-gray-900 mb-4">You may also like</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
                    @foreach($crossSellProducts as $p)
                        @include('partials.product-card', ['product' => $p])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- phones / tablets: total + Checkout always at hand; slides away while the summary's own button is on screen --}}
        <div class="ct-bar lg:hidden" data-focus-bar id="ct-bar">
            <div class="ct-bar-in">
                <div class="min-w-0 flex-1">
                    <p class="text-[11px] text-gray-500 leading-none">Total · {{ $itemCount }} {{ \Illuminate\Support\Str::plural('item', $itemCount) }}</p>
                    <p class="text-lg font-extrabold text-gray-900 leading-tight tabular-nums">৳{{ number_format($total) }}</p>
                </div>
                <a href="{{ route('checkout.index') }}" class="ct-bar-go">Checkout
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
    @endif
</div>

<style>
    .ct-chip { font-size: .75rem; font-weight: 700; color: rgb(var(--brand-700)); background: rgb(var(--brand-50)); border: 1px solid rgb(var(--brand-200)); padding: .2rem .6rem; border-radius: 999px; white-space: nowrap; }
    .ct-trust { display: flex; flex-wrap: wrap; gap: .35rem .9rem; font-size: .72rem; font-weight: 600; color: #475569; }
    .ct-img { flex-shrink: 0; width: 5.25rem; height: 5.25rem; border-radius: .9rem; background: #f8fafc; border: 1px solid #eef2f7; padding: .35rem; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    @media (min-width: 768px) { .ct-img { width: 6rem; height: 6rem; } }
    .ct-remove { width: 2rem; height: 2rem; border-radius: .6rem; display: flex; align-items: center; justify-content: center; color: #94a3b8; background: #f8fafc; transition: color .15s, background .15s; }
    .ct-remove:hover, .ct-remove:active { color: #dc2626; background: #fef2f2; }
    .ct-qty { display: inline-flex; align-items: center; border: 1.5px solid #e2e8f0; border-radius: .8rem; background: #f8fafc; overflow: hidden; }
    .ct-qty button { width: 2.35rem; height: 2.35rem; display: flex; align-items: center; justify-content: center; font-size: 1.15rem; font-weight: 700; color: #334155; }
    .ct-qty button:active:not(:disabled) { background: #e2e8f0; }
    .ct-qty button:disabled { color: #cbd5e1; }
    .ct-qty input { width: 2.6rem; height: 2.35rem; text-align: center; font-weight: 800; font-size: .95rem; color: #0f172a; background: #fff; border: 0; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; -moz-appearance: textfield; }
    .ct-qty input::-webkit-outer-spin-button, .ct-qty input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    .ct-go { display: flex; align-items: center; justify-content: center; gap: .5rem; width: 100%; padding: .95rem 1rem; border-radius: 1rem; font-weight: 800; font-size: 1rem; color: #fff;
        background: linear-gradient(135deg, rgb(var(--brand-500)), rgb(var(--brand-600))); box-shadow: 0 10px 24px -10px rgb(var(--brand-600) / .8); transition: transform .15s, box-shadow .15s; }
    .ct-go:hover { box-shadow: 0 14px 28px -10px rgb(var(--brand-600) / .9); } .ct-go:active { transform: scale(.98); }
    .ct-bar { position: fixed; left: 0; right: 0; bottom: 5rem; z-index: 40; padding: 0 .75rem; transition: transform .28s ease, opacity .28s ease; }
    .ct-bar.is-off { transform: translateY(140%); opacity: 0; pointer-events: none; }
    .ct-bar-in { max-width: 28rem; margin: 0 auto; display: flex; align-items: center; gap: .75rem; padding: .5rem .5rem .5rem 1rem; border-radius: 1.15rem;
        background: rgba(255, 255, 255, .96); -webkit-backdrop-filter: blur(14px); backdrop-filter: blur(14px); box-shadow: 0 14px 34px -10px rgba(15, 23, 42, .35); border: 1px solid rgba(15, 23, 42, .06); }
    .ct-bar-go { flex-shrink: 0; display: inline-flex; align-items: center; gap: .35rem; padding: .85rem 1.25rem; border-radius: .9rem; font-weight: 800; color: #fff; background: linear-gradient(135deg, rgb(var(--brand-500)), rgb(var(--brand-600))); box-shadow: 0 8px 18px -8px rgb(var(--brand-600) / .9); }
    .ct-bar-go:active { transform: scale(.96); }
    .dark .ct-trust { color: #94a3b8; } .dark .ct-chip { background: rgb(var(--brand-950)); border-color: rgb(var(--brand-800)); color: rgb(var(--brand-300)); }
    .dark .ct-img, .dark .ct-remove, .dark .ct-qty { background: #111827; border-color: #1f2937; }
    .dark .ct-qty input { background: #0b1222; color: #f8fafc; border-color: #1f2937; } .dark .ct-qty button { color: #e2e8f0; }
    .dark .ct-bar-in { background: rgba(17, 24, 39, .95); border-color: rgba(255, 255, 255, .08); }
</style>
@if($cartItems->isNotEmpty())
<script>
(function () {
    var bar = document.getElementById('ct-bar'), go = document.getElementById('ct-checkout');
    if (!bar || !go || !('IntersectionObserver' in window)) return;
    document.body.appendChild(bar); // fixed position must not sit inside a transformed parent
    new IntersectionObserver(function (e) { bar.classList.toggle('is-off', e[0].isIntersecting); }, { threshold: 0.4 }).observe(go);
})();
</script>
@php
    $cartItems->pluck('product')->filter()->each->loadMissing('brand', 'category');
    $gaCartItems = $cartItems->filter(fn ($i) => $i->product)->map(fn ($i) => tracking_item($i->product, [
        'price' => (float) $i->unit_price, 'quantity' => (int) $i->quantity, 'item_variant' => $i->combination?->label,
    ]))->values();
@endphp
{{-- GA4 view_cart — the step between add_to_cart and begin_checkout in the funnel report --}}
<script>window.mvTrack && window.mvTrack('view_cart', { value: {{ (float) $cartItems->sum('subtotal') }}, items: @json($gaCartItems) });</script>
@endif
@endsection
