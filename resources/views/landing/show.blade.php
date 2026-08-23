@extends('layouts.landing')

@section('content')
@php
    $currencySymbol = html_entity_decode(setting('currency_symbol', '৳'));
    $decimals = (int) setting('decimal_places', 0);
    $primary = $landingPage->brand_color ?: setting('primary_color', '#ea580c');
    $currencyCode = setting('currency_code', 'BDT');
    $trackProductId = $landingPage->product_id ? ($landingPage->product?->sku ?: (string) $landingPage->product_id) : $landingPage->slug;
@endphp
<style>
    .no-scrollbar::-webkit-scrollbar{display:none}
    .no-scrollbar{-ms-overflow-style:none;scrollbar-width:none}
    [x-cloak]{display:none!important}
</style>

@if(session('order_success'))
    {{-- Thank You state — same URL as the landing page itself (redirected back here after a
         successful order), rather than a separate route, so there's only ever one link to
         share/remember for this campaign. --}}
    <div class="text-center py-20 px-6">
        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-extrabold text-gray-900 mb-3">{{ $landingPage->thank_you_heading }}</h1>
        @if($landingPage->thank_you_message)
            <p class="text-gray-500 mb-4">{{ $landingPage->thank_you_message }}</p>
        @endif
        <p class="text-sm text-gray-400 font-mono mb-8">Order #{{ session('order_success') }}</p>

        <a href="{{ route('shop.index') }}" class="inline-flex w-full items-center justify-center gap-2 border-2 font-bold py-3.5 rounded-xl text-base transition hover:opacity-80 active:scale-[0.99]"
           style="border-color: {{ $primary }}; color: {{ $primary }};">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            {{ $landingPage->thank_you_button_text ?: 'আরও প্রোডাক্ট দেখুন' }}
        </a>
    </div>

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
            fbq('track', 'Purchase', {
                value: value, currency: currency,
                content_type: 'product', content_ids: [{{ Js::from($trackProductId) }}],
            });
        }
    })();
    </script>
