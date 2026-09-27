@extends('layouts.landing')

@section('content')
@php
    $currencySymbol = html_entity_decode(setting('currency_symbol', '৳'));
    $decimals = (int) setting('decimal_places', 0);
    $primary = $landingPage->brand_color ?: setting('primary_color', '#ea580c');
    $primaryDark = hex_shade($primary, -0.22);
    $currencyCode = setting('currency_code', 'BDT');
    $trackProductId = $landingPage->product_id ? ($landingPage->product?->sku ?: (string) $landingPage->product_id) : $landingPage->slug;
    // Computed once, shared by the hero badge and the offer box below (was duplicated/undefined-if-no-hero-image before).
    $discountPct = ($landingPage->compare_at_price && $landingPage->effective_price && $landingPage->compare_at_price > $landingPage->effective_price)
        ? round((($landingPage->compare_at_price - $landingPage->effective_price) / $landingPage->compare_at_price) * 100)
        : null;
    $stickyThumb = $landingPage->hero_image ?: $landingPage->product?->image;
@endphp
<style>
    .no-scrollbar::-webkit-scrollbar{display:none}
    .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}
    [x-cloak]{display:none!important}
    html{scroll-behavior:smooth}

    @keyframes lp-float{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
    .lp-float{animation:lp-float 3.5s ease-in-out infinite}
    @keyframes lp-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.07)}}
    .lp-pulse{animation:lp-pulse 1.7s ease-in-out infinite}

    /* Slow-drifting color blobs behind the hero — pure CSS, works for any brand color with no extra admin setup. */
    @keyframes lp-mesh{0%,100%{transform:translate(0,0) scale(1)}50%{transform:translate(4%,-5%) scale(1.1)}}
    .lp-mesh{position:absolute;border-radius:9999px;filter:blur(28px);opacity:.4;animation:lp-mesh 9s ease-in-out infinite;pointer-events:none}

    /* One-shot entrance for above-the-fold content — no scroll observer needed. */
    @keyframes lp-fade-up{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
    .lp-fade-up{animation:lp-fade-up .7s cubic-bezier(.22,1,.36,1) both}

    /* Scroll-reveal for everything below the fold — same vocabulary as layouts/app.blade.php's
       site-wide system (this page doesn't extend that layout, so it's re-declared here); JS is
       shared (resources/js/scroll-reveal.js, already loaded via app.js on every page). */
    .reveal{opacity:0;transform:translateY(20px);transition:opacity .6s cubic-bezier(.22,1,.36,1),transform .6s cubic-bezier(.22,1,.36,1)}
    .reveal.is-visible{opacity:1;transform:translateY(0)}
    .reveal-group>*{opacity:0;transform:translateY(16px);transition:opacity .5s cubic-bezier(.22,1,.36,1),transform .5s cubic-bezier(.22,1,.36,1)}
    .reveal-group.is-visible>*{opacity:1;transform:translateY(0)}
    @for ($i = 1; $i <= 10; $i++)
    .reveal-group.is-visible>*:nth-child({{ $i }}){transition-delay:{{ $i * 0.05 }}s}
    @endfor

    /* Premium gradient CTA: soft breathing glow + a light sweeping shine, built from just the
       one brand color the admin picks (hex_shade() darkens it for the second gradient stop). */
    .lp-cta{position:relative;overflow:hidden;isolation:isolate;background:linear-gradient(135deg,{{ $primary }},{{ $primaryDark }});animation:lp-glow 2.4s ease-in-out infinite}
    .lp-cta::after{content:'';position:absolute;top:0;bottom:0;left:-75%;width:50%;right:auto;background:linear-gradient(120deg,transparent,rgba(255,255,255,.4),transparent);transform:skewX(-20deg);animation:lp-shine 3.4s ease-in-out infinite}
    @keyframes lp-shine{0%,25%{left:-75%}70%,100%{left:130%}}
    @keyframes lp-glow{0%,100%{box-shadow:0 4px 18px -2px {{ $primary }}66}50%{box-shadow:0 4px 26px 1px {{ $primary }}99}}

    @keyframes lp-badge-pulse{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}
    .lp-badge-pulse{animation:lp-badge-pulse 1.8s ease-in-out infinite}

    /* Gradient price text, guarded behind @supports so browsers without background-clip:text
       just get the plain brand color instead of invisible (transparent-on-white) text. */
    .lp-price-gradient{color:{{ $primary }}}
    @supports (background-clip:text) or (-webkit-background-clip:text) {
        .lp-price-gradient{background:linear-gradient(135deg,{{ $primary }},{{ $primaryDark }});-webkit-background-clip:text;background-clip:text;color:transparent;-webkit-text-fill-color:transparent}
    }

    @media (prefers-reduced-motion: reduce){
        .lp-float,.lp-pulse,.lp-mesh,.lp-fade-up,.lp-cta,.lp-cta::after,.lp-badge-pulse{animation:none!important}
        .reveal,.reveal-group>*{opacity:1!important;transform:none!important;transition:none!important}
    }
</style>

@if(session('order_success'))
    {{-- Thank You state — same URL as the landing page itself (redirected back here after a
         successful order), rather than a separate route, so there's only ever one link to
         share/remember for this campaign. --}}
    <div class="relative text-center py-14 px-6 overflow-hidden">
        {{-- Confetti is injected here on load — see the script below. --}}
        <div id="ty-confetti" class="pointer-events-none fixed inset-0 overflow-hidden z-[60]" aria-hidden="true"></div>
        <div class="lp-mesh w-40 h-40 -top-10 -left-10" style="background: {{ $primary }};"></div>
        <div class="lp-mesh w-32 h-32 top-16 -right-10" style="background: {{ $primary }}; animation-delay: -4s;"></div>

        <div class="relative ty-reveal">
            <div class="relative w-20 h-20 mx-auto mb-6">
                <span class="absolute inset-0 rounded-full lp-pulse" style="background: #22c55e22;"></span>
                <svg class="relative w-20 h-20" viewBox="0 0 80 80" fill="none">
                    <circle cx="40" cy="40" r="36" stroke="#22c55e" stroke-width="4" fill="#f0fdf4" class="ty-circle"/>
                    <path d="M25 41l10 10 20-22" stroke="#22c55e" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" fill="none" class="ty-check"/>
                </svg>
            </div>

            <h1 class="text-2xl font-extrabold text-gray-900 mb-3">{{ $landingPage->thank_you_heading }}</h1>
            @if($landingPage->thank_you_message)
                <p class="text-gray-500 mb-5 leading-relaxed">{{ $landingPage->thank_you_message }}</p>
            @endif

            <div class="inline-flex items-center gap-2 bg-gray-50 border border-gray-100 rounded-full pl-4 pr-1.5 py-1.5 mb-8">
                <span class="text-xs text-gray-400">Order</span>
                <span id="ty-order-no" class="text-sm font-mono font-bold text-gray-700">#{{ session('order_success') }}</span>
                <button type="button" onclick="lpCopyOrderNo(this)" class="w-7 h-7 rounded-full flex items-center justify-center hover:bg-gray-200 active:scale-90 transition" title="কপি করুন" aria-label="Order নম্বর কপি করুন">
                    <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </div>

            {{-- A simple visual read of where things stand — matches the confirmation-call
                 message above, not a live tracker (this store has no live courier status feed). --}}
            <div class="flex items-start justify-center mb-8 px-1">
                @php $tySteps = ['অর্ডার সম্পন্ন', 'নিশ্চিতকরণ কল', 'ডেলিভারি']; @endphp
                @foreach($tySteps as $i => $label)
                    <div class="flex items-center {{ $i < count($tySteps) - 1 ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center gap-1.5 shrink-0 w-16">
                            <span class="relative w-7 h-7 shrink-0">
                                @if($i === 0)
                                    <span class="ty-step-ring absolute inset-0 rounded-full" style="background: #22c55e66;"></span>
                                @elseif($i === 1)
                                    <span class="ty-step-ring absolute inset-0 rounded-full" style="background: {{ $primary }}4d; animation-delay: .5s;"></span>
                                @endif
                                <span class="ty-step-badge absolute inset-0 rounded-full flex items-center justify-center text-xs font-bold shadow-sm"
                                      style="animation-delay: {{ $i * 0.18 }}s; {{ $i === 0 ? 'background:#22c55e;color:#fff' : 'background:' . $primary . '26;color:' . $primaryDark }}">
                                    @if($i === 0)
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                    @else
                                        {{ $i + 1 }}
                                    @endif
                                </span>
                            </span>
                            <span class="text-[10px] text-gray-400 font-medium leading-tight">{{ $label }}</span>
                        </div>
                        @if($i < count($tySteps) - 1)
                            <span class="relative flex-1 h-0.5 rounded-full -mt-4 bg-gray-200 overflow-visible">
                                @if($i === 0)
                                    <span class="ty-line-fill absolute inset-0 rounded-full" style="background: linear-gradient(90deg, #22c55e, {{ $primary }}66); animation-delay: .55s;"></span>
                                    <span class="ty-line-dot" style="animation-delay: 1.5s;"></span>
                                @endif
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>

            <a href="{{ route('shop.index') }}" class="ty-outline-btn group inline-flex w-full items-center justify-center gap-2 border-2 font-bold py-3.5 rounded-xl text-base active:scale-[0.98]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                {{ $landingPage->thank_you_button_text ?: 'আরও প্রোডাক্ট দেখুন' }}
            </a>
        </div>
    </div>
    <style>
        @keyframes ty-pop{0%{opacity:0;transform:translateY(16px) scale(.96)}60%{opacity:1;transform:translateY(-4px) scale(1.02)}100%{opacity:1;transform:translateY(0) scale(1)}}
        .ty-reveal{animation:ty-pop .7s cubic-bezier(.22,1,.36,1) both}
        .ty-circle{stroke-dasharray:227;stroke-dashoffset:227;animation:ty-circle-draw .6s ease-out forwards}
        .ty-check{stroke-dasharray:46;stroke-dashoffset:46;animation:ty-check-draw .35s ease-out .55s forwards}
        @keyframes ty-circle-draw{to{stroke-dashoffset:0}}
        @keyframes ty-check-draw{to{stroke-dashoffset:0}}
        .ty-outline-btn{border-color:{{ $primary }};color:{{ $primary }};transition:background-color .3s ease,color .3s ease,transform .15s ease}
        .ty-outline-btn:hover{background-color:{{ $primary }};color:#fff}
        .ty-outline-btn svg{transition:transform .3s ease}
        .ty-outline-btn:hover svg{transform:translateX(3px)}

        /* Order-status steps: each badge pops in with a small overshoot, the completed step
           gets a one-shot success ring and the "next up" step gets its own softer ring so the
           eye reads it as "coming next" rather than equally-distant as the last step. The first
           connector fills left-to-right (green fading into the brand color) with a small dot
           that keeps drifting along it, reading as "moving toward the next step" motion. */
        @keyframes ty-step-pop{0%{opacity:0;transform:scale(.4)}60%{opacity:1;transform:scale(1.18)}100%{opacity:1;transform:scale(1)}}
        .ty-step-badge{animation:ty-step-pop .55s cubic-bezier(.34,1.56,.64,1) both}
        @keyframes ty-ring-ping{0%{transform:scale(1);opacity:.65}100%{transform:scale(2.3);opacity:0}}
        .ty-step-ring{animation:ty-ring-ping 1.7s cubic-bezier(.22,.61,.36,1) infinite}
        @keyframes ty-line-grow{from{transform:scaleX(0)}to{transform:scaleX(1)}}
        .ty-line-fill{transform-origin:left;animation:ty-line-grow 1s cubic-bezier(.22,.61,.36,1) both}
        @keyframes ty-dot-travel{0%{left:0;opacity:0}12%{opacity:1}88%{opacity:1}100%{left:100%;opacity:0}}
        .ty-line-dot{position:absolute;top:50%;width:7px;height:7px;margin-top:-3.5px;margin-left:-3.5px;border-radius:9999px;background:#22c55e;box-shadow:0 0 6px 1px #22c55e99;animation:ty-dot-travel 2.4s ease-in-out infinite}

        @media (prefers-reduced-motion: reduce){
            .ty-reveal{animation:none!important;opacity:1!important;transform:none!important}
            .ty-circle,.ty-check{animation:none!important;stroke-dashoffset:0!important}
            .ty-step-badge{animation:none!important;opacity:1!important;transform:none!important}
            .ty-step-ring{display:none!important}
            .ty-line-fill{animation:none!important;transform:none!important}
            .ty-line-dot{display:none!important}
        }
    </style>
    <script>
        // Confetti burst — same self-contained technique used on the main checkout's thank-you
        // page (checkout/success.blade.php), reimplemented here since this page doesn't share
        // that layout. Two-layer trick: the outer piece falls while spinning, the inner piece
        // independently sways side to side, so it flutters instead of dropping like a rock.
        // Skipped entirely for anyone who has asked their OS/browser for reduced motion.
        function lpLaunchConfetti() {
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            var container = document.getElementById('ty-confetti');
            if (!container) return;
            var colors = ['#f97316', '#ec4899', '#6366f1', '#22c55e', '#eab308', '#06b6d4'];
            var total = window.innerWidth < 640 ? 50 : 90;
            for (var i = 0; i < total; i++) {
                (function () {
                    var piece = document.createElement('div');
                    piece.style.position = 'absolute';
                    piece.style.top = '-5vh';
                    piece.style.willChange = 'transform, opacity';
                    piece.style.left = (Math.random() * 100) + '%';
                    var fallDuration = 2.6 + Math.random() * 2;
                    var spin = (Math.random() < 0.5 ? -1 : 1) * (360 + Math.random() * 360);
                    piece.style.animation = 'ty-confetti-fall ' + fallDuration + 's linear ' + (Math.random() * 0.5) + 's forwards';
                    piece.style.setProperty('--ty-spin', spin + 'deg');

                    var inner = document.createElement('span');
                    var size = 6 + Math.random() * 7;
                    var isCircle = Math.random() < 0.4;
                    inner.style.display = 'block';
                    inner.style.width = size + 'px';
                    inner.style.height = (isCircle ? size : size * 2.2) + 'px';
                    inner.style.background = colors[Math.floor(Math.random() * colors.length)];
                    inner.style.borderRadius = isCircle ? '50%' : '2px';
                    inner.style.animation = 'ty-confetti-sway ' + (0.5 + Math.random() * 0.5) + 's ease-in-out infinite alternate';

                    piece.appendChild(inner);
                    piece.addEventListener('animationend', function () { piece.remove(); });
                    container.appendChild(piece);
                })();
            }
        }
        var lpConfettiStyle = document.createElement('style');
        lpConfettiStyle.textContent = '@keyframes ty-confetti-fall{0%{transform:translateY(0) rotate(0)}85%{opacity:1}100%{transform:translateY(115vh) rotate(var(--ty-spin,540deg));opacity:0}}@keyframes ty-confetti-sway{0%{transform:translateX(-12px)}100%{transform:translateX(12px)}}';
        document.head.appendChild(lpConfettiStyle);

        function lpCopyOrderNo(btn) {
            var text = document.getElementById('ty-order-no').textContent.trim();
            var done = function () {
                var original = btn.innerHTML;
                btn.innerHTML = '<svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                setTimeout(function () { btn.innerHTML = original; }, 1200);
            };
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(done).catch(function () {});
            } else {
                var ta = document.createElement('textarea');
                ta.value = text; ta.style.position = 'fixed'; ta.style.opacity = '0';
                document.body.appendChild(ta); ta.select();
                try { document.execCommand('copy'); } catch (e) {}
                document.body.removeChild(ta); done();
            }
        }

        document.addEventListener('DOMContentLoaded', function () { setTimeout(lpLaunchConfetti, 550); });
    </script>

    @php
        $adsConversionId = $landingPage->google_ads_conversion_id ?: setting('google_ads_conversion_id', '');
        $adsPurchaseLabel = $landingPage->google_ads_conversion_label ?: setting('google_ads_purchase_label', '');
        $googleEnhancedOn = setting('google_enhanced_conversions_enabled', '0') == '1';
        $fbAdvancedMatchingOn = setting('facebook_advanced_matching_enabled', '0') == '1';
    @endphp
    <script>
    {{-- Fires exactly once: order_success_* only exists in session for this one flashed
         request (see LandingPageController@order), so a page refresh drops back to the
         plain landing page state and this block simply never runs again for that order. --}}
    (function () {
        var orderId = @json(session('order_success'));
        var value = @json((float) session('order_success_value', 0));
        var currency = @json($currencyCode);
        var fbData = @json(session('order_success_fb', []));
        var googleData = @json(session('order_success_google', []));

        @if($googleEnhancedOn)
        if (typeof gtag === 'function' && Object.keys(googleData).length) {
            gtag('set', 'user_data', googleData);
        }
        @endif

        if (typeof gtag === 'function') {
            gtag('event', 'purchase', {
                transaction_id: orderId, value: value, currency: currency,
                items: [{ item_id: {{ Js::from($trackProductId) }}, item_name: {{ Js::from($landingPage->product?->name ?: $landingPage->title) }}, price: value }],
            });
            @if($adsConversionId && $adsPurchaseLabel)
            gtag('event', 'conversion', {
                send_to: {!! Js::from($adsConversionId . '/' . $adsPurchaseLabel) !!},
                value: value, currency: currency, transaction_id: orderId,
            });
            @endif
        }

        if (typeof fbq === 'function') {
            @if($fbAdvancedMatchingOn)
            if (Object.keys(fbData).length) {
                fbq('init', {{ Js::from($landingPage->fb_pixel_id ?: setting('facebook_pixel_id', '')) }}, fbData);
            }
            @endif
            // eventID matches the server-side Conversions API call LandingPageController@order
            // already fired for this order, so Meta merges the pair into one event.
            fbq('track', 'Purchase', {
                value: value, currency: currency,
                content_type: 'product', content_ids: [{{ Js::from($trackProductId) }}],
            }, { eventID: @json(session('order_success_fb_eid')) });
        }
    })();
    </script>
@else

    {{-- Hero --}}
    <div class="relative text-center px-4 pt-6 pb-5 overflow-hidden lp-fade-up" style="background: linear-gradient(180deg, {{ $primary }}14, transparent);">
        <div class="lp-mesh w-40 h-40 -top-10 -left-10" style="background: {{ $primary }};"></div>
        <div class="lp-mesh w-32 h-32 top-10 -right-8" style="background: {{ $primary }}; animation-delay: -4s;"></div>
        <div class="relative">
        @if($landingPage->hero_image)
            {{-- object-contain (not cover) so any uploaded photo — wide banner, square product
                 shot, tall bottle on a white background, whatever the admin uploads — shows in
                 full without an ugly crop. The soft color glow behind + drop-shadow on the image
                 itself give it depth so plain white-background product photos don't look like a
                 flat catalog thumbnail sitting on the page. --}}
            <div class="relative mb-4 px-4">
                <div class="absolute inset-x-10 inset-y-3 rounded-[2rem]" style="background: radial-gradient(ellipse at center, {{ $primary }}26, transparent 72%);"></div>
                {{-- Soft "ground shadow" ellipse beneath the product — makes it feel like it's
                     actually sitting/photographed somewhere rather than pasted flat on a page. --}}
                <div class="absolute left-1/2 bottom-1 -translate-x-1/2 w-2/3 h-4 rounded-full blur-md" style="background: {{ $primary }}30;"></div>
                <img src="{{ Storage::url($landingPage->hero_image) }}" alt="{{ $landingPage->title }}" class="relative w-full max-h-72 object-contain drop-shadow-2xl lp-float">

                @if($discountPct)
                    <span class="lp-pulse absolute top-1 left-3 inline-flex items-center gap-1 text-white text-xs font-extrabold px-3 py-1.5 rounded-full shadow-lg" style="background-color: {{ $primary }};">
                        🔥 -{{ $discountPct }}%
                    </span>
                @endif
            </div>
        @endif
        @if($landingPage->hero_heading || $landingPage->hero_subheading)
            {{-- A left-accent "callout" card rather than plain centered text — reads well
                 whether the admin typed one punchy line or, as often happens, a whole
                 paragraph pitch here: centered long paragraphs get a ragged, messy edge on
                 both sides and no visual separation from the page background, which is what
                 was making this look like an unstyled wall of text. Left-aligned inside a
                 bordered card gives it proper typographic hierarchy and a designed feel
                 regardless of how much text goes in. --}}
            <div class="text-left bg-white/90 backdrop-blur rounded-r-2xl rounded-l-lg shadow-md border border-gray-100 border-l-4 p-4 mb-4" style="border-left-color: {{ $primary }};">
                @if($landingPage->hero_heading)
                    <h1 class="text-lg font-bold text-gray-900 leading-relaxed">{{ $landingPage->hero_heading }}</h1>
                @endif
                @if($landingPage->hero_subheading)
                    <p class="text-sm text-gray-500 leading-relaxed {{ $landingPage->hero_heading ? 'mt-2' : '' }}">{{ $landingPage->hero_subheading }}</p>
                @endif
            </div>
        @endif
        @if($landingPage->rating_value)
        <div class="flex items-center justify-center gap-1.5 mb-4 text-sm">
            <span class="text-amber-400 tracking-tighter">{{ str_repeat('★', (int) round($landingPage->rating_value)) }}{{ str_repeat('☆', 5 - (int) round($landingPage->rating_value)) }}</span>
            <span class="font-semibold text-gray-700">{{ $landingPage->rating_value }}/৫</span>
            @if($landingPage->rating_count)<span class="text-gray-400">({{ number_format($landingPage->rating_count) }} রিভিউ)</span>@endif
        </div>
        @endif
        <a href="#order-form" id="hero-cta" class="lp-cta block w-full text-center text-white font-extrabold py-3.5 rounded-xl text-base shadow-lg active:scale-[0.99] transition-transform">
            {{ $landingPage->order_button_text }}
        </a>
        </div>
    </div>

    {{-- Trust badges strip --}}
    @if(filled($landingPage->trust_badges))
    <div class="bg-gradient-to-b from-green-50 to-green-50/40 border-y border-green-100 px-3 py-4">
        <div class="grid gap-2.5 reveal-group" style="grid-template-columns: repeat({{ min(3, count($landingPage->trust_badges)) }}, minmax(0,1fr));">
            @foreach(array_slice($landingPage->trust_badges, 0, 6) as $badge)
            <div class="flex flex-col items-center text-center gap-1.5 bg-white/70 rounded-xl py-2.5 px-1.5 shadow-sm border border-white hover:shadow-md transition-shadow duration-300">
                @if(!empty($badge['image']))
                    <img src="{{ Storage::url($badge['image']) }}" alt="" class="w-6 h-6 object-contain">
                @else
                    <span class="text-xl leading-none">{{ $badge['icon'] ?: '✅' }}</span>
                @endif
                <span class="text-[11px] text-green-800 font-medium leading-tight">{{ $badge['text'] }}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Admin's free-form content — sits on a soft brand-tinted card rather than bare on the
         page background, so a long, heavily-bolded pitch (the common case here) reads as a
         designed "info panel" instead of a plain wall of text floating on white. --}}
    @if($landingPage->content)
        <div class="px-4 py-6 reveal">
            <div class="prose prose-sm max-w-none leading-relaxed prose-headings:font-extrabold prose-a:text-orange-600 prose-strong:text-gray-900 rounded-2xl border border-gray-100 shadow-md p-5"
                 style="background: linear-gradient(160deg, {{ $primary }}0d, {{ $primary }}03);">
                {!! $landingPage->content !!}
            </div>
        </div>
    @endif

    {{-- How it works --}}
    @if($landingPage->how_it_works_video)
    <div class="px-4 py-6 border-t border-gray-100 reveal">
        <h2 class="text-center font-extrabold text-gray-900 mb-4">{{ $landingPage->how_it_works_heading ?: 'এটি কিভাবে কাজ করে?' }}</h2>
        @php $embed = embed_video_url($landingPage->how_it_works_video); @endphp
        <div class="rounded-2xl overflow-hidden shadow-xl ring-1 ring-black/5 bg-black aspect-video">
            @if($embed && $embed['type'] === 'iframe')
                <iframe src="{{ $embed['src'] }}" class="w-full h-full" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            @elseif($embed && $embed['type'] === 'video')
                <video src="{{ $embed['src'] }}" class="w-full h-full" controls preload="none"></video>
            @else
                <a href="{{ $landingPage->how_it_works_video }}" target="_blank" rel="noopener" class="flex items-center justify-center w-full h-full text-white gap-2">
                    <svg class="w-10 h-10" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4l10 6-10 6V4z"/></svg>
                </a>
            @endif
        </div>
    </div>
    @endif

    {{-- Benefits grid --}}
    @if(filled($landingPage->benefits))
    <div class="px-4 py-6 border-t border-gray-100 reveal">
        <h2 class="text-center font-extrabold text-gray-900 mb-4">{{ $landingPage->benefits_heading ?: 'উপকারিতা' }}</h2>
        <div class="grid grid-cols-2 gap-3 reveal-group">
            @foreach($landingPage->benefits as $b)
            <div class="border border-gray-100 rounded-2xl p-3.5 shadow-sm text-center bg-white hover:shadow-md hover:-translate-y-0.5 active:scale-[0.98] transition-all duration-300">
                @if(!empty($b['image']))
                    <div class="w-11 h-11 mx-auto mb-2 rounded-xl overflow-hidden bg-gray-50 flex items-center justify-center">
                        <img src="{{ Storage::url($b['image']) }}" alt="" class="w-8 h-8 object-contain">
                    </div>
                @else
                    <div class="w-11 h-11 mx-auto mb-2 rounded-full flex items-center justify-center text-xl text-white shadow-sm" style="background: linear-gradient(135deg, {{ $primary }}, {{ $primaryDark }});">
                        {{ $b['icon'] ?: '✨' }}
                    </div>
                @endif
                <p class="font-semibold text-gray-800 text-xs mb-0.5">{{ $b['title'] }}</p>
                @if(!empty($b['description']))<p class="text-[11px] text-gray-500 leading-snug">{{ $b['description'] }}</p>@endif
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Who is this for --}}
    @if(filled($landingPage->who_for))
    <div class="px-4 py-6 border-t border-gray-100 reveal">
        <h2 class="text-center font-extrabold text-gray-900 mb-4">{{ $landingPage->who_for_heading ?: 'কাদের জন্য এটি' }}</h2>
        <div class="space-y-2 reveal-group">
            @foreach($landingPage->who_for as $w)
            <div class="flex items-center gap-3 border border-gray-100 rounded-xl p-3.5 bg-gray-50 hover:bg-white hover:shadow-md transition-all duration-300">
                @if(!empty($w['image']))
                    <img src="{{ Storage::url($w['image']) }}" alt="" class="w-9 h-9 object-contain shrink-0 rounded-lg">
                @else
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-lg shrink-0 text-white shadow-sm" style="background: linear-gradient(135deg, {{ $primary }}, {{ $primaryDark }});">
                        {{ $w['icon'] ?: '👤' }}
                    </div>
                @endif
                <span class="text-base text-gray-700 min-w-0 flex-1 leading-snug">{!! bold_markup($w['text']) !!}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Testimonials --}}
    @if(filled($landingPage->testimonial_videos) || filled($landingPage->testimonial_images))
    <div class="px-4 py-6 border-t border-gray-100 reveal">
        <h2 class="text-center font-extrabold text-gray-900 mb-1">{{ $landingPage->testimonials_heading ?: 'গ্রাহকরা কি বলছেন?' }}</h2>
        @if($landingPage->rating_value)
        <p class="text-center text-xs text-gray-400 mb-4">
            <span class="text-amber-400">{{ str_repeat('★', (int) round($landingPage->rating_value)) }}</span>
            {{ $landingPage->rating_value }}/৫ @if($landingPage->rating_count)({{ number_format($landingPage->rating_count) }} রিভিউ)@endif
        </p>
        @else
        <div class="mb-4"></div>
        @endif

        @if(filled($landingPage->testimonial_videos))
        <div x-data="{ track: null, active: 0 }" class="relative mb-4">
            <div x-ref="track" @scroll.debounce.100ms="active = $el.children[0] ? Math.round($el.scrollLeft / $el.children[0].offsetWidth) : 0" class="flex gap-3 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-1">
                @foreach($landingPage->testimonial_videos as $v)
                @php $embed = embed_video_url($v['video_url']); @endphp
                <div class="snap-center shrink-0 w-[68%]">
                    <div class="rounded-xl overflow-hidden shadow-lg ring-1 ring-black/5 bg-black aspect-[9/16]">
                        @if($embed && $embed['type'] === 'iframe')
                            <iframe src="{{ $embed['src'] }}" class="w-full h-full" loading="lazy" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                        @elseif($embed && $embed['type'] === 'video')
                            <video src="{{ $embed['src'] }}" class="w-full h-full object-cover" controls preload="none"></video>
                        @else
                            <a href="{{ $v['video_url'] }}" target="_blank" rel="noopener" class="flex items-center justify-center w-full h-full text-white">
                                <svg class="w-9 h-9" fill="currentColor" viewBox="0 0 20 20"><path d="M6 4l10 6-10 6V4z"/></svg>
                            </a>
                        @endif
                    </div>
                    @if(!empty($v['name']))<p class="text-center text-xs text-gray-500 mt-1.5">{{ $v['name'] }}</p>@endif
                </div>
                @endforeach
            </div>
            @if(count($landingPage->testimonial_videos) > 1)
            <div class="flex justify-center gap-1.5 mt-2">
                @foreach($landingPage->testimonial_videos as $i => $v)
                <span class="h-1.5 rounded-full transition-all duration-300" :class="active === {{ $i }} ? 'w-4' : 'w-1.5 bg-gray-300'" :style="active === {{ $i }} ? 'background-color: {{ $primary }}' : ''"></span>
                @endforeach
            </div>
            @endif
        </div>
        @endif

        @if(filled($landingPage->testimonial_images))
        <div x-data="{ active: 0 }" class="relative">
            <div @scroll.debounce.100ms="active = $el.children[0] ? Math.round($el.scrollLeft / $el.children[0].offsetWidth) : 0" class="flex gap-3 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-1">
                @foreach($landingPage->testimonial_images as $img)
                <div class="snap-center shrink-0 w-[55%]">
                    <img src="{{ Storage::url($img) }}" class="w-full rounded-xl shadow-lg ring-1 ring-black/5 object-cover">
                </div>
                @endforeach
            </div>
            @if(count($landingPage->testimonial_images) > 1)
            <div class="flex justify-center gap-1.5 mt-2">
                @foreach($landingPage->testimonial_images as $i => $img)
                <span class="h-1.5 rounded-full transition-all duration-300" :class="active === {{ $i }} ? 'w-4' : 'w-1.5 bg-gray-300'" :style="active === {{ $i }} ? 'background-color: {{ $primary }}' : ''"></span>
                @endforeach
            </div>
            @endif
        </div>
        @endif
    </div>
    @endif

    {{-- Special offer / pricing box --}}
    <div id="offer" class="px-4 py-6 border-t border-gray-100 reveal">
        <div class="relative rounded-[1.75rem] p-[2px] shadow-[0_10px_32px_-10px_rgba(0,0,0,0.25)]" style="background: linear-gradient(135deg, {{ $primary }}, {{ $primaryDark }});">
            <div class="rounded-[1.6rem] bg-white p-5">
            @if($landingPage->offer_badge_text)
            <div class="text-center -mt-9 mb-3">
                <span class="lp-badge-pulse inline-block text-white text-xs font-bold px-4 py-1.5 rounded-full shadow-lg" style="background: linear-gradient(135deg, {{ $primary }}, {{ $primaryDark }});">{{ $landingPage->offer_badge_text }}</span>
            </div>
            @endif

            @if($landingPage->product?->image)
                {{-- Fixed-size rounded "card" frame with a soft brand-tinted backdrop; the photo
                     itself uses object-contain so it never gets cropped, whatever its shape. --}}
                <div class="w-44 h-44 mx-auto mb-3 rounded-2xl flex items-center justify-center p-4" style="background: linear-gradient(145deg, {{ $primary }}14, {{ $primary }}05);">
                    <img src="{{ Storage::url($landingPage->product->image) }}" alt="{{ $landingPage->title }}" class="max-w-full max-h-full object-contain drop-shadow-md">
                </div>
            @endif

            @if(filled($landingPage->pricing_items))
            <div class="space-y-1.5 mb-3">
                @foreach($landingPage->pricing_items as $item)
                <div class="flex justify-between gap-3 text-sm text-gray-600">
                    <span class="min-w-0 flex-1">{{ $item['label'] }}</span>
                    <span class="font-medium text-gray-800 shrink-0">{{ $item['price'] }}</span>
                </div>
                @endforeach
            </div>
            <div class="border-t border-dashed border-gray-200 my-3"></div>
            @endif

            <div class="text-center">
                @if($discountPct)
                    <span class="inline-block text-[11px] font-bold text-white px-2.5 py-0.5 rounded-full mb-1.5" style="background-color: {{ $primaryDark }};">-{{ $discountPct }}% ছাড়</span>
                @endif
                @if($landingPage->compare_at_price && $landingPage->effective_price && $landingPage->compare_at_price > $landingPage->effective_price)
                    <p class="text-sm text-gray-400 line-through">{{ format_currency((float) $landingPage->compare_at_price) }}</p>
                @endif
                @if($landingPage->effective_price)
                    <p class="lp-price-gradient text-3xl font-extrabold">{{ format_currency($landingPage->effective_price) }}</p>
                @endif
            </div>

            <a href="#order-form" class="lp-cta block w-full text-center text-white font-extrabold py-3.5 rounded-xl text-base shadow-lg active:scale-[0.99] transition-transform mt-4">
                {{ $landingPage->order_button_text }}
            </a>
            </div>
        </div>

        @if(filled($landingPage->trust_badges))
        <div class="flex items-center justify-center gap-6 mt-4">
            @foreach(array_slice($landingPage->trust_badges, 0, 2) as $badge)
            <span class="flex items-center gap-1.5 text-xs text-gray-500 font-medium min-w-0">
                @if(!empty($badge['image']))
                    <img src="{{ Storage::url($badge['image']) }}" alt="" class="w-4 h-4 object-contain shrink-0">
                @else
                    <span class="text-base shrink-0">{{ $badge['icon'] ?: '✅' }}</span>
                @endif
                <span class="min-w-0">{{ $badge['text'] }}</span>
            </span>
            @endforeach
        </div>
        @endif
    </div>

    {{-- FAQ --}}
    @if(filled($landingPage->faqs))
    <div class="px-4 py-6 border-t border-gray-100 reveal" x-data="{ open: null }">
        <h2 class="text-center font-extrabold text-gray-900 mb-4">{{ $landingPage->faqs_heading ?: 'সচরাচর জিজ্ঞাসা' }}</h2>
        <div class="space-y-1">
            @foreach($landingPage->faqs as $i => $faq)
            <div class="rounded-xl px-3 transition-colors duration-300" :style="open === {{ $i }} ? 'background: {{ $primary }}0d' : ''">
                <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between gap-3 text-left py-3">
                    <span class="text-sm font-semibold text-gray-800 min-w-0 flex-1">{{ $faq['question'] }}</span>
                    <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 transition-transform duration-300" :class="open === {{ $i }} ? 'rotate-180' : ''" :style="open === {{ $i }} ? 'background: {{ $primary }}1a' : ''">
                        <svg class="w-3.5 h-3.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </span>
                </button>
                <div x-show="open === {{ $i }}" x-transition:enter="transition ease-out duration-250" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" x-cloak>
                    <p class="text-sm text-gray-500 pb-3 leading-relaxed">{{ $faq['answer'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Certificates --}}
    @if(filled($landingPage->certificates))
    <div class="px-4 py-6 border-t border-gray-100 bg-gray-50 reveal">
        <h2 class="text-center font-extrabold text-gray-900 mb-1">{{ $landingPage->certificates_heading ?: 'সার্টিফাইড প্রতিষ্ঠান' }}</h2>
        @if($landingPage->certificates_subheading)
            <p class="text-center text-xs text-gray-500 mb-4 max-w-xs mx-auto">{{ $landingPage->certificates_subheading }}</p>
        @else
            <div class="mb-4"></div>
        @endif
        <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-1 reveal-group">
            @foreach($landingPage->certificates as $img)
            <div class="snap-center shrink-0 w-[45%]">
                <img src="{{ Storage::url($img) }}" class="w-full rounded-xl shadow-md border border-gray-100 bg-white object-cover hover:scale-[1.03] transition-transform duration-300">
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Order Form --}}
    <div id="order-form" class="bg-gray-50 border-t border-gray-100 py-8 px-4 scroll-mt-[4.5rem]"
        x-data="{
            qty: 1,
            unit: {{ (float) ($landingPage->effective_price ?? 0) }},
            zones: {{ Js::from($landingPage->delivery_zones ?: []) }},
            zoneIndex: {{ $landingPage->delivery_zones ? 0 : 'null' }},
            get zoneCharge() { return (this.zoneIndex !== null && this.zones[this.zoneIndex]) ? parseFloat(this.zones[this.zoneIndex].charge || 0) : 0; },
            get subtotal() { return this.unit * this.qty; },
            get total() { return this.subtotal + this.zoneCharge; },
            trackInitiateCheckout() {
                if (typeof fbq === 'function') {
                    fbq('track', 'InitiateCheckout', {
                        value: this.total, currency: {{ Js::from($currencyCode) }},
                        content_type: 'product', content_ids: [{{ Js::from($trackProductId) }}],
                    });
                }
                if (typeof gtag === 'function') {
                    gtag('event', 'begin_checkout', {
                        currency: {{ Js::from($currencyCode) }}, value: this.total,
                        items: [{ item_id: {{ Js::from($trackProductId) }}, item_name: {{ Js::from($landingPage->product?->name ?: $landingPage->title) }}, price: this.unit }],
                    });
                }
            },
        }"
        {{-- Fires once — the moment the order form actually scrolls into view (however the
             visitor got there: a CTA click, or just scrolling), same "reached checkout"
             semantic as the main store's InitiateCheckout, without needing a handler wired
             onto every "Order Now" button on the page individually. --}}
        x-init="new IntersectionObserver((entries, obs) => { if (entries[0].isIntersecting) { trackInitiateCheckout(); obs.disconnect(); } }, { threshold: 0.3 }).observe($el)">
        <div class="bg-white rounded-2xl shadow-xl p-5 reveal">
            <div class="text-center mb-5">
                <h2 class="text-lg font-extrabold text-gray-900">{{ $landingPage->title }}</h2>
                @if($landingPage->effective_price)
                    <p class="lp-price-gradient text-2xl font-extrabold mt-1">{{ format_currency($landingPage->effective_price) }}</p>
                @endif
            </div>

            @if($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl p-3">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
            @endif

            <form action="{{ route('landing.order', $landingPage) }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="flex items-center gap-1.5 text-base font-bold text-gray-900 mb-1.5">
                        <span class="w-1 h-4 rounded-full shrink-0" style="background: {{ $primary }};"></span>
                        আপনার নাম <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </span>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full border border-gray-300 rounded-xl pl-11 pr-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                    </div>
                </div>
                <div>
                    <label class="flex items-center gap-1.5 text-base font-bold text-gray-900 mb-1.5">
                        <span class="w-1 h-4 rounded-full shrink-0" style="background: {{ $primary }};"></span>
                        মোবাইল নম্বর <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        </span>
                        <input type="tel" inputmode="tel" name="phone" value="{{ old('phone') }}" required
                            class="w-full border border-gray-300 rounded-xl pl-11 pr-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                    </div>
                    @include('partials.phone-live-check', ['selector' => 'input[name=phone]', 'bn' => true])
                </div>

                @if($landingPage->collect_address)
                <div>
                    <label class="flex items-center gap-1.5 text-base font-bold text-gray-900 mb-1.5">
                        <span class="w-1 h-4 rounded-full shrink-0" style="background: {{ $primary }};"></span>
                        সম্পূর্ণ ঠিকানা @if($landingPage->require_address)<span class="text-red-500">*</span>@endif
                    </label>
                    <textarea name="address" rows="2" {{ $landingPage->require_address ? 'required' : '' }}
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">{{ old('address') }}</textarea>
                </div>
                @endif

                @if(filled($landingPage->delivery_zones))
                <div>
                    <label class="flex items-center gap-1.5 text-base font-bold text-gray-900 mb-2">
                        <span class="w-1 h-4 rounded-full shrink-0" style="background: {{ $primary }};"></span>
                        ডেলিভারি এলাকা
                    </label>
                    <div class="space-y-2">
                        @foreach($landingPage->delivery_zones as $i => $zone)
                        <label class="flex items-center justify-between border rounded-xl px-4 py-3 cursor-pointer transition-all duration-200 hover:shadow-sm"
                               :class="zoneIndex === {{ $i }} ? 'ring-2 shadow-sm' : 'border-gray-200'"
                               :style="zoneIndex === {{ $i }} ? 'border-color:{{ $primary }};--tw-ring-color:{{ $primary }}' : ''">
                            <span class="flex items-center gap-2 text-base text-gray-700 min-w-0">
                                <input type="radio" x-model.number="zoneIndex" value="{{ $i }}" class="text-orange-600 focus:ring-orange-500 shrink-0">
                                <span class="min-w-0">{{ $zone['label'] }}</span>
                            </span>
                            <span class="text-base font-semibold text-gray-800 shrink-0">{{ $currencySymbol }}{{ number_format((float) $zone['charge'], $decimals) }}</span>
                        </label>
                        @endforeach
                    </div>
                    <input type="hidden" name="delivery_zone" :value="zoneIndex">
                </div>
                @endif

                <div>
                    <label class="flex items-center gap-1.5 text-base font-bold text-gray-900 mb-2">
                        <span class="w-1 h-4 rounded-full shrink-0" style="background: {{ $primary }};"></span>
                        পরিমাণ
                    </label>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="qty = Math.max(1, qty - 1)" :disabled="qty <= 1" class="w-11 h-11 rounded-xl border-2 text-lg font-bold flex items-center justify-center active:scale-90 transition-all duration-150 disabled:opacity-30 disabled:active:scale-100" style="border-color: {{ $primary }}33; color: {{ $primaryDark }};">−</button>
                        <span class="w-10 text-center font-extrabold text-lg" x-text="qty"></span>
                        <button type="button" @click="qty = Math.min(5, qty + 1)" :disabled="qty >= 5" class="w-11 h-11 rounded-xl border-2 text-lg font-bold flex items-center justify-center active:scale-90 transition-all duration-150 disabled:opacity-30 disabled:active:scale-100" style="border-color: {{ $primary }}33; color: {{ $primaryDark }};">+</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1.5" x-show="qty >= 5" x-cloak>প্রতি অর্ডারে সর্বোচ্চ ৫টি নেওয়া যাবে। আরও প্রয়োজন হলে আলাদা অর্ডার করুন।</p>
                    <input type="hidden" name="quantity" :value="qty">
                </div>

                @foreach($landingPage->order_form_fields ?? [] as $field)
                <div>
                    <label class="flex items-center gap-1.5 text-base font-bold text-gray-900 mb-1.5">
                        <span class="w-1 h-4 rounded-full shrink-0" style="background: {{ $primary }};"></span>
                        {{ $field['label'] }} @if($field['required'])<span class="text-red-500">*</span>@endif
                    </label>
                    @if($field['type'] === 'textarea')
                        <textarea name="custom[{{ $field['key'] }}]" rows="2" {{ $field['required'] ? 'required' : '' }}
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">{{ old('custom.' . $field['key']) }}</textarea>
                    @elseif($field['type'] === 'select')
                        <select name="custom[{{ $field['key'] }}]" {{ $field['required'] ? 'required' : '' }}
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                            <option value="">নির্বাচন করুন…</option>
                            @foreach($field['options'] ?? [] as $opt)
                                <option value="{{ $opt }}" {{ old('custom.' . $field['key']) === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    @elseif($field['type'] === 'checkbox')
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="custom[{{ $field['key'] }}]" value="1" {{ old('custom.' . $field['key']) ? 'checked' : '' }} class="rounded text-orange-600 w-5 h-5">
                            <span class="text-base text-gray-600">হ্যাঁ</span>
                        </label>
                    @else
                        <input type="{{ $field['type'] }}" name="custom[{{ $field['key'] }}]" value="{{ old('custom.' . $field['key']) }}" {{ $field['required'] ? 'required' : '' }}
                            class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                    @endif
                </div>
                @endforeach

                @if($landingPage->effective_price)
                <div class="rounded-xl p-4 space-y-1.5 text-base" style="background: linear-gradient(160deg, {{ $primary }}0f, {{ $primary }}03);">
                    <div class="flex justify-between text-gray-600">
                        <span>পণ্যের মূল্য</span>
                        <span x-text="'{{ $currencySymbol }}' + subtotal.toFixed({{ $decimals }})"></span>
                    </div>
                    @if(filled($landingPage->delivery_zones))
                    <div class="flex justify-between text-gray-600">
                        <span>ডেলিভারি চার্জ</span>
                        <span x-text="'{{ $currencySymbol }}' + zoneCharge.toFixed({{ $decimals }})"></span>
                    </div>
                    @endif
                    <div class="flex justify-between font-extrabold pt-1.5 border-t border-dashed" style="border-color: {{ $primary }}40; color: {{ $primaryDark }};">
                        <span>সর্বমোট</span>
                        <span x-text="'{{ $currencySymbol }}' + total.toFixed({{ $decimals }})"></span>
                    </div>
                </div>
                @endif

                <button type="submit"
                    class="lp-cta w-full text-white font-extrabold py-4 rounded-xl text-lg transition-transform active:scale-[0.99]">
                    {{ $landingPage->order_button_text }}
                </button>
                <p class="text-xs text-center text-gray-400">💵 ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন।</p>
            </form>
        </div>
    </div>

    {{-- Persistent bottom order bar — appears once the hero's own CTA scrolls out of view and
         hides again once the real order form is on screen, so there's never a moment past the
         hero where the visitor has to hunt for a way to buy. Pure vanilla JS (two
         IntersectionObservers over plain element IDs) — no changes needed anywhere else. --}}
    <div x-data="{
            show: false,
            init() {
                const heroCta = document.getElementById('hero-cta');
                const orderForm = document.getElementById('order-form');
                if (!heroCta || !orderForm) { return; }
                let heroPassed = false, formVisible = false;
                const update = () => { this.show = heroPassed && !formVisible; };
                new IntersectionObserver(([e]) => { heroPassed = !e.isIntersecting; update(); }, { threshold: 0 }).observe(heroCta);
                new IntersectionObserver(([e]) => { formVisible = e.isIntersecting; update(); }, { threshold: 0.15 }).observe(orderForm);
            },
        }"
        x-show="show" x-cloak
        x-transition:enter="transition ease-out duration-300" x-transition:enter-start="translate-y-full opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-full opacity-0"
        class="fixed bottom-0 inset-x-0 z-50 flex justify-center pointer-events-none">
        <div class="w-full max-w-md bg-white/95 backdrop-blur border-t border-gray-100 shadow-[0_-8px_24px_-8px_rgba(0,0,0,0.15)] px-3 py-2.5 flex items-center gap-3 pointer-events-auto" style="padding-bottom: max(0.625rem, env(safe-area-inset-bottom));">
            @if($stickyThumb)
            <img src="{{ Storage::url($stickyThumb) }}" alt="" class="w-11 h-11 rounded-xl object-contain bg-gray-50 border border-gray-100 shrink-0">
            @endif
            <div class="min-w-0 flex-1">
                <p class="text-xs font-semibold text-gray-700 truncate">{{ $landingPage->title }}</p>
                @if($landingPage->effective_price)
                <p class="lp-price-gradient text-sm font-extrabold">{{ format_currency($landingPage->effective_price) }}</p>
                @endif
            </div>
            <a href="#order-form" class="lp-cta shrink-0 text-white text-sm font-bold px-4 py-2.5 rounded-xl shadow-md active:scale-[0.97] transition-transform whitespace-nowrap">
                {{ $landingPage->order_button_text }}
            </a>
        </div>
    </div>
@endif
@endsection
