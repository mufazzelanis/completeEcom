{{-- A landing page in one of the designed themes (App\Support\LandingThemes): the same saved
     content as the classic page, in the theme's own section order. Included by landing/show,
     which has already worked out $currencySymbol, $decimals, $currencyCode, $trackProductId,
     $discountPct and $lpTheme. Everything that reacts to a tap (packs, zones, totals, FAQ,
     videos, zoom) runs in the page itself, so it changes at once with no request. --}}
@php
    $lp = $landingPage;
    $bnDigits = fn ($n) => strtr((string) $n, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
    $heroImg = $lp->hero_image ?: $lp->product?->image;
    $productImg = $lp->product?->image ?: $lp->hero_image;
    $price = $lp->effective_price;
    $was = ($lp->compare_at_price && $price && $lp->compare_at_price > $price) ? (float) $lp->compare_at_price : null;
    $badges = array_values(array_slice($lp->trust_badges ?? [], 0, 6));
    $stars = $lp->rating_value ? max(0, min(5, (int) round($lp->rating_value))) : 0;
    $srcset = fn ($path) => image_thumb($path, 400) . ' 400w, ' . image_thumb($path, 800) . ' 800w, ' . image_thumb($path, 1200) . ' 1200w';
    $heading = fn (?string $own, string $section) => $own ?: \App\Support\LandingThemes::heading($lpTheme, $section);
    $heroBadge = $lp->offer_badge_text ?: match ($lpTheme) {
        'clinical' => '১০০% অরিজিনাল প্রোডাক্ট',
        'offer' => 'আজকের বিশেষ অফার',
        'care' => 'কোমল যত্ন, নিরাপদ পছন্দ',
        'premium' => 'প্রিমিয়াম কালেকশন',
        default => 'ক্যাশ অন ডেলিভারি',
    };
    $clockTotal = max(1, (int) $lp->urgency_bar_minutes * 60 + (int) $lp->urgency_bar_seconds);
    $checkSvg = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>';
    $arrowSvg = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5-5 5M6 12h12"/></svg>';
@endphp

@foreach(\App\Support\LandingThemes::sections($lpTheme) as $section)
@switch($section)

{{-- ═══ Hero ═══ --}}
@case('hero')
<section class="lt-hero">
    <div class="lt-wrap">
        <div class="lt-hero-grid">
            <div class="lt-enter">
                <span class="lt-badge"><i></i>{{ $heroBadge }}</span>
                <h1 class="lt-h1">{{ $lp->hero_heading ?: $lp->title }}</h1>
                @if($lp->hero_subheading)
                    <p class="lt-lead">{{ $lp->hero_subheading }}</p>
                @endif
                @if($stars)
                <div class="lt-rating">
                    <span class="lt-stars" aria-hidden="true">{{ str_repeat('★', $stars) }}{{ str_repeat('☆', 5 - $stars) }}</span>
                    <b>{{ $bnDigits($lp->rating_value) }}/৫</b>
                    @if($lp->rating_count)<span class="lt-muted">({{ $bnDigits(number_format($lp->rating_count)) }} রিভিউ)</span>@endif
                </div>
                @endif

                @if($lpTheme === 'offer' && $lp->urgency_bar_enabled)
                <div class="lt-clock" style="margin-top:18px" x-data="{ left: {{ $clockTotal }} }" x-init="setInterval(() => { if (left > 0) left--; }, 1000)" aria-label="অফার শেষ হতে বাকি">
                    <div><b x-text="String(Math.floor(left / 60)).padStart(2, '0')">{{ str_pad((string) intdiv($clockTotal, 60), 2, '0', STR_PAD_LEFT) }}</b><small>মিনিট</small></div>
                    <div><b x-text="String(left % 60).padStart(2, '0')">{{ str_pad((string) ($clockTotal % 60), 2, '0', STR_PAD_LEFT) }}</b><small>সেকেন্ড</small></div>
                </div>
                @endif

                @if($price)
                <div class="lt-price-row">
                    <span class="lt-price">{{ format_currency($price) }}</span>
                    @if($was)<span class="lt-was">{{ format_currency($was) }}</span>@endif
                    @if($discountPct)<span class="lt-save">{{ $bnDigits($discountPct) }}% ছাড় · {{ format_currency($was - $price) }} সাশ্রয়</span>@endif
                </div>
                @else
                <div style="height:20px"></div>
                @endif

                <div class="lt-cta-row">
                    <a href="#order-form" id="hero-cta" class="lt-btn">{{ $lp->order_button_text }} {!! $arrowSvg !!}</a>
                </div>

                @if($badges)
                <div class="lt-chips">
                    @foreach(array_slice($badges, 0, 3) as $b)
                    <span>
                        @if(!empty($b['image']))<img src="{{ Storage::url($b['image']) }}" alt="" width="18" height="18" loading="lazy">@else{{ $b['icon'] ?: '✅' }}@endif
                        {{ $b['text'] }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>

            @if($heroImg)
            <div class="lt-hero-media lt-enter-2">
                <div class="lt-hero-frame">
                    @if($discountPct)<span class="lt-burst">{{ $bnDigits($discountPct) }}%<small>ছাড়</small></span>@endif
                    <img src="{{ image_thumb($heroImg, 800) }}" srcset="{{ $srcset($heroImg) }}" sizes="(min-width: 900px) 520px, 260px"
                         width="800" height="800" alt="{{ $lp->product?->name ?: $lp->title }}" fetchpriority="high" decoding="async">
                </div>
            </div>
            @endif
        </div>
    </div>
</section>
@break

{{-- ═══ Trust strip ═══ --}}
@case('trust')
@if($badges)
<div class="lt-trust">
    <div class="lt-wrap lt-trust-grid reveal-group">
        @foreach($badges as $b)
        <div class="lt-trust-item">
            <span class="lt-ico">@if(!empty($b['image']))<img src="{{ Storage::url($b['image']) }}" alt="" width="26" height="26" loading="lazy">@else{{ $b['icon'] ?: '✅' }}@endif</span>
            <span>{{ $b['text'] }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif
@break

{{-- ═══ Benefits ═══ --}}
@case('benefits')
@if(filled($lp->benefits))
<section class="lt-sec lt-sec--alt" id="benefits">
    <div class="lt-wrap">
        <div class="lt-sec-head reveal">
            <h2 class="lt-h2">{{ $heading($lp->benefits_heading, 'benefits') }}</h2>
        </div>
        <div class="lt-cards reveal-group">
            @foreach($lp->benefits as $i => $b)
            <div class="lt-card">
                <span class="lt-num">{{ $bnDigits(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) }}</span>
                <span class="lt-ico">@if(!empty($b['image']))<img src="{{ Storage::url($b['image']) }}" alt="" width="26" height="26" loading="lazy">@else{{ $b['icon'] ?: '✨' }}@endif</span>
                <h3>{{ $b['title'] }}</h3>
                @if(!empty($b['description']))<p>{{ $b['description'] }}</p>@endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@break

{{-- ═══ Who is it for ═══ --}}
@case('who_for')
@if(filled($lp->who_for))
<section class="lt-sec">
    <div class="lt-wrap lt-narrow" style="max-width:960px">
        <div class="lt-sec-head reveal">
            <h2 class="lt-h2">{{ $heading($lp->who_for_heading, 'who_for') }}</h2>
        </div>
        <div class="lt-list reveal-group">
            @foreach($lp->who_for as $w)
            <div class="lt-li">
                <span class="lt-ico">@if(!empty($w['image']))<img src="{{ Storage::url($w['image']) }}" alt="" width="26" height="26" loading="lazy">@else{{ $w['icon'] ?: '✔️' }}@endif</span>
                <span>{!! bold_markup($w['text']) !!}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@break

{{-- ═══ Free-form content (the admin's editor) ═══ --}}
@case('content')
@if($lp->content)
<section class="lt-sec">
    <div class="lt-wrap lt-narrow">
        <article class="lt-article reveal">
            <div class="lp-content" style="--lp-primary: {{ $lp->brand_color ?: \App\Support\LandingThemes::tokens($lpTheme)['brand'] }};">
                {!! demote_h1($lp->content) !!}
            </div>
        </article>
    </div>
</section>
@endif
@break

{{-- ═══ How it works video ═══ --}}
@case('video')
@if($lp->how_it_works_video)
<section class="lt-sec">
    <div class="lt-wrap lt-narrow">
        <div class="lt-sec-head reveal">
            <h2 class="lt-h2">{{ $heading($lp->how_it_works_heading, 'video') }}</h2>
        </div>
        <div class="reveal">
            @include('landing.themes._video', ['url' => $lp->how_it_works_video, 'tall' => false, 'label' => null])
        </div>
    </div>
</section>
@endif
@break

{{-- ═══ Reviews: videos and screenshots ═══ --}}
@case('testimonials')
@if(filled($lp->testimonial_videos) || filled($lp->testimonial_images))
<section class="lt-sec" x-data="{ zoom: null }" @keydown.escape.window="zoom = null">
    <div class="lt-wrap">
        <div class="lt-sec-head reveal">
            <h2 class="lt-h2">{{ $heading($lp->testimonials_heading, 'testimonials') }}</h2>
            @if($stars)
            <p class="lt-sub"><span class="lt-stars">{{ str_repeat('★', $stars) }}</span> {{ $bnDigits($lp->rating_value) }}/৫ @if($lp->rating_count)· {{ $bnDigits(number_format($lp->rating_count)) }} রিভিউ@endif</p>
            @endif
        </div>
        @if(filled($lp->testimonial_videos))
        <div class="lt-rail lt-rail--video" style="margin-bottom:18px">
            @foreach($lp->testimonial_videos as $v)
            <div>
                @include('landing.themes._video', ['url' => $v['video_url'], 'tall' => true, 'label' => $v['name'] ?? null])
            </div>
            @endforeach
        </div>
        @endif
        @if(filled($lp->testimonial_images))
        <div class="lt-rail lt-rail--img">
            @foreach($lp->testimonial_images as $img)
            <div>
                <button type="button" class="lt-shot" @click="zoom = @js(Storage::url($img))" aria-label="রিভিউ বড় করে দেখুন">
                    <img src="{{ image_thumb($img, 400) }}" alt="গ্রাহকের রিভিউ" loading="lazy" decoding="async" width="400" height="500">
                </button>
            </div>
            @endforeach
        </div>
        @endif
    </div>
    <div class="lt-zoom" x-show="zoom" x-cloak x-transition.opacity @click="zoom = null"><img :src="zoom" alt=""></div>
</section>
@endif
@break

{{-- ═══ Certificates ═══ --}}
@case('certificates')
@if(filled($lp->certificates))
<section class="lt-sec lt-sec--alt" x-data="{ zoom: null }" @keydown.escape.window="zoom = null">
    <div class="lt-wrap">
        <div class="lt-sec-head reveal">
            <h2 class="lt-h2">{{ $heading($lp->certificates_heading, 'certificates') }}</h2>
            @if($lp->certificates_subheading)<p class="lt-sub">{{ $lp->certificates_subheading }}</p>@endif
        </div>
        <div class="lt-certs reveal-group">
            @foreach($lp->certificates as $img)
            <button type="button" class="lt-shot" @click="zoom = @js(Storage::url($img))" aria-label="সার্টিফিকেট বড় করে দেখুন">
                <img src="{{ image_thumb($img, 400) }}" alt="সার্টিফিকেট" loading="lazy" decoding="async" width="400" height="400">
            </button>
            @endforeach
        </div>
    </div>
    <div class="lt-zoom" x-show="zoom" x-cloak x-transition.opacity @click="zoom = null"><img :src="zoom" alt=""></div>
</section>
@endif
@break

{{-- ═══ Offer box ═══ --}}
@case('offer')
@if($price || filled($lp->pricing_items))
<section class="lt-sec" id="offer">
    <div class="lt-wrap">
        <div class="lt-offer reveal">
            <span class="lt-offer-tag">{{ $lp->offer_badge_text ?: 'বিশেষ অফার' }}</span>
            @if($productImg)
                <img class="lt-offer-img" src="{{ image_thumb($productImg, 400) }}" alt="{{ $lp->product?->name ?: $lp->title }}" width="170" height="170" loading="lazy" decoding="async">
            @endif
            <h2 class="lt-h2" style="font-size:1.3rem">{{ $lp->product?->name ?: $lp->title }}</h2>
            @if(filled($lp->pricing_items))
            <div class="lt-lines">
                @foreach($lp->pricing_items as $item)
                <div><span>{{ $item['label'] }}</span><b>{{ $item['price'] }}</b></div>
                @endforeach
            </div>
            @endif
            @if($price)
            <div class="lt-price-row">
                <span class="lt-price">{{ format_currency($price) }}</span>
                @if($was)<span class="lt-was">{{ format_currency($was) }}</span>@endif
                @if($discountPct)<span class="lt-save">{{ $bnDigits($discountPct) }}% ছাড়</span>@endif
            </div>
            @endif
            <a href="#order-form" class="lt-btn lt-btn--block">{{ $lp->order_button_text }} {!! $arrowSvg !!}</a>
            <p class="lt-cod">💵 পণ্য হাতে পেয়ে টাকা পরিশোধ করুন</p>
        </div>
    </div>
</section>
@endif
@break

{{-- ═══ FAQ ═══ --}}
@case('faq')
@if(filled($lp->faqs))
<section class="lt-sec">
    <div class="lt-wrap">
        <div class="lt-sec-head reveal">
            <h2 class="lt-h2">{{ $heading($lp->faqs_heading, 'faq') }}</h2>
        </div>
        <div class="lt-faq reveal-group">
            @foreach($lp->faqs as $i => $faq)
            <details @if($i === 0) open @endif>
                <summary>{{ $faq['question'] }} <i aria-hidden="true"></i></summary>
                <p>{{ $faq['answer'] }}</p>
            </details>
            @endforeach
        </div>
    </div>
</section>
@endif
@break

{{-- ═══ Order form ═══ --}}
@case('order')
@include('landing.themes._order')
@break

@endswitch
@endforeach

{{-- Phone: an order bar appears once the hero's button has scrolled away, and steps aside
     while the form itself is on screen. Desktop has the button in the header instead. --}}
<div class="lt-bar is-hidden" x-data="{
        show: false,
        init() {
            const cta = document.getElementById('hero-cta'), form = document.getElementById('order-form');
            if (!cta || !form) return;
            let past = false, onForm = false;
            const set = () => { this.show = past && !onForm; };
            new IntersectionObserver(([e]) => { past = !e.isIntersecting && e.boundingClientRect.top < 0; set(); }).observe(cta);
            new IntersectionObserver(([e]) => { onForm = e.isIntersecting; set(); }, { threshold: 0.1 }).observe(form);
        },
    }" :class="{ 'is-hidden': !show }">
    @if($productImg)<img src="{{ image_thumb($productImg, 96) }}" alt="" width="46" height="46" loading="lazy">@endif
    <div class="lt-bar-txt">
        <p>{{ $lp->product?->name ?: $lp->title }}</p>
        @if($price)<b>{{ format_currency($price) }}</b>@endif
    </div>
    <a href="#order-form" class="lt-btn">{{ $lp->order_button_text }}</a>
</div>