@else

    {{-- Hero --}}
    <div class="text-center px-4 pt-6 pb-5" style="background: linear-gradient(180deg, {{ $primary }}14, transparent);">
        @if($landingPage->hero_image)
            {{-- object-contain (not cover) so any uploaded photo — wide banner, square product
                 shot, tall bottle on a white background, whatever the admin uploads — shows in
                 full without an ugly crop. The soft color glow behind + drop-shadow on the image
                 itself give it depth so plain white-background product photos don't look like a
                 flat catalog thumbnail sitting on the page. --}}
            <div class="relative mb-4 px-4">
                <div class="absolute inset-x-10 inset-y-3 rounded-[2rem]" style="background: radial-gradient(ellipse at center, {{ $primary }}26, transparent 72%);"></div>
                <img src="{{ Storage::url($landingPage->hero_image) }}" alt="{{ $landingPage->title }}" class="relative w-full max-h-72 object-contain drop-shadow-xl">
            </div>
        @endif
        @if($landingPage->hero_heading)
            <h1 class="text-xl font-extrabold text-gray-900 leading-snug mb-2">{{ $landingPage->hero_heading }}</h1>
        @endif
        @if($landingPage->hero_subheading)
            <p class="text-sm text-gray-500 mb-3">{{ $landingPage->hero_subheading }}</p>
        @endif
        @if($landingPage->rating_value)
        <div class="flex items-center justify-center gap-1.5 mb-4 text-sm">
            <span class="text-amber-400 tracking-tighter">{{ str_repeat('★', (int) round($landingPage->rating_value)) }}{{ str_repeat('☆', 5 - (int) round($landingPage->rating_value)) }}</span>
            <span class="font-semibold text-gray-700">{{ $landingPage->rating_value }}/৫</span>
            @if($landingPage->rating_count)<span class="text-gray-400">({{ number_format($landingPage->rating_count) }} রিভিউ)</span>@endif
        </div>
        @endif
        <a href="#order-form" class="block w-full text-center text-white font-extrabold py-3.5 rounded-xl text-base shadow-lg hover:opacity-90 active:scale-[0.99] transition"
           style="background-color: {{ $primary }};">
            {{ $landingPage->order_button_text }}
        </a>
    </div>

    {{-- Trust badges strip --}}
    @if(filled($landingPage->trust_badges))
    <div class="bg-green-50 border-y border-green-100 px-3 py-3">
        <div class="grid gap-2" style="grid-template-columns: repeat({{ min(3, count($landingPage->trust_badges)) }}, minmax(0,1fr));">
            @foreach(array_slice($landingPage->trust_badges, 0, 6) as $badge)
            <div class="flex flex-col items-center text-center gap-1">
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

    {{-- Admin's free-form content --}}
    @if($landingPage->content)
        <div class="px-4 py-8 prose prose-sm max-w-none prose-headings:font-extrabold prose-a:text-orange-600">
            {!! $landingPage->content !!}
        </div>
    @endif

    {{-- How it works --}}
    @if($landingPage->how_it_works_video)
    <div class="px-4 py-6 border-t border-gray-100">
        <h2 class="text-center font-extrabold text-gray-900 mb-4">{{ $landingPage->how_it_works_heading ?: 'এটি কিভাবে কাজ করে?' }}</h2>
        @php $embed = embed_video_url($landingPage->how_it_works_video); @endphp
        <div class="rounded-2xl overflow-hidden shadow-md bg-black aspect-video">
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
    <div class="px-4 py-6 border-t border-gray-100">
        <h2 class="text-center font-extrabold text-gray-900 mb-4">{{ $landingPage->benefits_heading ?: 'উপকারিতা' }}</h2>
        <div class="grid grid-cols-2 gap-3">
            @foreach($landingPage->benefits as $b)
            <div class="border border-gray-100 rounded-2xl p-3 shadow-sm text-center">
                @if(!empty($b['image']))
                    <img src="{{ Storage::url($b['image']) }}" alt="" class="w-8 h-8 object-contain mx-auto mb-1.5">
                @else
                    <span class="text-2xl leading-none block mb-1.5">{{ $b['icon'] ?: '✨' }}</span>
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
    <div class="px-4 py-6 border-t border-gray-100">
        <h2 class="text-center font-extrabold text-gray-900 mb-4">{{ $landingPage->who_for_heading ?: 'কাদের জন্য এটি' }}</h2>
        <div class="space-y-2">
            @foreach($landingPage->who_for as $w)
            <div class="flex items-center gap-3 border border-gray-100 rounded-xl p-3 bg-gray-50">
                @if(!empty($w['image']))
                    <img src="{{ Storage::url($w['image']) }}" alt="" class="w-6 h-6 object-contain shrink-0">
                @else
                    <span class="text-xl leading-none shrink-0">{{ $w['icon'] ?: '👤' }}</span>
                @endif
                <span class="text-sm text-gray-700 min-w-0 flex-1">{!! bold_markup($w['text']) !!}</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Testimonials --}}
    @if(filled($landingPage->testimonial_videos) || filled($landingPage->testimonial_images))
    <div class="px-4 py-6 border-t border-gray-100">
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
        <div x-data="{ track: null }" class="relative mb-4">
            <div x-ref="track" class="flex gap-3 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-1">
                @foreach($landingPage->testimonial_videos as $v)
                @php $embed = embed_video_url($v['video_url']); @endphp
                <div class="snap-center shrink-0 w-[68%]">
                    <div class="rounded-xl overflow-hidden shadow-md bg-black aspect-[9/16]">
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
        </div>
        @endif

        @if(filled($landingPage->testimonial_images))
        <div class="relative">
            <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-1">
                @foreach($landingPage->testimonial_images as $img)
                <div class="snap-center shrink-0 w-[55%]">
                    <img src="{{ Storage::url($img) }}" class="w-full rounded-xl shadow-md border border-gray-100 object-cover">
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- Special offer / pricing box --}}
    <div id="offer" class="px-4 py-6 border-t border-gray-100">
        <div class="rounded-2xl border-2 p-5" style="border-color: {{ $primary }};">
            @if($landingPage->offer_badge_text)
            <div class="text-center -mt-8 mb-3">
                <span class="inline-block text-white text-xs font-bold px-4 py-1.5 rounded-full shadow" style="background-color: {{ $primary }};">{{ $landingPage->offer_badge_text }}</span>
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
                @if($landingPage->compare_at_price && $landingPage->effective_price && $landingPage->compare_at_price > $landingPage->effective_price)
                    <p class="text-sm text-gray-400 line-through">{{ format_currency((float) $landingPage->compare_at_price) }}</p>
                @endif
                @if($landingPage->effective_price)
                    <p class="text-3xl font-extrabold" style="color: {{ $primary }};">{{ format_currency($landingPage->effective_price) }}</p>
                @endif
            </div>

            <a href="#order-form" class="block w-full text-center text-white font-extrabold py-3.5 rounded-xl text-base shadow-lg hover:opacity-90 active:scale-[0.99] transition mt-4"
               style="background-color: {{ $primary }};">
                {{ $landingPage->order_button_text }}
            </a>
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
    <div class="px-4 py-6 border-t border-gray-100" x-data="{ open: null }">
        <h2 class="text-center font-extrabold text-gray-900 mb-4">{{ $landingPage->faqs_heading ?: 'সচরাচর জিজ্ঞাসা' }}</h2>
        <div class="divide-y divide-gray-100">
            @foreach($landingPage->faqs as $i => $faq)
            <div class="py-3">
                <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}" class="w-full flex items-center justify-between gap-3 text-left">
                    <span class="text-sm font-semibold text-gray-800 min-w-0 flex-1">{{ $faq['question'] }}</span>
                    <svg class="w-4 h-4 text-gray-400 shrink-0 transition-transform" :class="open === {{ $i }} ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <p class="text-sm text-gray-500 mt-2 leading-relaxed" x-show="open === {{ $i }}" x-transition x-cloak>{{ $faq['answer'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Certificates --}}
    @if(filled($landingPage->certificates))
    <div class="px-4 py-6 border-t border-gray-100 bg-gray-50">
        <h2 class="text-center font-extrabold text-gray-900 mb-1">{{ $landingPage->certificates_heading ?: 'সার্টিফাইড প্রতিষ্ঠান' }}</h2>
        @if($landingPage->certificates_subheading)
            <p class="text-center text-xs text-gray-500 mb-4 max-w-xs mx-auto">{{ $landingPage->certificates_subheading }}</p>
        @else
            <div class="mb-4"></div>
        @endif
        <div class="flex gap-3 overflow-x-auto snap-x snap-mandatory no-scrollbar pb-1">
            @foreach($landingPage->certificates as $img)
            <div class="snap-center shrink-0 w-[45%]">
                <img src="{{ Storage::url($img) }}" class="w-full rounded-xl shadow-md border border-gray-100 bg-white object-cover">
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Order Form --}}
    <div id="order-form" class="bg-gray-50 border-t border-gray-100 py-8 px-4 scroll-mt-16"
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
        <div class="bg-white rounded-2xl shadow-xl p-5">
            <div class="text-center mb-5">
                <h2 class="text-lg font-extrabold text-gray-900">{{ $landingPage->title }}</h2>
                @if($landingPage->effective_price)
                    <p class="text-2xl font-extrabold mt-1" style="color: {{ $primary }};">{{ format_currency($landingPage->effective_price) }}</p>
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
                    <label class="block text-base font-medium text-gray-700 mb-1">আপনার নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                </div>
                <div>
                    <label class="block text-base font-medium text-gray-700 mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                    <input type="tel" inputmode="tel" name="phone" value="{{ old('phone') }}" required
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">
                </div>

                @if($landingPage->collect_address)
                <div>
                    <label class="block text-base font-medium text-gray-700 mb-1">
                        সম্পূর্ণ ঠিকানা @if($landingPage->require_address)<span class="text-red-500">*</span>@endif
                    </label>
                    <textarea name="address" rows="2" {{ $landingPage->require_address ? 'required' : '' }}
                        class="w-full border border-gray-300 rounded-xl px-4 py-3 text-base focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-500 transition">{{ old('address') }}</textarea>
                </div>
                @endif

                @if(filled($landingPage->delivery_zones))
                <div>
                    <label class="block text-base font-medium text-gray-700 mb-2">ডেলিভারি এলাকা</label>
                    <div class="space-y-2">
                        @foreach($landingPage->delivery_zones as $i => $zone)
                        <label class="flex items-center justify-between border rounded-xl px-4 py-3 cursor-pointer transition"
                               :class="zoneIndex === {{ $i }} ? 'ring-2' : 'border-gray-200'"
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
                    <label class="block text-base font-medium text-gray-700 mb-2">পরিমাণ</label>
                    <div class="flex items-center gap-3">
                        <button type="button" @click="qty = Math.max(1, qty - 1)" class="w-10 h-10 rounded-xl border border-gray-300 text-lg font-bold text-gray-600 hover:bg-gray-50 transition">−</button>
                        <span class="w-10 text-center font-semibold text-lg" x-text="qty"></span>
                        <button type="button" @click="qty = Math.min(99, qty + 1)" class="w-10 h-10 rounded-xl border border-gray-300 text-lg font-bold text-gray-600 hover:bg-gray-50 transition">+</button>
                    </div>
                    <input type="hidden" name="quantity" :value="qty">
                </div>

                @foreach($landingPage->order_form_fields ?? [] as $field)
                <div>
                    <label class="block text-base font-medium text-gray-700 mb-1">
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
                <div class="bg-gray-50 rounded-xl p-4 space-y-1.5 text-base">
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
                    <div class="flex justify-between font-bold text-gray-900 pt-1.5 border-t border-dashed border-gray-200">
                        <span>সর্বমোট</span>
                        <span x-text="'{{ $currencySymbol }}' + total.toFixed({{ $decimals }})"></span>
                    </div>
                </div>
                @endif

                <button type="submit"
                    class="w-full text-white font-extrabold py-4 rounded-xl text-lg transition shadow-lg hover:opacity-90 active:scale-[0.99]"
                    style="background-color: {{ $primary }};">
                    {{ $landingPage->order_button_text }}
                </button>
                <p class="text-xs text-center text-gray-400">💵 ক্যাশ অন ডেলিভারি — পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন।</p>
            </form>
        </div>
    </div>
@endif
@endsection
