<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Invoice {{ $order->order_number }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #1f2937; background: #fff; }
    .page { padding: 40px; }

    /* Header */
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 40px; border-bottom: 3px solid #6366f1; padding-bottom: 24px; }
    .brand { font-size: 24px; font-weight: 700; color: #6366f1; }
    .brand-sub { font-size: 11px; color: #6b7280; margin-top: 2px; }
    .invoice-title { text-align: right; }
    .invoice-title h1 { font-size: 28px; font-weight: 800; color: #6366f1; letter-spacing: 2px; }
    .invoice-title p { font-size: 11px; color: #6b7280; margin-top: 3px; }

    /* Info grid */
    .info-grid { display: flex; justify-content: space-between; margin-bottom: 32px; gap: 20px; }
    .info-box { flex: 1; }
    .info-box h4 { font-size: 10px; font-weight: 700; color: #6366f1; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
    .info-box p { font-size: 12px; color: #374151; line-height: 1.6; }
    .info-box .val { font-weight: 600; }

    /* Status badge */
    .badge { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
    .badge-pending    { background: #fef3c7; color: #92400e; }
    .badge-processing { background: #dbeafe; color: #1e40af; }
    .badge-shipped    { background: #ede9fe; color: #5b21b6; }
    .badge-delivered  { background: #d1fae5; color: #065f46; }
    .badge-cancelled  { background: #fee2e2; color: #991b1b; }
    .badge-refunded   { background: #f3f4f6; color: #374151; }

    /* Items table */
    table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
    .items-table thead th { background: #6366f1; color: #fff; padding: 10px 14px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .items-table thead th:first-child { text-align: left; border-radius: 6px 0 0 6px; }
    .items-table thead th:last-child  { border-radius: 0 6px 6px 0; }
    .items-table tbody tr { border-bottom: 1px solid #f3f4f6; }
    .items-table tbody tr:last-child { border-bottom: none; }
    .items-table tbody td { padding: 10px 14px; font-size: 12px; }
    .items-table tbody tr:nth-child(even) { background: #f9fafb; }
    .items-table .text-right { text-align: right; }
    .items-table .text-center { text-align: center; }

    /* Totals */
    .totals { float: right; width: 280px; }
    .totals table { width: 100%; }
    .totals td { padding: 6px 10px; font-size: 12px; }
    .totals .label { color: #6b7280; }
    .totals .amount { text-align: right; font-weight: 600; }
    .totals .total-row { background: #6366f1; color: #fff; border-radius: 6px; }
    .totals .total-row td { font-size: 14px; font-weight: 700; padding: 10px 12px; }
    .totals .separator { border-top: 1px solid #e5e7eb; }

    /* Notes */
    .clearfix::after { content: ''; display: table; clear: both; }
    .notes { margin-top: 40px; padding: 16px; background: #f9fafb; border-radius: 8px; border-left: 4px solid #6366f1; }
    .notes h4 { font-size: 11px; font-weight: 700; color: #6366f1; margin-bottom: 6px; text-transform: uppercase; }
    .notes p { font-size: 11px; color: #6b7280; line-height: 1.5; }

    /* Footer */
    .footer { margin-top: 48px; padding-top: 20px; border-top: 1px solid #e5e7eb; text-align: center; }
    .footer p { font-size: 10px; color: #9ca3af; line-height: 1.8; }
    .footer strong { color: #6366f1; }
</style>
</head>
<body>
<div class="page">

    {{-- Header --}}
    <div class="header">
        <div>
            <div class="brand">ShopVista</div>
            <div class="brand-sub">Your one-stop online store</div>
            <div style="margin-top:10px; font-size:11px; color:#6b7280; line-height:1.6;">
                support@shopvista.com<br>
                +880 1700-000000<br>
                Dhaka, Bangladesh
            </div>
        </div>
        <div class="invoice-title">
            <h1>INVOICE</h1>
            <p># {{ $order->order_number }}</p>
            <div style="margin-top:12px;">
                <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
            </div>
            <p style="margin-top:10px; font-size:11px; color:#6b7280;">
                Issued: {{ $order->created_at->format('d F Y') }}
            </p>
        </div>
    </div>

    {{-- Bill To / Ship To / Order Info --}}
    <div class="info-grid">
        <div class="info-box">
            <h4>Bill To</h4>
            <p>
                <span class="val">{{ $order->user?->name ?? $order->shipping_name }}</span><br>
                {{ $order->user?->email }}<br>
                {{ $order->shipping_phone }}
            </p>
        </div>
        <div class="info-box">
            <h4>Ship To</h4>
            <p>
                {{ $order->shipping_name }}<br>
                {{ $order->shipping_address }}<br>
                {{ $order->shipping_city }}@if($order->shipping_state), {{ $order->shipping_state }}@endif @if($order->shipping_zip) {{ $order->shipping_zip }}@endif<br>
                {{ $order->shipping_country }}
            </p>
        </div>
        <div class="info-box">
            <h4>Order Details</h4>
            <p>
                <span style="color:#6b7280;">Order Number:</span> <span class="val">{{ $order->order_number }}</span><br>
                <span style="color:#6b7280;">Date:</span> {{ $order->created_at->format('d M Y') }}<br>
                <span style="color:#6b7280;">Payment:</span> {{ ucfirst(str_replace('_',' ',$order->payment_method)) }}<br>
                <span style="color:#6b7280;">Payment Status:</span>
                <span style="font-weight:600; color:{{ $order->payment_status === 'paid' ? '#059669' : '#dc2626' }}">
                    {{ ucfirst($order->payment_status) }}
                </span>
            </p>
        </div>
    </div>

    {{-- Items --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="text-align:left; width:5%">#</th>
                <th style="text-align:left;">Product</th>
                <th class="text-center" style="width:10%">Qty</th>
                <th class="text-right" style="width:15%">Unit Price</th>
                <th class="text-right" style="width:15%">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $i => $item)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>
                    <strong>{{ $item->product_name }}</strong>
                    @if($item->product?->sku)
                    <br><span style="font-size:10px; color:#9ca3af;">SKU: {{ $item->product->sku }}</span>
                    @endif
                </td>
                <td class="text-center">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->price, 2) }}</td>
                <td class="text-right"><strong>${{ number_format($item->subtotal, 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="clearfix">
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="amount">${{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->discount > 0)
                <tr>
                    <td class="label">Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td>
                    <td class="amount" style="color:#dc2626;">-${{ number_format($order->discount, 2) }}</td>
                </tr>
                @endif
                @if($order->shipping > 0)
                <tr>
                    <td class="label">Shipping</td>
                    <td class="amount">${{ number_format($order->shipping, 2) }}</td>
                </tr>
                @endif
                @if($order->tax > 0)
                <tr>
                    <td class="label">Tax</td>
                    <td class="amount">${{ number_format($order->tax, 2) }}</td>
                </tr>
                @endif
                <tr>
                    <td colspan="2" class="separator" style="padding:0;"></td>
                </tr>
                <tr class="total-row">
                    <td class="label" style="color:#fff;">Total</td>
                    <td class="amount" style="color:#fff;">${{ number_format($order->total, 2) }}</td>
                </tr>
            </table>
        </div>
    </div>

    @if($order->notes)
    <div class="notes">
        <h4>Order Notes</h4>
        <p>{{ $order->notes }}</p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="footer">
        <p>
            Thank you for shopping with <strong>ShopVista</strong>!<br>
            For questions about this invoice, contact us at <strong>support@shopvista.com</strong><br>
            This is a computer-generated invoice and does not require a signature.
        </p>
    </div>

</div>
</body>
</html>
