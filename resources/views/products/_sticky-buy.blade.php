{{-- Phone-only bar: price + "Order now" fixed above the bottom nav whenever the real buy
     buttons (#pd-buy-form) are off screen, so a shopper reading the description or reviews can
     order without scrolling back up. "Order now" presses the real Buy Now button — same
     quantity, variant check, loader and tracking. If a variant still has to be picked, it
     scrolls up to the options instead. Moved to <body> on load: a transformed ancestor would
     otherwise trap position:fixed. --}}
@php
    $sbPrice = $product->isVariable() ? ($priceMin ?? (float) $product->final_price) : (float) $product->final_price;
    $sbWas = !$product->isVariable() && $product->price > $sbPrice ? (float) $product->price : null;
    $sbPhone = setting('company_phone');
@endphp
<div id="pd-sticky-buy" class="pd-sb" hidden aria-hidden="true">
    <div class="pd-sb-in">
        @if($product->image)
            <img src="{{ image_thumb($product->image, 200) }}" alt="" class="pd-sb-img">
        @endif
        <div class="pd-sb-price">
            <p class="pd-sb-label">{{ $product->isVariable() ? 'From' : 'Price' }}</p>
            <p class="pd-sb-now">৳{{ number_format($sbPrice) }}@if($sbWas) <s>৳{{ number_format($sbWas) }}</s>@endif</p>
        </div>
        @if($sbPhone)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $sbPhone) }}" class="pd-sb-call" aria-label="Call to order">
                <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
            </a>
        @endif
        <button type="button" class="pd-sb-btn" data-sb-order>
            {{ setting('buy_now_button_text', 'Buy Now') }}
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/></svg>
        </button>
    </div>
</div>
<style>
    .pd-sb { position: fixed; left: 0; right: 0; bottom: calc(5rem + env(safe-area-inset-bottom, 0px)); z-index: 40; padding: 0 .75rem; transform: translateY(140%); opacity: 0; transition: transform .28s ease, opacity .28s ease; pointer-events: none; }
    .pd-sb.is-on { transform: none; opacity: 1; pointer-events: auto; }
    @media (min-width: 1024px) { .pd-sb { display: none !important; } }
    .pd-sb-in { max-width: 28rem; margin: 0 auto; display: flex; align-items: center; gap: .6rem; padding: .45rem .45rem .45rem .5rem; border-radius: 1.1rem; background: rgba(255, 255, 255, .96); backdrop-filter: blur(14px); -webkit-backdrop-filter: blur(14px); box-shadow: 0 14px 34px -10px rgba(15, 23, 42, .35); border: 1px solid rgba(15, 23, 42, .06); }
    .dark .pd-sb-in { background: rgba(17, 24, 39, .95); border-color: rgba(255, 255, 255, .08); }
    .pd-sb-img { width: 2.6rem; height: 2.6rem; border-radius: .7rem; object-fit: contain; background: #f3f4f6; flex-shrink: 0; }
    .dark .pd-sb-img { background: #1f2937; }
    .pd-sb-price { flex: 1; min-width: 0; }
    .pd-sb-label { font-size: .65rem; color: #9ca3af; line-height: 1; }
    .pd-sb-now { font-size: 1.05rem; font-weight: 800; color: #111827; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .dark .pd-sb-now { color: #fff; }
    .pd-sb-now s { font-size: .75rem; font-weight: 500; color: #9ca3af; margin-left: .2rem; }
    .pd-sb-call { flex-shrink: 0; width: 2.75rem; height: 2.75rem; border-radius: .85rem; display: flex; align-items: center; justify-content: center; color: #16a34a; background: #f0fdf4; border: 1px solid #bbf7d0; }
    .dark .pd-sb-call { background: rgba(22, 163, 74, .15); border-color: rgba(134, 239, 172, .3); color: #4ade80; }
    .pd-sb-btn { flex-shrink: 0; display: inline-flex; align-items: center; gap: .35rem; padding: .8rem 1.1rem; border-radius: .85rem; font-weight: 800; font-size: .92rem; color: #fff; background: #4f46e5; box-shadow: 0 8px 18px -8px #4f46e5; }
    .pd-sb-btn:active { transform: scale(.96); }
    /* the floating Chat / Back-to-top buttons share this spot — lift them above the bar while it shows */
    @media (max-width: 1023px) {
        body .fixed.bottom-20 { transition: bottom .28s ease; }
        body.pd-sb-on .fixed.bottom-20 { bottom: calc(9.25rem + env(safe-area-inset-bottom, 0px)); }
    }
</style>
<script>
(function () {
    var bar = document.getElementById('pd-sticky-buy');
    var form = document.getElementById('pd-buy-form');
    if (!bar || !form || !('IntersectionObserver' in window)) return;
    document.body.appendChild(bar);
    bar.hidden = false;
    var set = function (on) {
        bar.classList.toggle('is-on', on);
        bar.setAttribute('aria-hidden', on ? 'false' : 'true');
        document.body.classList.toggle('pd-sb-on', on);
    };
    new IntersectionObserver(function (entries) { set(!entries[0].isIntersecting); }, { threshold: 0 }).observe(form);
    bar.querySelector('[data-sb-order]').addEventListener('click', function () {
        var buy = form.querySelector('[data-buy-now]');
        if (!buy || buy.disabled) {
            // a variant still has to be chosen — take them to the options
            form.scrollIntoView({ behavior: 'smooth', block: 'center' });
            form.closest('[x-data]') && form.parentElement.animate && form.animate([{ transform: 'scale(1)' }, { transform: 'scale(1.02)' }, { transform: 'scale(1)' }], { duration: 450 });
            return;
        }
        buy.click();
    });
})();
</script>
