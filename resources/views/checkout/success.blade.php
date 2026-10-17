@extends('layouts.app')
@section('title', 'Order Placed!')

@section('content')
<div id="confetti-container" class="pointer-events-none fixed inset-0 overflow-hidden z-[9998]" aria-hidden="true"></div>

<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div id="thankyou-card" class="bg-white rounded-2xl shadow-sm p-12 dl-reveal">
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
                        <p class="text-xs text-green-600 mt-1">Set a password now to log in later with your phone number — to track this order and rate the products once they arrive.</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- First password for the account just created (it's random otherwise, with no email to
             reset through) — offered for the whole session, so it survives a page refresh. --}}
        @if(session('password_set'))
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 text-left">
                <p class="text-sm font-semibold text-green-700">✓ Password saved — log in any time with {{ auth()->user()->phone }} and your password.</p>
            </div>
        @elseif(session('can_set_password') === auth()->id())
            {{-- Same <x-auth-field> as the login page (eye button to show/hide each field), plus a
                 live strength meter, match check and Caps Lock warning; Save stays disabled until
                 the password is valid and both fields match. Meter colors are inline hex, not
                 Tailwind classes — the orange/red palettes follow the admin's brand colors here,
                 which would turn a "weak" bar into brand blue. --}}
            <form method="POST" action="{{ route('account.set-password') }}" class="bg-white border border-gray-200 rounded-xl p-4 mb-6 text-left space-y-4"
                  x-data="{
                      pw: '', pw2: '', caps: false, saving: false,
                      get score() {
                          const p = this.pw; if (p.length < 6) return 0;
                          let s = 1;
                          if (p.length >= 10) s++;
                          if (/[a-zA-Z]/.test(p) && /\d/.test(p)) s++;
                          if (/[A-Z]/.test(p) && /[a-z]/.test(p) || /[^a-zA-Z0-9]/.test(p)) s++;
                          return Math.min(s, 4);
                      },
                      get level() { return [['Too short', '#9ca3af'], ['Weak', '#f43f5e'], ['Fair', '#f59e0b'], ['Good', '#14b8a6'], ['Strong', '#10b981']][this.score]; },
                      get matches() { return this.pw2.length > 0 && this.pw === this.pw2; },
                      get valid() { return this.pw.length >= 6 && this.matches; },
                      checkCaps(e) { this.caps = !!(e.getModifierState && e.getModifierState('CapsLock')); },
                  }"
                  @submit="if (!valid) { $event.preventDefault(); return; } saving = true">
                @csrf
                <div>
                    <p class="text-sm font-semibold text-gray-800">Set your password</p>
                    <p class="text-xs text-gray-500 mt-0.5">You'll log in with <strong>{{ auth()->user()->phone }}</strong> and this password.</p>
                </div>

                <div>
                    <x-auth-field id="new-password" name="password" type="password" icon="lock" label="Password"
                                  required minlength="6" autocomplete="new-password" placeholder="At least 6 characters"
                                  x-model="pw" x-on:keyup="checkCaps($event)" x-on:keydown="checkCaps($event)" />
                    {{-- strength meter: 4 segments filled up to the current score --}}
                    <div class="mt-2" x-show="pw.length" x-cloak>
                        <div class="flex gap-1" aria-hidden="true">
                            <template x-for="i in 4" :key="i">
                                <div class="h-1.5 flex-1 rounded-full bg-gray-200 dark:bg-gray-700 transition-colors"
                                     :style="i <= score ? `background-color: ${level[1]}` : ''"></div>
                            </template>
                        </div>
                        <p class="text-xs mt-1 font-medium" :style="`color: ${level[1]}`" aria-live="polite">
                            <span x-text="level[0]"></span><span x-show="score > 0 && score < 3" class="text-gray-500 font-normal"> — longer, with numbers and symbols, is safer</span>
                        </p>
                    </div>
                </div>

                <div>
                    <x-auth-field id="new-password-confirm" name="password_confirmation" type="password" icon="lock" label="Confirm password"
                                  required minlength="6" autocomplete="new-password" placeholder="Type it again"
                                  x-model="pw2" x-on:keyup="checkCaps($event)" x-on:keydown="checkCaps($event)" />
                    <p class="text-xs mt-1 font-medium" x-show="pw2.length" x-cloak aria-live="polite"
                       :style="`color: ${matches ? '#10b981' : '#f59e0b'}`"
                       x-text="matches ? '✓ Passwords match' : '✗ Passwords don\'t match yet'"></p>
                </div>

                <p class="text-xs font-medium text-amber-700 dark:text-yellow-400" x-show="caps" x-cloak>⚠ Caps Lock is on</p>

                <button type="submit" :disabled="!valid || saving" :class="(!valid || saving) && 'opacity-60 cursor-not-allowed'"
                        class="w-full bg-orange-700 text-white font-semibold py-2.5 rounded-lg text-sm hover:brightness-110 transition">
                    <span x-show="!saving">Save password</span>
                    <span x-show="saving" x-cloak>Saving…</span>
                </button>
            </form>
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
                    <a href="{{ route('orders.download', [$order, $item]) }}" data-turbo="false" class="flex items-center justify-between bg-white rounded-lg px-4 py-3 hover:bg-indigo-50/50 transition">
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

