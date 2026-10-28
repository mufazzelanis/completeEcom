<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Invoice {{ $order->order_number }}</title>
    @php
        // Bangla text (order notes, names, addresses) needs a font with real Bengali
        // glyph coverage — Hind Siliguri is registered in OrderController::invoice()
        // before rendering. Two settings let admins re-theme the invoice to match
        // their brand without editing this template.
        $accentColor = setting('invoice_accent_color', '#6366f1');
        $darkColor = setting('invoice_dark_color', '#111827');
    @endphp
    @include('admin.invoices._pdf-styles')
</head>

<body>
    @php
        // Hind Siliguri (registered in OrderController::invoice()) has a real glyph
        // for the Bengali Taka sign (৳), so it no longer needs to be spelled out as "Tk".
        $currencySymbol = setting('currency_symbol', '৳');
        $symbolPosition = setting('currency_position', 'left');
        $money = function ($amount) use ($currencySymbol, $symbolPosition) {
            $formatted = number_format((float) $amount, 2);
            return $symbolPosition === 'right' ? $formatted . ' ' . $currencySymbol : $currencySymbol . $formatted;
        };

$storeName = setting('company_name') ?: setting('site_name', 'ShopVista');
$storeEmail = setting('company_email') ?: 'support@shopvista.com';
// No placeholder fallback for the phone — unlike email/address, a fake "+880 1700-000000"
// on a real invoice reads as a real (wrong) contact number, not obviously a placeholder.
// Left blank in Settings -> General -> Company Phone, the line is simply omitted.
$storePhone = setting('company_phone');
$storeAddress = setting('company_address') ?: 'Dhaka, Bangladesh';
$footerText = setting('invoice_footer_text', 'Thank you for shopping with us!');
$invoiceTerms = setting('invoice_terms');

// Invoice numbers are sequential and derived from the order id, so re-downloading
// the same order's invoice always yields the same number without a separate
// counter column — starting point and prefix are admin-configurable.
$invoicePrefix = setting('invoice_prefix', 'INV-');
$invoiceStartNumber = (int) setting('invoice_start_number', 1000);
$invoiceNumber = $invoicePrefix . str_pad($invoiceStartNumber + $order->id - 1, 6, '0', STR_PAD_LEFT);

$invoiceDueDays = (int) setting('invoice_due_days', 0);
$dueDate = $invoiceDueDays > 0 ? $order->created_at->copy()->addDays($invoiceDueDays)->format('d F Y') : null;

$logoPath = setting('invoice_logo');
$logoAbsolutePath = null;
if ($logoPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($logoPath)) {
    $logoAbsolutePath = \Illuminate\Support\Facades\Storage::disk('public')->path($logoPath);
        }

$invoiceTitle = setting('invoice_title') ?: 'INVOICE';
$taxNumber = setting('invoice_tax_number');
$bankDetails = setting('invoice_bank_details');
$showSku = setting('invoice_show_sku', '1') == '1';
$showWatermark = setting('invoice_show_watermark', '1') == '1' && in_array($order->status, ['cancelled', 'refunded']);
$showWords = setting('invoice_show_words', '1') == '1';
$currencyWordName = match (setting('currency_code', 'BDT')) {
    'BDT' => 'Taka', 'USD' => 'Dollars', 'INR' => 'Rupees', 'EUR' => 'Euros', 'GBP' => 'Pounds',
    default => setting('currency_code', 'BDT'),
};
$trackingQr ??= null; // passed in by OrderController::invoice(); null when disabled or generation failed

