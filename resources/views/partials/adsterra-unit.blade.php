{{-- One Adsterra ad placement — include with:
     @include('partials.adsterra-unit', ['code' => setting('adsterra_code_infeed')])
     Unlike AdSense (one stable data-ad-client/data-ad-slot shape), Adsterra's snippet differs
     per ad format (Banner/Native/Social Bar/etc.), so this renders the admin's pasted code
     verbatim rather than assuming any particular structure — same trust model as the site's
     Custom CSS/JS fields (admin-only, not sanitized, since sanitizing would just strip the
     script tags the ad needs). Blog-only by placement (see where this is included), same as
     the AdSense unit. --}}
@php
    $adsterraEnabled = setting('adsterra_enabled', '0') === '1';
@endphp
@if($adsterraEnabled && !empty($code))
    <div class="my-5 text-center">
        <p class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-600 mb-1.5">Advertisement</p>
        {!! $code !!}
    </div>
@endif
