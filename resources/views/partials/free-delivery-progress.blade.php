{{-- "Add ৳X more for free delivery" — only when Settings → Shipping → Free shipping is on.
     Same threshold ShippingCalculator applies (cart subtotal before discounts). --}}
@php
    $fdOn = setting('free_shipping_enabled', '0') === '1';
    $fdMin = (float) setting('free_shipping_min', 999);
    $fdSub = (float) ($subtotal ?? 0);
@endphp
@if($fdOn && $fdMin > 0)
    @php $fdLeft = max(0, $fdMin - $fdSub); $fdPct = min(100, round($fdSub / $fdMin * 100)); @endphp
    <div class="{{ $class ?? '' }}" style="border-radius: .9rem; padding: .7rem .85rem; background: {{ $fdLeft > 0 ? 'rgba(99,102,241,.08)' : 'rgba(16,185,129,.1)' }}; border: 1px solid {{ $fdLeft > 0 ? 'rgba(99,102,241,.25)' : 'rgba(16,185,129,.3)' }};">
        <p class="text-sm text-gray-700 dark:text-gray-200">
            @if($fdLeft > 0)
                🚚 Add <strong style="color:#4f46e5;">৳{{ number_format($fdLeft) }}</strong> more for <strong>free delivery</strong>
            @else
                🎉 You've unlocked <strong style="color:#059669;">free delivery!</strong>
            @endif
        </p>
        <div style="margin-top: .45rem; height: .45rem; border-radius: 999px; background: rgba(148,163,184,.25); overflow: hidden;" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $fdPct }}" aria-label="Progress to free delivery">
            <div style="height: 100%; width: {{ $fdPct }}%; border-radius: 999px; background: {{ $fdLeft > 0 ? 'linear-gradient(90deg,#6366f1,#8b5cf6)' : 'linear-gradient(90deg,#10b981,#34d399)' }}; transition: width .5s ease;"></div>
        </div>
        @if($fdLeft > 0 && ($showLink ?? true))
            <a href="{{ route('shop.index') }}" class="inline-block mt-1.5 text-xs font-semibold hover:underline" style="color:#4f46e5;">Add more items →</a>
        @endif
    </div>
@endif