$signaturePath = setting('invoice_signature');
$signatureAbsolutePath = null;
if ($signaturePath && \Illuminate\Support\Facades\Storage::disk('public')->exists($signaturePath)) {
    $signatureAbsolutePath = \Illuminate\Support\Facades\Storage::disk('public')->path($signaturePath);
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
                        @if($storePhone)
                            {{ $storePhone }}<br>
                        @endif
                        {{ $storeAddress }}
                        @if($taxNumber)
                            <br>{{ $taxNumber }}
                        @endif
                    </div>
                </td>
                <td class="invoice-title">
                    <h1>{{ $invoiceTitle }}</h1>
                    <div class="inv-number">{{ $invoiceNumber }}</div>
                    <div style="margin-top:6px;">
                        <span class="badge badge-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
                    </div>
                    <div class="inv-meta">
                        Issued: {{ $order->created_at->format('d F Y') }}
                        @if($dueDate)
                            <br>Due: {{ $dueDate }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <p class="greeting">Dear <strong>{{ $order->shipping_name }}</strong>, thank you for shopping with {{ $storeName }} — here are the details of your order.</p>

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
                <td>
                    <div class="info-box">
                        <h4>Order Details</h4>
                        <p>
                            <span class="row-label">Order Ref:</span> {{ $order->order_number }}<br>
                            <span class="row-label">Payment Method:</span>
                            {{ ucfirst(str_replace('_', ' ', $order->payment_method)) }}<br>
                            <span class="row-label">Payment Status:</span>
                            <span class="{{ $order->payment_status === 'paid' ? 'paid-yes' : 'paid-no' }}"
                                style="font-weight:700;">
                                {{ ucfirst($order->payment_status) }}
                            </span>
                        </p>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <div class="items-wrap">
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
                            @if ($item->variant_label)
                                <div class="prod-sku">{{ $item->variant_label }}</div>
                            @endif
                            @if ($showSku && $item->product?->sku)
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
        </div>

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

        @if ($showWords)
            <p class="words-row">In words: <strong>{{ amount_in_words((float) $order->total) }} {{ $currencyWordName }} Only</strong></p>
        @endif

        @php
            // Side-by-side instead of stacked whenever more than one of these is present —
            // with every optional box switched on at once this is the difference between
            // fitting on one page and spilling a couple of lines onto an almost-empty second
            // one. white-space:pre-line (see .note-box p) keeps each box's own line breaks.
            $noteBoxes = array_filter([
                $order->notes ? ['title' => 'Order Notes', 'body' => $order->notes] : null,
                $invoiceTerms ? ['title' => 'Terms &amp; Conditions', 'body' => $invoiceTerms] : null,
                $bankDetails ? ['title' => 'Bank / Payment Details', 'body' => $bankDetails] : null,
            ]);
        @endphp
        @if ($noteBoxes)
            <table style="width:100%; margin-top:6px; border-collapse:separate; border-spacing:8px 0;">
                <tr>
                    @foreach ($noteBoxes as $box)
                        <td style="width:{{ (int) (100 / count($noteBoxes)) }}%; vertical-align:top; padding:0;">
                            <div class="note-box" style="margin-top:0;">
                                <h4>{!! $box['title'] !!}</h4>
                                <p>{{ $box['body'] }}</p>
                            </div>
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif

        @if ($trackingQr || $signatureAbsolutePath)
            <table class="bottom-strip">
                <tr>
                    <td style="text-align:left;">
                        @if ($trackingQr)
                            <img src="{{ $trackingQr }}" class="qr-code" alt="QR code">
                            <div class="qr-caption">Scan to track this order online</div>
                        @endif
                    </td>
                    <td style="width:200px; text-align:center;">
                        @if ($signatureAbsolutePath)
                            <img src="{{ $signatureAbsolutePath }}" class="signature-img" alt="Signature">
                            <div class="signature-line">Authorized Signature</div>
                        @endif
                    </td>
                </tr>
            </table>
        @endif

        {{-- Footer --}}
        <div class="footer">
            <p>
                {{ $footerText }}<br>
                For questions about this invoice, contact us at <strong>{{ $storeEmail }}</strong><br>
                @if ($signatureAbsolutePath)
                    This is a computer-generated invoice.
                @else
                    This is a computer-generated invoice and does not require a signature.
                @endif
            </p>
        </div>

        @if ($showWatermark)
            <div class="watermark">{{ strtoupper($order->status) }}</div>
        @endif

    </div>
</body>

</html>
