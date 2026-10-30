{{-- Dashboard → Recent orders rows (also returned by DashboardController::orders for the
     tabs and the live refresh). Tap a row for the quick view; the green button moves the
     order one step on (Confirm → Ship → Delivered). --}}
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $next = ['pending' => ['processing', '✓ Confirm'], 'processing' => ['shipped', '🚚 Ship'], 'shipped' => ['delivered', '🎉 Delivered']];
    $label = ['pending' => 'Pending', 'processing' => 'Confirmed', 'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled', 'refunded' => 'Refunded'];
@endphp
@forelse($orders as $order)
    @php
        $name = $order->shipping_name ?: ($order->user->name ?? 'Guest');
        $isNew = ($newSince ?? 0) && $order->id > $newSince;
        $created = $order->created_at->timezone($tz);
    @endphp
    <div class="do-row{{ $isNew ? ' is-new' : '' }}" data-id="{{ $order->id }}" @click="rowClick($event, {{ $order->id }})" :class="current === {{ $order->id }} && 'is-open'">
        <span class="do-av" style="background: hsl({{ crc32($name) % 360 }} 65% 52%)">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
        <div class="do-main">
            <p class="do-name">
                <span class="truncate">{{ $name }}</span>
                @if($isNew)<span class="do-tag" style="background:#f97316;color:#fff">NEW</span>@endif
                @if($order->is_preorder)<span class="do-tag" style="background:#7c3aed;color:#fff">PRE</span>@endif
                @if($order->is_fraud_flagged)<span class="do-tag" style="background:#fee2e2;color:#b91c1c" title="Fraud score {{ $order->fraud_score }}">⚠ Risk</span>@endif
            </p>
            <p class="do-meta">
                <span class="do-num">{{ $order->order_number }}</span>
                <span>· {{ $created->diffForHumans(null, true, true) }} ago</span>
                @if($order->shipping_phone)<span class="do-hide-s">· {{ $order->shipping_phone }}</span>@endif
            </p>
            <div class="do-thumbs">
                @foreach($order->items->take(4) as $it)
                    @if($it->product?->image)<img src="{{ image_thumb($it->product->image, 96) }}" alt="" loading="lazy" title="{{ $it->product_name }} × {{ $it->quantity }}">@else<span title="{{ $it->product_name }}">{{ mb_substr($it->product_name, 0, 1) }}</span>@endif
                @endforeach
                @if($order->items_count > 4)<span>+{{ $order->items_count - 4 }}</span>@endif
            </div>
        </div>
        <div class="do-side">
            <p class="do-total">৳{{ number_format($order->total) }}</p>
            <p class="do-pay">
                <span class="do-dot" style="background:{{ $order->payment_status === 'paid' ? '#10b981' : ($order->payment_status === 'failed' ? '#ef4444' : '#f59e0b') }}"></span>
                {{ $order->payment_status === 'paid' ? 'Paid' : \App\Services\Notifications\OrderEmail::paymentLabel($order->payment_method) }}
            </p>
            <span class="oq-st oq-st-{{ $order->status }}">{{ $label[$order->status] ?? ucfirst($order->status) }}</span>
        </div>
        <div class="do-acts">
            @isset($next[$order->status])
                <button type="button" class="do-go" @click="advance({{ $order->id }}, '{{ $next[$order->status][0] }}')" :disabled="busy === {{ $order->id }}"
                        title="Move to {{ $label[$next[$order->status][0]] }}">{{ $next[$order->status][1] }}</button>
            @endisset
            <button type="button" class="do-eye" @click="show({{ $order->id }})" title="Quick view" aria-label="Quick view {{ $order->order_number }}">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </button>
        </div>
    </div>
@empty
    <div class="do-empty">
        <p style="font-size:1.75rem">🎉</p>
        <p class="font-semibold text-gray-700 dark:text-gray-200">All clear here</p>
        <p class="text-sm text-gray-400">No orders in this list right now.</p>
    </div>
@endforelse
