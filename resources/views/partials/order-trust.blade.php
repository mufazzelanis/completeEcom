{{-- "Why order from us" strip, right next to the buy / place-order buttons — the point where a
     first-time buyer hesitates. Every line comes from settings (COD payment method active,
     Settings → Shipping delivery days, return days, company phone), so it never promises
     something the shop doesn't do. Page-local ot-* CSS, light + dark. --}}
@php
    $otCodActive = \App\Models\PaymentMethod::where('is_active', true)->whereIn('slug', ['cod', 'cash_on_delivery'])->exists();
    $otDays = trim((string) setting('delivery_days', '3-7'));
    $otReturn = (int) setting('return_days', '7');
    // "Order on WhatsApp" with the product / cart already written in the message — no number on the page
    $otWa = ($showChat ?? true) ? whatsapp_chat_url($waText ?? ('Hi ' . setting('site_name', '') . ', I need help with my order.')) : null;
    $otItems = array_values(array_filter([
        $otCodActive ? ['💵', 'Cash on Delivery', 'Pay when you receive'] : null,
        $otDays !== '' ? ['🚚', 'Fast delivery', $otDays . ' days, all over Bangladesh'] : null,
        $otReturn > 0 ? ['↩️', $otReturn . '-day easy return', 'Wrong or damaged? We replace it'] : null,
        ['✅', '100% original', 'Sourced from trusted suppliers'],
    ]));
@endphp
<style>
    .ot-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .ot-item { display: flex; align-items: center; gap: .6rem; padding: .6rem .7rem; border-radius: .85rem; background: #f8fafc; border: 1px solid #eef1f5; }
    .ot-item .ot-i { font-size: 1.15rem; line-height: 1; flex-shrink: 0; }
    .ot-item .ot-t { font-size: .8rem; font-weight: 700; color: #111827; line-height: 1.2; }
    .ot-item .ot-d { font-size: .7rem; color: #6b7280; line-height: 1.3; margin-top: .1rem; }
    .ot-wa { display: flex; align-items: center; justify-content: center; gap: .6rem; width: 100%; margin-top: .6rem; padding: .6rem .9rem; border-radius: .85rem; font-size: .85rem; font-weight: 700; color: #15803d; background: #f0fdf4; border: 1px solid #bbf7d0; transition: background-color .15s, transform .15s; }
    .ot-wa:hover { background: #dcfce7; transform: translateY(-1px); }
    .ot-wa small { font-weight: 500; color: #4b5563; }
    .ot-wa-ic { position: relative; isolation: isolate; flex-shrink: 0; width: 1.6rem; height: 1.6rem; border-radius: 50%; background: #25d366; color: #fff; display: flex; align-items: center; justify-content: center; }
    .ot-wa-ic::before { content: ''; position: absolute; inset: 0; border-radius: 50%; background: #25d366; animation: ot-pulse 2s ease-out infinite; z-index: -1; }
    @keyframes ot-pulse { 0% { transform: scale(1); opacity: .55; } 80%, 100% { transform: scale(1.9); opacity: 0; } }
    @media (prefers-reduced-motion: reduce) { .ot-wa-ic::before { animation: none; } }
    .dark .ot-wa { background: rgba(22, 163, 74, .12); border-color: rgba(134, 239, 172, .3); color: #4ade80; }
    .dark .ot-wa:hover { background: rgba(22, 163, 74, .2); }
    .dark .ot-wa small { color: #9ca3af; }
    .dark .ot-item { background: #182032; border-color: #263044; }
    .dark .ot-item .ot-t { color: #f3f4f6; }
    .dark .ot-item .ot-d { color: #9ca3af; }
</style>
<div class="{{ $class ?? '' }}">
    <div class="ot-grid">
        @foreach($otItems as [$icon, $title, $desc])
            <div class="ot-item">
                <span class="ot-i" aria-hidden="true">{{ $icon }}</span>
                <div class="min-w-0"><p class="ot-t">{{ $title }}</p><p class="ot-d">{{ $desc }}</p></div>
            </div>
        @endforeach
    </div>
    @if($otWa)
        <a href="{{ $otWa }}" target="_blank" rel="noopener nofollow" class="ot-wa">
            <span class="ot-wa-ic" aria-hidden="true">
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 24 24"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.16-.17.2-.35.22-.64.07-.3-.15-1.26-.46-2.4-1.48-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.6.13-.14.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.07c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.7.63.71.22 1.36.19 1.87.12.57-.09 1.76-.72 2-1.41.25-.7.25-1.29.18-1.41-.07-.13-.27-.2-.57-.35M12.05 21.79h-.01a9.87 9.87 0 01-5.03-1.38l-.36-.21-3.74.98 1-3.65-.24-.37a9.86 9.86 0 01-1.51-5.26c0-5.45 4.44-9.88 9.89-9.88 2.64 0 5.12 1.03 6.99 2.9a9.82 9.82 0 012.89 6.99c0 5.45-4.44 9.88-9.88 9.88M20.46 3.49A11.82 11.82 0 0012.05 0C5.5 0 .16 5.34.16 11.89c0 2.1.55 4.14 1.59 5.94L.06 24l6.3-1.65a11.88 11.88 0 005.68 1.45h.01c6.55 0 11.89-5.34 11.89-11.89 0-3.18-1.24-6.16-3.48-8.41"/></svg>
            </span>
            <span>Order on WhatsApp <small>— quick reply</small></span>
        </a>
    @endif
</div>