<style>
    /* Real content is always in the DOM and visible-by-default (no-JS safety) — the overlay
       from partials/delivery-loader.blade.php sits on top of it the whole time regardless,
       so there's nothing to actually hide here. This just adds a nice fade+rise the instant
       that overlay clears, via the .dl-revealed toggle the script below adds. */
    #thankyou-card.dl-reveal { opacity: 0; transform: translateY(10px); }
    #thankyou-card.dl-reveal.dl-revealed { opacity: 1; transform: translateY(0); transition: opacity 0.45s ease, transform 0.45s ease; }
    @media (prefers-reduced-motion: reduce) {
        #thankyou-card.dl-reveal { opacity: 1; transform: none; }
    }

    /* Confetti burst — a two-layer trick: the outer piece falls straight down while
       spinning (confetti-fall), and the inner piece independently sways side to side
       (confetti-sway) so the whole thing flutters instead of dropping like a rock. */
    .confetti-piece {
        position: absolute;
        top: -5vh;
        will-change: transform, opacity;
        animation: confetti-fall linear forwards;
    }
    .confetti-piece-inner {
        display: block;
        animation: confetti-sway ease-in-out infinite alternate;
    }
    @keyframes confetti-fall {
        0%   { transform: translateY(0) rotate(0deg); opacity: 1; }
        85%  { opacity: 1; }
        100% { transform: translateY(115vh) rotate(var(--confetti-spin, 540deg)); opacity: 0; }
    }
    @keyframes confetti-sway {
        0%   { transform: translateX(-12px); }
        100% { transform: translateX(12px); }
    }
</style>
<script>
    // Reuses the exact same overlay every Add to Cart / Buy Now / Place Order click already
    // showed on the way here — so the whole journey (cart -> checkout -> this page) reads as
    // one continuous, branded "your order is on its way" moment instead of three unrelated
    // loading states. The package icon morphs into a confirmation check, then clears to
    // reveal the real card underneath (which was fully rendered the entire time).
    // A confetti burst timed to the exact moment the card is revealed, so the page feels
    // like it's celebrating with the customer rather than just finishing a load. Built with
    // plain DOM+CSS (no library) so it costs nothing extra to ship; skipped entirely for
    // anyone who has asked their OS/browser for reduced motion.
    function launchConfetti() {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const container = document.getElementById('confetti-container');
        if (!container) return;

        const colors = [@json(setting('primary_color', '#ea580c')), @json(setting('secondary_color', '#ec4899')), @json(hex_shade(setting('primary_color', '#ea580c'), 0.35)), '#22c55e', '#eab308', '#06b6d4']; // brand first
        const total = window.innerWidth < 640 ? 60 : 110;

        for (let i = 0; i < total; i++) {
            const piece = document.createElement('div');
            piece.className = 'confetti-piece';
            const fallDuration = 2.6 + Math.random() * 2;
            const spin = (Math.random() < 0.5 ? -1 : 1) * (360 + Math.random() * 360);
            piece.style.left = (Math.random() * 100) + '%';
            piece.style.animationDuration = fallDuration + 's';
            piece.style.animationDelay = (Math.random() * 0.6) + 's';
            piece.style.setProperty('--confetti-spin', spin + 'deg');

            const inner = document.createElement('span');
            inner.className = 'confetti-piece-inner';
            const size = 6 + Math.random() * 7;
            const isCircle = Math.random() < 0.4;
            inner.style.width = size + 'px';
            inner.style.height = (isCircle ? size : size * 2.2) + 'px';
            inner.style.background = colors[Math.floor(Math.random() * colors.length)];
            inner.style.borderRadius = isCircle ? '50%' : '2px';
            inner.style.animationDuration = (0.5 + Math.random() * 0.5) + 's';

            piece.appendChild(inner);
            piece.addEventListener('animationend', function () { piece.remove(); });
            container.appendChild(piece);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        showDeliveryLoader('Confirming your order…');
        const overlay = document.getElementById('delivery-loader-overlay');
        const card = document.getElementById('thankyou-card');
        if (!overlay) { if (card) card.classList.add('dl-revealed'); launchConfetti(); return; }

        const packageIcon = overlay.querySelector('[data-loader-icon="package"]');
        const successIcon = overlay.querySelector('[data-loader-icon="success"]');
        const bar = overlay.querySelector('[data-loader-bar]');
        const message = overlay.querySelector('[data-loader-message]');

        setTimeout(function () {
            if (packageIcon) packageIcon.hidden = true;
            if (successIcon) successIcon.hidden = false;
            if (bar) bar.style.width = '100%';
            if (message) message.textContent = 'Order confirmed!';
        }, 650);

        setTimeout(function () {
            hideDeliveryLoader();
            if (card) card.classList.add('dl-revealed');
            launchConfetti();
            // Reset for the next time this same overlay is used elsewhere on the site.
            if (packageIcon) packageIcon.hidden = false;
            if (successIcon) successIcon.hidden = true;
            if (bar) bar.style.width = '';
        }, 1150);
    });
