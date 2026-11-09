<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->typeMeta()[1] }} {{ $invoice->number }}</title>
    @include('admin.invoices._pdf-styles')
</head>
<body>
@php
    [$docTitle, $docLabel] = $invoice->typeMeta();
    [$statusLabel, $statusColor] = $invoice->statusMeta();
    $isQuote = in_array($invoice->type, ['quotation', 'proforma'], true);
    $hasLineDiscount = collect($invoice->items)->contains(fn ($i) => ($i['discount'] ?? 0) > 0);
    $hasPhotos = !empty($photos);
    $stamp = match (true) {
        $invoice->status === 'cancelled' => 'CANCELLED',
        $invoice->status === 'paid' && !$isQuote => 'PAID',
        $invoice->status === 'draft' => 'DRAFT',
        default => null,
    };
    $trim = fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
@endphp

@include('admin.invoices._pdf-band', ['title' => $docTitle, 'number' => $invoice->number])

@if ($stamp)
    <watermarktext content="{{ $stamp }}" alpha="0.08" />
@endif

<table class="facts">
    <tr>
        <td class="first" style="width:25%;"><div class="label">Date</div><div class="fact-value">{{ $invoice->issue_date->format('d M Y') }}</div></td>
        <td style="width:25%;"><div class="label">{{ $isQuote ? 'Valid Until' : 'Due Date' }}</div><div class="fact-value">{{ $invoice->due_date?->format('d M Y') ?: '—' }}</div></td>
        <td style="width:25%;"><div class="label">Payment Method</div><div class="fact-value">{{ $invoice->payment_method ?: '—' }}</div></td>
        <td style="width:25%;"><div class="label">Status</div><div class="fact-value" style="color:{{ darken_for_white_text($statusColor) }};">{{ $statusLabel }}</div></td>
    </tr>
</table>

<table class="parties">
    <tr>
        <td class="card">
            <div class="label">{{ $isQuote ? 'Prepared For' : 'Bill To' }}</div>
            <div class="party-name">{{ $invoice->customer_name }}</div>
            @if ($invoice->customer_phone)<div class="party-line" style="font-weight:bold; color:#111827;">{{ $invoice->customer_phone }}</div>@endif
            @if ($invoice->customer_address)<div class="party-line">{!! nl2br(e($invoice->customer_address)) !!}</div>@endif
            @if ($invoice->customer_email)<div class="party-line">{{ $invoice->customer_email }}</div>@endif
        </td>
        <td class="gap"></td>
        <td class="card">
            <div class="label">From</div>
            <div class="party-name">{{ $store['name'] }}</div>
            @if ($store['phone'])<div class="party-line" style="font-weight:bold; color:#111827;">{{ $store['phone'] }}</div>@endif
            <div class="party-line">{{ $store['address'] }}</div>
            @if ($store['email'])<div class="party-line">{{ $store['email'] }}</div>@endif
            @if ($store['tax'])<div class="party-line">{{ $store['tax'] }}</div>@endif
        </td>
    </tr>
</table>

<table class="items">
    <thead>
        <tr>
            <th style="width:7%;" class="c">#</th>
            @if ($hasPhotos)<th style="width:9%;"></th>@endif
            <th>Description</th>
            <th style="width:8%; text-align:center;">Qty</th>
            <th style="width:15%; text-align:right;">Unit Price</th>
            @if ($hasLineDiscount)<th style="width:12%; text-align:right;">Discount</th>@endif
            <th style="width:16%; text-align:right;">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($invoice->items as $i => $item)
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c">{{ $i + 1 }}</td>
                @if ($hasPhotos)
                    <td class="photo">@if (!empty($photos[$i]))<img src="{{ $photos[$i] }}" style="width:11mm; height:11mm;" alt="">@endif</td>
                @endif
                <td>
                    <div class="item-name">{{ $item['name'] }}</div>
                    @if (!empty($item['description']))<div class="item-sub">{{ $item['description'] }}</div>@endif
                </td>
                <td class="c">{{ $trim($item['qty']) }}</td>
                <td class="r">{{ $money($item['price']) }}</td>
                @if ($hasLineDiscount)<td class="r" style="color:#dc2626;">{{ ($item['discount'] ?? 0) > 0 ? '-' . $money($item['discount']) : '—' }}</td>@endif
                <td class="r" style="font-weight:bold; color:#111827;">{{ $money($item['total'] ?? 0) }}</td>
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
                    <div style="font-weight:bold; color:#111827;">{{ amount_in_words((float) $invoice->total) }} {{ $store['currencyWord'] }} Only</div>
                </td></tr></table>
            @endif
        </td>
        <td class="gap"></td>
        <td class="right">
            <table class="totals">
                <tr><td>Subtotal</td><td class="v">{{ $money($invoice->subtotal) }}</td></tr>
                @if ($invoice->discount > 0)
                    <tr><td>Discount{{ $invoice->discount_type === 'percent' ? ' (' . $trim($invoice->discount_value) . '%)' : '' }}</td><td class="v minus">-{{ $money($invoice->discount) }}</td></tr>
                @endif
                @if ($invoice->tax > 0)
                    <tr><td>Tax / VAT ({{ $trim($invoice->tax_rate) }}%)</td><td class="v">{{ $money($invoice->tax) }}</td></tr>
                @endif
                @if ($invoice->shipping > 0)
                    <tr><td>Delivery Charge</td><td class="v">{{ $money($invoice->shipping) }}</td></tr>
                @endif
                <tr class="grand"><td>Total</td><td style="text-align:right;">{{ $money($invoice->total) }}</td></tr>
                @if (!$isQuote && !in_array($invoice->status, ['draft', 'cancelled'], true))
                    @if ($invoice->paid_amount > 0)
                        <tr><td>Paid</td><td class="v">{{ $money($invoice->paid_amount) }}</td></tr>
                    @endif
                    @if ($invoice->due_amount > 0)
                        <tr class="due"><td>Balance Due</td><td style="text-align:right;">{{ $money($invoice->due_amount) }}</td></tr>
                    @else
                        <tr class="settled"><td>Paid in Full</td><td style="text-align:right;">{{ $money($invoice->total) }}</td></tr>
                    @endif
                @endif
            </table>
        </td>
    </tr>
</table>

@include('admin.invoices._pdf-closing', ['thanks' => ($isQuote ? 'Thank you for considering us, ' : 'Thank you for your business, ') . $invoice->customer_name . '!', 'qr' => null, 'boxes' => [
    $invoice->notes ? ['Notes', $invoice->notes] : null,
    $invoice->terms ? ['Terms & Conditions', $invoice->terms] : null,
    $store['bank'] && !$isQuote ? ['Bank / Payment Details', $store['bank']] : null,
]])
</body>
</html>
