{{-- "Why order from us" strip, right next to the buy / place-order buttons — the point where a
     first-time buyer hesitates. Every line comes from settings (COD payment method active,
     Settings → Shipping delivery days, return days, company phone), so it never promises
     something the shop doesn't do. Page-local ot-* CSS, light + dark. --}}
@php
    $otCodActive = \App\Models\PaymentMethod::where('is_active', true)->whereIn('slug', ['cod', 'cash_on_delivery'])->exists();
    $otDays = trim((string) setting('delivery_days', '3-7'));
    $otReturn = (int) setting('return_days', '7');
    $otPhone = setting('company_phone');
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
    .ot-call { display: flex; align-items: center; justify-content: center; gap: .5rem; margin-top: .6rem; padding: .55rem .8rem; border-radius: .85rem; font-size: .82rem; color: #374151; background: #f0fdf4; border: 1px dashed #86efac; }
    .ot-call a { font-weight: 800; color: #15803d; white-space: nowrap; }
    .dark .ot-item { background: #182032; border-color: #263044; }
    .dark .ot-item .ot-t { color: #f3f4f6; }
    .dark .ot-item .ot-d { color: #9ca3af; }
    .dark .ot-call { background: rgba(22, 163, 74, .12); border-color: rgba(134, 239, 172, .35); color: #d1d5db; }
    .dark .ot-call a { color: #4ade80; }
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
    @if($otPhone && ($showCall ?? true))
        <div class="ot-call">
            <span aria-hidden="true">📞</span>
            <span>Prefer to order by phone? Call <a href="tel:{{ preg_replace('/[^0-9+]/', '', $otPhone) }}">{{ $otPhone }}</a></span>
        </div>
    @endif
</div>