</script>

@if($shouldTrackPurchase ?? false)
@php
    $currencyCode = setting('currency_code', 'BDT');
    // Product ID (not SKU) — the same item_id/content_id as view_item, add_to_cart, the
    // Merchant feed and Meta's catalog; mixing in SKUs here split each product in two.
    $trackedItems = $order->items->map(fn ($item) => [
        'id'       => (string) $item->product_id,
        'name'     => $item->product_name,
        'price'    => (float) $item->price,
        'quantity' => $item->quantity,
    ])->values();
    $order->items->pluck('product')->filter()->each->loadMissing('brand', 'category');
    $gaItems = $order->items->map(fn ($item) => tracking_item($item->product, [
        'item_id' => (string) $item->product_id, // still set when the product was since deleted
        'item_name' => $item->product_name,
        'price' => (float) $item->price,
        'quantity' => (int) $item->quantity,
        'item_variant' => $item->variant_label,
    ]))->values();

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

    // GA4 + GTM via the layout's shared tracker. value = revenue incl. shipping, as GA4
    // expects; shipping/tax/coupon broken out so reports can separate them.
    window.mvTrack('purchase', {
        transaction_id: orderId,
        value: value,
        shipping: {{ (float) $order->shipping }},
        tax: {{ (float) $order->tax }},
        @if($order->coupon_code)coupon: @json($order->coupon_code),@endif
        @if((float) $order->discount > 0)discount: {{ (float) $order->discount }},@endif
        payment_type: @json($order->payment_method),
        items: @json($gaItems),
    });
    if (typeof gtag === 'function') {
        @if($adsConversionId && $adsPurchaseLabel)
        gtag('event', 'conversion', {
            send_to: {!! Js::from($adsConversionId . '/' . $adsPurchaseLabel) !!},
            value: value,
            currency: currency,
            transaction_id: orderId,
        });
        @endif
    }


    @if($pixelOn)
    if (typeof fbq === 'function') {
        @if($fbAdvancedMatchingOn && $orderTrackingData['fb'])
        // Refresh Advanced Matching with this order's shipping details right
        // before the highest-value event fires — see the @php block above.
        fbq('init', {!! Js::from($pixelId) !!}, {!! Js::from($orderTrackingData['fb']) !!});
        @endif
        // eventID matches the server-side Conversions API call CheckoutController@success
        // already fired for this order (guarded there the same way, by $shouldTrackPurchase)
        // so Meta merges the pair into one event instead of double-counting this sale.
        fbq('track', 'Purchase', {
            value: value,
            currency: currency,
            content_type: 'product',
            content_ids: items.map(function (i) { return i.id; })
        }, { eventID: {!! Js::from($fbPurchaseEventId) !!} });
    }
    @endif
})();
</script>
@endif
@endsection
