{{-- Order email body (OrderEmail::body) — goes inside emails.layouts.branded. Email-safe:
     tables and inline styles only. --}}
@php
    $muted = '#6b7280';
    $line = '#eef0f4';
    $addressLines = collect([$order->shipping_address, collect([$order->shipping_city, $order->shipping_state, $order->shipping_zip])->filter()->unique()->implode(', ')])
        ->filter()->unique(fn ($l) => mb_strtolower(trim($l)));
    $isCod = in_array(strtolower((string) $order->payment_method), ['cod', 'cash_on_delivery'], true);
    $paymentState = match (true) {
        $order->payment_status === 'paid' => ['Paid', '#059669', '#ecfdf5'],
        $order->payment_status === 'refunded' => ['Refunded', '#0891b2', '#ecfeff'],
        $order->status === 'cancelled' => ['Not charged', '#6b7280', '#f3f4f6'],
        $isCod => ['Pay on delivery', '#b45309', '#fffbeb'],
        default => [ucfirst((string) $order->payment_status ?: 'Unpaid'), '#b45309', '#fffbeb'],
    };
@endphp

{{-- headline --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center" style="padding:0 0 22px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" valign="middle" width="64" height="64" style="width:64px; height:64px; border-radius:32px; background-color:{{ $color }}1a; font-size:30px; line-height:64px; text-align:center;">{{ $icon }}</td>
                </tr>
            </table>
            <h1 style="margin:16px 0 6px; font-size:24px; line-height:1.3; font-weight:800; color:#111827; letter-spacing:-.3px;">{{ $title }}</h1>
            <p style="margin:0; font-size:13px; color:{{ $muted }};">
                Order <strong style="color:#111827;">#{{ $order->order_number }}</strong>
                · {{ $order->created_at?->timezone(setting('timezone', 'Asia/Dhaka'))->format('d M Y') }}
            </p>
        </td>
    </tr>
</table>

{{-- the editable intro (Admin → Notifications → Templates) --}}
<div style="font-size:15px; line-height:1.65; color:#374151;">{!! $intro !!}</div>

{{-- progress --}}
@if($showTracker)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:22px 0 4px;">
        <tr>
            @foreach(\App\Services\Notifications\OrderEmail::STEPS as $i => $label)
                @php $done = $i <= $step; $current = $i === $step; @endphp
                <td align="center" valign="top" width="25%" style="width:25%; padding:0 2px;">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="height:6px; line-height:6px; font-size:0; border-radius:3px; background-color:{{ $done ? $color : '#e5e7eb' }};">&nbsp;</td>
                        </tr>
                    </table>
                    <p style="margin:8px 0 0; font-size:12px; line-height:1.3; font-weight:{{ $current ? '700' : '600' }}; color:{{ $done ? ($current ? $color : '#111827') : '#9ca3af' }};">
                        {{ $done && !$current ? '✓ ' : '' }}{{ $label }}
                    </p>
                </td>
            @endforeach
        </tr>
    </table>
@elseif(!$isAdmin)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:18px 0 4px;">
        <tr>
            <td style="padding:12px 16px; border-radius:12px; background-color:{{ $color }}12; border-left:4px solid {{ $color }}; font-size:13px; line-height:1.55; color:#374151;">
                <strong style="color:{{ $color }};">{{ ucfirst($order->status) }}</strong> — this order won't be delivered{{ $order->status === 'refunded' ? ' and your money is on its way back' : '' }}.
            </td>
        </tr>
    </table>
@endif

{{-- button --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center" style="padding:26px 0 8px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td align="center" bgcolor="{{ $color }}" style="border-radius:12px; background-color:{{ $color }};">
                        <a href="{{ $ctaUrl }}" target="_blank" style="display:inline-block; padding:14px 30px; font-size:15px; font-weight:700; color:#ffffff; text-decoration:none; border-radius:12px;">{{ $ctaLabel }} &rarr;</a>
                    </td>
                </tr>
            </table>
            @if(!$isAdmin && in_array($stage, ['delivered', 'cancelled', 'refunded'], true))
                <p style="margin:12px 0 0; font-size:13px;"><a href="{{ url('/shop') }}" style="color:{{ $color }}; text-decoration:none; font-weight:600;">Continue shopping</a></p>
            @endif
        </td>
    </tr>
</table>

{{-- order summary --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:26px; border:1px solid {{ $line }}; border-radius:14px;">
    <tr>
        <td style="padding:14px 18px; border-bottom:1px solid {{ $line }}; background-color:#f9fafb; border-radius:14px 14px 0 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="font-size:13px; font-weight:700; color:#111827; text-transform:uppercase; letter-spacing:.6px;">Order summary</td>
                    <td align="right" style="font-size:12px; color:{{ $muted }};">{{ $order->items->sum('quantity') }} {{ \Illuminate\Support\Str::plural('item', $order->items->sum('quantity')) }}</td>
                </tr>
            </table>
        </td>
    </tr>
    @foreach($order->items as $item)
        @php $img = $item->product?->image ? image_thumb($item->product->image, 200) : null; @endphp
        <tr>
            <td style="padding:14px 18px; border-bottom:1px solid {{ $line }};">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                    <tr>
                        <td width="60" valign="top" style="width:60px; padding-right:14px;">
                            @if($img)
                                <img src="{{ $img }}" alt="" width="60" height="60" style="width:60px; height:60px; border-radius:10px; object-fit:contain; background-color:#f3f4f6; display:block;">
                            @else
                                <div style="width:60px; height:60px; border-radius:10px; background-color:#f3f4f6;"></div>
                            @endif
                        </td>
                        <td valign="top" style="font-size:14px; line-height:1.45; color:#111827;">
                            <strong style="font-weight:600;">{{ $item->product_name }}</strong>
                            @if($item->variant_label)<br><span style="font-size:12px; color:{{ $muted }};">{{ $item->variant_label }}</span>@endif
                            <br><span style="font-size:12px; color:{{ $muted }};">Qty {{ $item->quantity }} × {{ $money($item->price) }}</span>
                        </td>
                        <td valign="top" align="right" style="font-size:14px; font-weight:700; color:#111827; white-space:nowrap; padding-left:10px;">{{ $money($item->subtotal) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    @endforeach
    <tr>
        <td style="padding:14px 18px 6px;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px; color:#4b5563;">
                <tr><td style="padding:3px 0;">Subtotal</td><td align="right" style="padding:3px 0;">{{ $money($order->subtotal) }}</td></tr>
                @if($order->discount > 0)
                    <tr><td style="padding:3px 0; color:#dc2626;">Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</td><td align="right" style="padding:3px 0; color:#dc2626;">−{{ $money($order->discount) }}</td></tr>
                @endif
                <tr><td style="padding:3px 0;">Delivery</td><td align="right" style="padding:3px 0;">{{ $order->shipping > 0 ? $money($order->shipping) : 'Free' }}</td></tr>
                @if($order->tax > 0)
                    <tr><td style="padding:3px 0;">Tax</td><td align="right" style="padding:3px 0;">{{ $money($order->tax) }}</td></tr>
                @endif
                <tr>
                    <td style="padding:12px 0 8px; border-top:1px solid {{ $line }}; font-size:16px; font-weight:800; color:#111827;">Total</td>
                    <td align="right" style="padding:12px 0 8px; border-top:1px solid {{ $line }}; font-size:18px; font-weight:800; color:{{ $color }};">{{ $money($order->total) }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>

{{-- delivery + payment --}}
<table role="presentation" class="em-stack" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:16px;">
    <tr>
        <td valign="top" width="50%" style="width:50%; padding-right:8px;">
            <div style="border:1px solid {{ $line }}; border-radius:14px; padding:14px 18px; font-size:13px; line-height:1.6; color:#4b5563;">
                <p style="margin:0 0 6px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:{{ $muted }};">Delivery address</p>
                <strong style="color:#111827;">{{ $order->shipping_name }}</strong><br>
                @foreach($addressLines as $l){{ $l }}<br>@endforeach
                @if($order->shipping_phone){{ $order->shipping_phone }}@endif
            </div>
        </td>
        <td valign="top" width="50%" style="width:50%; padding-left:8px;">
            <div style="border:1px solid {{ $line }}; border-radius:14px; padding:14px 18px; font-size:13px; line-height:1.6; color:#4b5563;">
                <p style="margin:0 0 6px; font-size:11px; font-weight:700; letter-spacing:.8px; text-transform:uppercase; color:{{ $muted }};">Payment</p>
                <strong style="color:#111827;">{{ \App\Services\Notifications\OrderEmail::paymentLabel($order->payment_method) }}</strong><br>
                <span style="display:inline-block; margin-top:6px; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:700; color:{{ $paymentState[1] }}; background-color:{{ $paymentState[2] }};">{{ $paymentState[0] }}</span>
                @if(!$isAdmin && $isCod && $order->payment_status !== 'paid' && !in_array($order->status, ['cancelled', 'refunded'], true))
                    <br><span style="font-size:12px;">Please keep <strong style="color:#111827;">{{ $money($order->total) }}</strong> ready for the rider.</span>
                @endif
            </div>
        </td>
    </tr>
</table>

@if($isAdmin && $order->notes)
    <p style="margin:16px 0 0; padding:12px 16px; border-radius:12px; background-color:#fffbeb; font-size:13px; color:#92400e;"><strong>Customer note:</strong> {{ $order->notes }}</p>
@endif
