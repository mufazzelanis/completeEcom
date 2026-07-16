<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice {{ $order->order_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12.5px;
            color: #1f2937;
            background: #fff;
        }

        .page {
            padding: 36px 42px;
        }

        /* Top accent bar */
        .accent-bar {
            height: 6px;
            background-color: #6366f1;
            border-radius: 3px;
            margin-bottom: 28px;
        }

        /* Header */
        .header-table {
            width: 100%;
            margin-bottom: 28px;
        }

        .header-table td {
            vertical-align: top;
            padding: 0;
        }

        .brand-logo {
            max-height: 46px;
            max-width: 190px;
            margin-bottom: 6px;
        }

        .brand {
            font-family: DejaVu Sans, sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: #4338ca;
        }

        .brand-sub {
            font-size: 10.5px;
            color: #6b7280;
            margin-top: 2px;
        }

        .brand-contact {
            margin-top: 10px;
            font-size: 10.5px;
            color: #6b7280;
            line-height: 1.7;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-family: DejaVu Sans, sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #111827;
            letter-spacing: 3px;
            line-height: 1;
        }

        .invoice-title .inv-number {
            font-size: 12px;
            color: #6366f1;
            font-weight: 700;
            margin-top: 6px;
        }

        .invoice-title .inv-meta {
            margin-top: 8px;
            font-size: 10.5px;
            color: #6b7280;
            line-height: 1.7;
        }

        /* Status badge */
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-processing {
            background: #dbeafe;
            color: #1e40af;
        }

        .badge-shipped {
            background: #ede9fe;
            color: #5b21b6;
        }

        .badge-delivered {
            background: #d1fae5;
            color: #065f46;
        }

        .badge-cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-refunded {
            background: #f3f4f6;
            color: #374151;
        }

        .paid-yes {
            color: #059669;
        }

        .paid-no {
            color: #dc2626;
        }

        /* Divider */
        .divider {
            border-top: 1px solid #e5e7eb;
            margin-bottom: 28px;
        }

        /* Info grid */
        .info-grid {
            width: 100%;
            margin-bottom: 30px;
            border-collapse: separate;
            border-spacing: 9px 0;
        }

        .info-grid td {
            vertical-align: top;
            width: 50%;
            padding: 0;
        }

        .info-box {
            background: #f9fafb;
            border-radius: 10px;
            padding: 14px 16px;
        }

        .info-box h4 {
            font-size: 9.5px;
            font-weight: 700;
            color: #6366f1;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        .info-box p {
            font-size: 11.5px;
            color: #374151;
            line-height: 1.7;
        }

        .info-box .val {
            font-weight: 700;
            color: #111827;
        }

        .info-box .row-label {
            color: #9ca3af;
        }

        /* Items table */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        .items-table {
            margin-bottom: 20px;
        }

        .items-table thead th {
            background: #111827;
            color: #fff;
            padding: 11px 14px;
            font-size: 10.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table thead th:first-child {
            border-radius: 8px 0 0 8px;
        }

        .items-table thead th:last-child {
            border-radius: 0 8px 8px 0;
        }

        .items-table tbody tr {
            border-bottom: 1px solid #f3f4f6;
        }

        .items-table tbody tr:last-child {
            border-bottom: none;
        }

        .items-table tbody td {
            padding: 11px 14px;
            font-size: 11.5px;
            vertical-align: top;
        }

        .items-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }

        .items-table .prod-name {
            font-weight: 700;
            color: #111827;
        }

        .items-table .prod-sku {
            font-size: 9.5px;
            color: #9ca3af;
            margin-top: 2px;
        }

        .items-table .text-right {
            text-align: right;
        }

        .items-table .text-center {
            text-align: center;
        }

        /* Totals */
        .clearfix::after {
            content: '';
            display: table;
            clear: both;
        }

        .totals {
            float: right;
            width: 280px;
        }

        .totals table {
            width: 100%;
        }

        .totals td {
            padding: 7px 0;
            font-size: 11.5px;
        }

        .totals .label {
            color: #6b7280;
        }

        .totals .amount {
            text-align: right;
            font-weight: 600;
            color: #111827;
        }

        .totals .discount-amount {
            color: #dc2626;
        }

        .totals .separator td {
            border-top: 1px solid #e5e7eb;
            padding: 0;
        }

        .totals .total-row td {
            font-size: 15px;
            font-weight: 800;
            padding: 12px 14px;
        }

        .totals .total-row {
            background: #111827;
            color: #fff;
        }

        .totals .total-row .label {
            color: #d1d5db;
            font-weight: 700;
        }

        .totals .total-row .amount {
            color: #fff;
        }

        /* Notes / Terms */
        .note-box {
            margin-top: 14px;
            padding: 14px 16px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid #6366f1;
        }

        .note-box h4 {
            font-size: 10px;
            font-weight: 700;
            color: #6366f1;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .note-box p {
            font-size: 10.5px;
            color: #6b7280;
            line-height: 1.6;
            white-space: pre-line;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .footer p {
            font-size: 10px;
            color: #9ca3af;
            line-height: 1.8;
        }

        .footer strong {
            color: #6366f1;
        }
    </style>
</head>

<body>
    @php
        // dompdf's bundled DejaVu Sans font has no glyph for the Bengali Taka sign (৳);
// it renders as a blank box, so the PDF always spells it out as "Tk" instead.
$currencySymbol = setting('currency_symbol', '৳');
$pdfSymbol = $currencySymbol === '৳' ? 'Tk ' : $currencySymbol;
$symbolPosition = setting('currency_position', 'left');
$money = function ($amount) use ($pdfSymbol, $symbolPosition) {
    $formatted = number_format((float) $amount, 2);
    return $symbolPosition === 'right' ? $formatted . ' ' . trim($pdfSymbol) : $pdfSymbol . $formatted;
};

$storeName = setting('company_name') ?: setting('site_name', 'ShopVista');
$storeEmail = setting('company_email') ?: 'support@shopvista.com';
$storePhone = setting('company_phone') ?: '+880 1700-000000';
$storeAddress = setting('company_address') ?: 'Dhaka, Bangladesh';
$footerText = setting('invoice_footer_text', 'Thank you for shopping with us!');
$invoiceTerms = setting('invoice_terms');

$logoPath = setting('invoice_logo');
$logoAbsolutePath = null;
if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
    $logoAbsolutePath = \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath);
        }
    @endphp
    <div class="page">

        <div class="accent-bar"></div>

        {{-- Header --}}
        <table class="header-table">
            <tr>
                <td>
                    @if ($logoAbsolutePath)
                        <img src="{{ $logoAbsolutePath }}" class="brand-logo" alt="{{ $storeName }}">
                    @else
                        <div class="brand">{{ $storeName }}</div>
                    @endif
                    <div class="brand-contact">
                        {{ $storeEmail }}<br>
                        {{ $storePhone }}<br>
                        {{ $storeAddress }}
                    </div>
                </td>
                <td class="invoice-title">
                    <h1>INVOICE</h1>
                    <div class="inv-number">#{{ $order->order_number }}</div>
                    <span class="row-label">Payment:</span>
                    {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}<br>
                    <div style="margin-top:10px;">
                        <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                    </div>
                    <div class="inv-meta">
                        Issued: {{ $order->created_at->format('d F Y') }}
                    </div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        {{-- Ship To / Order Info --}}
        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-box">
                        <h4>Ship To</h4>
                        <p>
                            <span class="val">{{ $order->shipping_name }}</span><br>
                            {{ $order->shipping_address }}<br>
                            {{ $order->shipping_city }}@if ($order->shipping_state)
                                , {{ $order->shipping_state }}
                                @endif @if ($order->shipping_zip)
                                    {{ $order->shipping_zip }}
                                @endif
                                <br>
                                {{ $order->shipping_country }}<br>
                                {{ $order->shipping_phone }}
                        </p>
                    </div>
                </td>
                {{-- <td>
                    <div class="info-box">
                        <h4>Order Details</h4>
                        <p>

                            <span class="row-label">Payment:</span>
                            {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}<br>
                            <span class="row-label">Payment Status:</span>
                            <span class="{{ $order->payment_status === 'paid' ? 'paid-yes' : 'paid-no' }}"
                                style="font-weight:700;">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </p>
                    </div> --}}
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <table class="items-table">
            <thead>
                <tr>
                    <th style="text-align:left; width:5%">#</th>
                    <th style="text-align:left;">Product</th>
                    <th class="text-center" style="width:10%">Qty</th>
                    <th class="text-right" style="width:17%">Unit Price</th>
                    <th class="text-right" style="width:17%">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="prod-name">{{ $item->product_name }}</div>
                            @if ($item->product?->sku)
                                <div class="prod-sku">SKU: {{ $item->product->sku }}</div>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">{{ $money($item->price) }}</td>
                        <td class="text-right" style="font-weight:700;">{{ $money($item->subtotal) }}</td>
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
                        <td class="amount">{{ $money($order->subtotal) }}</td>
                    </tr>
                    @if ($order->discount > 0)
                        <tr>
                            <td class="label">Discount{{ $order->coupon_code ? ' (' . $order->coupon_code . ')' : '' }}
                            </td>
                            <td class="amount discount-amount">-{{ $money($order->discount) }}</td>
                        </tr>
                    @endif
                    @if ($order->shipping > 0)
                        <tr>
                            <td class="label">Shipping</td>
                            <td class="amount">{{ $money($order->shipping) }}</td>
                        </tr>
                    @endif
                    @if ($order->tax > 0)
                        <tr>
                            <td class="label">Tax</td>
                            <td class="amount">{{ $money($order->tax) }}</td>
                        </tr>
                    @endif
                    <tr class="separator">
                        <td colspan="2"></td>
                    </tr>
                    <tr class="total-row">
                        <td class="label">Total</td>
                        <td class="amount">{{ $money($order->total) }}</td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="clearfix"></div>

        @if ($order->notes)
            <div class="note-box">
                <h4>Order Notes</h4>
                <p>{{ $order->notes }}</p>
            </div>
        @endif

        @if ($invoiceTerms)
            <div class="note-box">
                <h4>Terms &amp; Conditions</h4>
                <p>{{ $invoiceTerms }}</p>
            </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ $footerText }}<br>
                For questions about this invoice, contact us at <strong>{{ $storeEmail }}</strong><br>
                This is a computer-generated invoice and does not require a signature.
            </p>
        </div>

    </div>
</body>

</html>
