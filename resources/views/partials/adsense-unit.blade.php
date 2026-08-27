{{-- Single Google AdSense ad unit — include with:
     @include('partials.adsense-unit', ['slot' => setting('adsense_slot_infeed')])
     Deliberately only ever included from blog views (Settings → Advertisements is explicit
     that ads are Blog-only — nowhere in the shop/checkout/landing-page funnels, where an ad
     would just distract a customer away from actually buying). Renders nothing at all unless
     ads are enabled, a Publisher ID is set, AND this specific slot has an ID — so leaving any
     one placement's slot blank in Settings cleanly skips just that spot. --}}
@php
    $adsEnabled = setting('ads_enabled', '0') === '1';
    $adsPublisherId = setting('adsense_publisher_id', '');
@endphp
@if($adsEnabled && $adsPublisherId && !empty($slot))
    {{-- The loader script must load exactly once per page no matter how many ad units are on
         it — @once keys off this exact source location, so every include of this same
         partial correctly shares one loader regardless of how many times it's used. --}}
    @once
        <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $adsPublisherId }}" crossorigin="anonymous"></script>
    @endonce
    <div class="my-5 text-center">
        <p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-600 mb-1.5">Advertisement</p>
        <ins class="adsbygoogle" style="display:block"
             data-ad-client="{{ $adsPublisherId }}"
             data-ad-slot="{{ $slot }}"
             data-ad-format="auto"
             data-full-width-responsive="true"></ins>
        <script>(adsbygoogle = window.adsbygoogle || []).push({});</script>
    </div>
@endif
