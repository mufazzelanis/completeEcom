{{-- Phone-only bar: price + "Order now" fixed above the bottom nav whenever the real buy
     buttons (#pd-buy-form) are off screen, so a shopper reading the description or reviews can
     order without scrolling back up. "Order now" presses the real Buy Now button — same
     quantity, variant check, loader and tracking. If a variant still has to be picked, it
     scrolls up to the options instead. Moved to <body> on load: a transformed ancestor would
     otherwise trap position:fixed. --}}
@php
    $sbPrice = $product->isVariable() ? ($priceMin ?? (float) $product->final_price) : (float) $product->final_price;
    $sbWas = !$product->isVariable() && $product->price > $sbPrice ? (float) $product->price : null;
    $sbWa = whatsapp_chat_url("Hi, I'd like to order this:\n{$product->name} — ৳" . number_format($sbPrice) . "\n" . route('products.show', $product));
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
        @if($sbWa)
            <a href="{{ $sbWa }}" target="_blank" rel="noopener nofollow" class="pd-sb-call" aria-label="Order on WhatsApp" title="Order on WhatsApp">
                <svg width="22" height="22" fill="currentColor" viewBox="0 0 24 24"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.07-.13-.27-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.44 9.88-9.88 9.88M20.46 3.49A11.82 11.82 0 0012.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.94L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.16-3.48-8.41"/></svg>
                <span class="pd-sb-dot" aria-hidden="true"></span>
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
    .pd-sb-call { position: relative; flex-shrink: 0; width: 2.75rem; height: 2.75rem; border-radius: .85rem; display: flex; align-items: center; justify-content: center; color: #16a34a; background: #f0fdf4; border: 1px solid #bbf7d0; }
    /* small "online" dot that softly pulses — catches the eye without a flashing banner */
    .pd-sb-dot { position: absolute; top: .3rem; right: .3rem; width: .5rem; height: .5rem; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 2px #fff; }
    .pd-sb-dot::after { content: ''; position: absolute; inset: 0; border-radius: 50%; background: #22c55e; animation: pd-sb-pulse 1.8s ease-out infinite; }
    @keyframes pd-sb-pulse { 0% { transform: scale(1); opacity: .7; } 100% { transform: scale(2.6); opacity: 0; } }
    @media (prefers-reduced-motion: reduce) { .pd-sb-dot::after { animation: none; } }
    .dark .pd-sb-dot { box-shadow: 0 0 0 2px #111827; }
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
