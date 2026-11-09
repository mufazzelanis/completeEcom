<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice {{ $order->order_number }}</title>
    @include('admin.invoices._pdf-styles')
</head>
<body>
@php
    // Sequential and derived from the order id, so re-downloading always gives the same number.
    $invoiceNumber = setting('invoice_prefix', 'INV-') . str_pad((int) setting('invoice_start_number', 1000) + $order->id - 1, 6, '0', STR_PAD_LEFT);
    $dueDays = (int) setting('invoice_due_days', 0);
    $showSku = setting('invoice_show_sku', '1') == '1';
    $stopped = in_array($order->status, ['cancelled', 'refunded'], true);

    $paymentName = \App\Models\PaymentMethod::where('slug', $order->payment_method)->value('name')
        ?: ($order->payment_method === 'cod' ? 'Cash on Delivery' : \Illuminate\Support\Str::headline((string) $order->payment_method));
    $isCod = $order->payment_method === 'cod';
    $isPaid = $order->payment_status === 'paid';
    $advance = $order->is_preorder ? (float) $order->advance_amount : 0;
    $balance = $isPaid && !$advance ? 0 : max(0, (float) $order->total - $advance);

    $steps = ['pending' => 'Order Placed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
    $reached = array_search($order->status, array_keys($steps), true);

    $addressLines = array_filter([
        $order->shipping_address,
        collect([$order->shipping_city, $order->shipping_state, $order->shipping_zip])->filter()->implode(', '),
        $order->shipping_country,
    ]);
    $customerEmail = $order->user?->email ?: $order->guest_email;
    $hasPhotos = !empty($photos);
    $transaction = $order->payment?->transaction_id;

    $trackingQr ??= null;
@endphp

@include('admin.invoices._pdf-band', ['title' => setting('invoice_title') ?: 'INVOICE', 'number' => $invoiceNumber])

@if ($stopped && setting('invoice_show_watermark', '1') == '1')
    <watermarktext content="{{ strtoupper($order->status) }}" alpha="0.09" />
@endif

<table class="facts">
    <tr>
        <td class="first" style="width:23%;"><div class="label">Invoice Date</div><div class="fact-value">{{ $order->created_at->format('d M Y') }}</div></td>
        <td style="width:29%;"><div class="label">Order No.</div><div class="fact-value">{{ $order->order_number }}</div></td>
        <td style="width:26%;"><div class="label">Payment Method</div><div class="fact-value">{{ $paymentName }}</div></td>
        <td style="width:22%;"><div class="label">Payment</div><div class="fact-value" style="color:{{ $isPaid ? '#047857' : '#b45309' }};">{{ ucfirst($order->payment_status) }}</div></td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td class="card">
            <div class="label">Deliver To</div>
            <div class="party-name">{{ $order->shipping_name }}</div>
            <div class="party-line" style="font-weight:bold; color:#111827;">{{ $order->shipping_phone }}</div>
            <div class="party-line">{!! implode('<br>', array_map('e', $addressLines)) !!}</div>
            @if ($customerEmail)<div class="party-line">{{ $customerEmail }}</div>@endif
        </td>
        <td class="gap"></td>
        <td class="card">
            <div class="label">Sold By</div>
            <div class="party-name">{{ $store['name'] }}</div>
            @if ($store['phone'])<div class="party-line" style="font-weight:bold; color:#111827;">{{ $store['phone'] }}</div>@endif
            <div class="party-line">{{ $store['address'] }}</div>
            @if ($store['email'])<div class="party-line">{{ $store['email'] }}</div>@endif
            @if ($store['tax'])<div class="party-line">{{ $store['tax'] }}</div>@endif
        </td>
    </tr>
</table>

@if ($stopped)
    <table class="alert"><tr><td>This order was {{ $order->status }}.</td></tr></table>
@elseif ($reached !== false)
    <table class="journey">
        <tr>
            @foreach (array_values($steps) as $i => $step)
                @if ($i > 0)<td class="gap"></td>@endif
                <td class="step {{ $i <= $reached ? 'step-done' : 'step-todo' }}" style="width:24%;">{{ $step }}</td>
            @endforeach
        </tr>
    </table>
@endif

<table class="items">
    <thead>
        <tr>
            <th style="width:7%;" class="c">#</th>
            @if ($hasPhotos)<th style="width:9%;"></th>@endif
            <th>Product</th>
            <th class="r" style="width:16%; text-align:right;">Unit Price</th>
            <th style="width:8%; text-align:center;">Qty</th>
            <th style="width:17%; text-align:right;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($order->items as $i => $item)
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c">{{ $i + 1 }}</td>
                @if ($hasPhotos)
                    <td class="photo">@if (!empty($photos[$item->id]))<img src="{{ $photos[$item->id] }}" style="width:11mm; height:11mm;" alt="">@endif</td>
                @endif
                <td>
                    <div class="item-name">{{ $item->product_name }}</div>
                    @php $sub = array_filter([$item->variant_label, $showSku && $item->product?->sku ? 'SKU: ' . $item->product->sku : null]); @endphp
                    @if ($sub)<div class="item-sub">{{ implode('  ·  ', $sub) }}</div>@endif
                </td>
                <td class="r">{{ $money($item->price) }}</td>
                <td class="c">{{ $item->quantity }}</td>
                <td class="r" style="font-weight:bold; color:#111827;">{{ $money($item->subtotal) }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table class="summary">
    <tr>
        <td class="left">
            @if ($store['showWords'])
                <table class="panel"><tr><td>
                    <div class="label">Amount in Words</div>
                    <div style="font-weight:bold; color:#111827;">{{ amount_in_words((float) $order->total) }} {{ $store['currencyWord'] }} Only</div>
                </td></tr></table>
            @endif
            <table class="panel"><tr><td>
                <div class="label">Payment</div>
                <div class="panel-big">{{ $paymentName }}</div>
                @if ($stopped)
                    <div class="small">No payment is due on this order.</div>
                @elseif ($isPaid && !$advance)
                    <div class="small">Payment received. Thank you.</div>
                @elseif ($isCod)
                    <div class="small">Please pay the delivery person when you receive your order.</div>
                @endif
                @if ($transaction)<div class="small">Transaction ID: <b>{{ $transaction }}</b></div>@endif
                @if ($dueDays > 0 && !$isPaid && !$stopped)<div class="small">Due by: <b>{{ $order->created_at->copy()->addDays($dueDays)->format('d M Y') }}</b></div>@endif
            </td></tr></table>
        </td>
        <td class="gap"></td>
        <td class="right">
            <table class="totals">
                <tr><td>Subtotal</td><td class="v">{{ $money($order->subtotal) }}</td></tr>
                @if ($order->discount > 0)
                    <tr><td>Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}</td><td class="v minus">-{{ $money($order->discount) }}</td></tr>
                @endif
                @if ($order->shipping > 0)
                    <tr><td>Delivery Charge</td><td class="v">{{ $money($order->shipping) }}</td></tr>
                @endif
                @if ($order->tax > 0)
                    <tr><td>Tax</td><td class="v">{{ $money($order->tax) }}</td></tr>
                @endif
                @if ($order->payment_charge > 0)
                    <tr><td>Payment Charge</td><td class="v">{{ $money($order->payment_charge) }}</td></tr>
                @endif
                <tr class="grand"><td>Total</td><td style="text-align:right;">{{ $money($order->total) }}</td></tr>
                @if ($advance > 0)
                    <tr><td>{{ $isPaid ? 'Advance Paid' : 'Advance to Pay' }}</td><td class="v">{{ $money($advance) }}</td></tr>
                @endif
                @unless ($stopped)
                    @if ($balance > 0)
                        <tr class="due"><td>{{ $isCod || $advance ? 'Collect on Delivery' : 'Amount Due' }}</td><td style="text-align:right;">{{ $money($balance) }}</td></tr>
                    @else
                        <tr class="settled"><td>Paid in Full</td><td style="text-align:right;">{{ $money($order->total) }}</td></tr>
                    @endif
                @endunless
            </table>
        </td>
    </tr>
</table>

@include('admin.invoices._pdf-closing', ['thanks' => $stopped ? 'Thank you for choosing ' . $store['name'] . '.' : 'Thank you for your order, ' . $order->shipping_name . '!', 'qr' => $stopped ? null : $trackingQr, 'boxes' => [
    $order->notes ? ['Order Notes', $order->notes] : null,
    setting('invoice_terms') ? ['Terms & Conditions', setting('invoice_terms')] : null,
    $store['bank'] ? ['Bank / Payment Details', $store['bank']] : null,
]])
</body>
</html>
