<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $invoice->typeMeta()[1] }} {{ $invoice->number }}</title>
    @php
        // Same look as order invoices (Settings → Invoice); an invoice can override the accent colour
        $accentColor = $invoice->accent_color ?: setting('invoice_accent_color', '#6366f1');
        $darkColor = setting('invoice_dark_color', '#111827');
    @endphp
    @include('admin.invoices._pdf-styles')
    <style>
        .items-table .num { text-align: right; white-space: nowrap; }
        .pay-strip { width: 100%; margin-top: 8px; border-collapse: separate; border-spacing: 8px 0; }
        .pay-strip td { padding: 0; vertical-align: top; }
        .pay-card { border-radius: 8px; padding: 6px 12px; }
        .pay-card .k { font-size: 9px; text-transform: uppercase; letter-spacing: .6px; }
        .pay-card .v { font-size: 13px; font-weight: 700; margin-top: 1px; }
        .stamp { position: fixed; top: 430px; left: 0; width: 100%; text-align: center; font-size: 86px; font-weight: 800; opacity: .10; letter-spacing: 10px; transform: rotate(-25deg); }
    </style>
</head>

<body>
    @php
        $currencySymbol = setting('currency_symbol', '৳');
        $symbolPosition = setting('currency_position', 'left');
        $money = function ($amount) use ($currencySymbol, $symbolPosition) {
            $formatted = number_format((float) $amount, 2);
            return $symbolPosition === 'right' ? $formatted . ' ' . $currencySymbol : $currencySymbol . $formatted;
        };

        $storeName = setting('company_name') ?: setting('site_name', 'ShopVista');
        $storeEmail = setting('company_email') ?: 'support@shopvista.com';
        $storePhone = setting('company_phone');
        $storeAddress = setting('company_address') ?: 'Dhaka, Bangladesh';
        $footerText = setting('invoice_footer_text', 'Thank you for shopping with us!');
        $taxNumber = setting('invoice_tax_number');
        $bankDetails = setting('invoice_bank_details');
        $showWords = setting('invoice_show_words', '1') == '1';
        $currencyWordName = match (setting('currency_code', 'BDT')) {
            'BDT' => 'Taka', 'USD' => 'Dollars', 'INR' => 'Rupees', 'EUR' => 'Euros', 'GBP' => 'Pounds',
            default => setting('currency_code', 'BDT'),
        };

        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        $logoPath = setting('invoice_logo');
        $logoAbsolutePath = $logoPath && $disk->exists($logoPath) ? $disk->path($logoPath) : null;
        $signaturePath = setting('invoice_signature');
        $signatureAbsolutePath = $signaturePath && $disk->exists($signaturePath) ? $disk->path($signaturePath) : null;

        [$docTitle, $docLabel] = $invoice->typeMeta();
        [$statusLabel, $statusColor] = $invoice->statusMeta();
        $isQuote = in_array($invoice->type, ['quotation', 'proforma'], true);
        $hasLineDiscount = collect($invoice->items)->contains(fn ($i) => ($i['discount'] ?? 0) > 0);
        $stamp = match (true) {
            $invoice->status === 'cancelled' => ['CANCELLED', '#dc2626'],
            $invoice->status === 'paid' && !$isQuote => ['PAID', '#059669'],
            $invoice->status === 'draft' => ['DRAFT', '#64748b'],
            default => null,
        };
    @endphp
    <div class="page">
        <div class="accent-bar"></div>

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
                        @if ($storePhone){{ $storePhone }}<br>@endif
                        {{ $storeAddress }}
                        @if ($taxNumber)<br>{{ $taxNumber }}@endif
                    </div>
                </td>
                <td class="invoice-title">
                    <h1>{{ $docTitle }}</h1>
                    <div class="inv-number">{{ $invoice->number }}</div>
                    @unless ($isQuote)
                        <div style="margin-top:6px;">
                            <span class="badge" style="background: {{ $statusColor }}22; color: {{ $statusColor }};">{{ $statusLabel }}</span>
                        </div>
                    @endunless
                    <div class="inv-meta">
                        Date: {{ $invoice->issue_date->format('d F Y') }}
                        @if ($invoice->due_date)
                            <br>{{ $isQuote ? 'Valid until' : 'Due' }}: {{ $invoice->due_date->format('d F Y') }}
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <div class="divider"></div>

        <table class="info-grid">
            <tr>
                <td>
                    <div class="info-box">
                        <h4>{{ $isQuote ? 'Prepared For' : 'Bill To' }}</h4>
                        <p>
                            <span class="val">{{ $invoice->customer_name }}</span>
                            @if ($invoice->customer_address)<br>{!! nl2br(e($invoice->customer_address)) !!}@endif
                            @if ($invoice->customer_phone)<br>{{ $invoice->customer_phone }}@endif
                            @if ($invoice->customer_email)<br>{{ $invoice->customer_email }}@endif
                        </p>
                    </div>
                </td>
                <td>
                    <div class="info-box">
                        <h4>{{ $docLabel }} Details</h4>
                        <p>
                            <span class="row-label">{{ $docLabel }} No:</span> {{ $invoice->number }}<br>
                            <span class="row-label">Date:</span> {{ $invoice->issue_date->format('d M Y') }}<br>
                            @unless ($isQuote)
                                <span class="row-label">Payment:</span>
                                <span style="font-weight:700; color: {{ $statusColor }};">{{ $statusLabel }}</span>
                                @if ($invoice->payment_method) · {{ $invoice->payment_method }}@endif
                            @else
                                <span class="row-label">Items:</span> {{ count($invoice->items) }}
                            @endunless
                        </p>
                    </div>
                </td>
            </tr>
        </table>

        <div class="items-wrap">
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="text-align:left; width:5%">#</th>
                        <th style="text-align:left;">Description</th>
                        <th class="text-center" style="width:9%">Qty</th>
                        <th class="text-right" style="width:15%">Unit Price</th>
                        @if ($hasLineDiscount)<th class="text-right" style="width:12%">Discount</th>@endif
                        <th class="text-right" style="width:16%">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $i => $item)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>
                                <div class="prod-name">{{ $item['name'] }}</div>
                                @if (!empty($item['description']))<div class="prod-sku">{{ $item['description'] }}</div>@endif
                            </td>
                            <td class="text-center">{{ rtrim(rtrim(number_format((float) $item['qty'], 2, '.', ''), '0'), '.') }}</td>
                            <td class="num">{{ $money($item['price']) }}</td>
                            @if ($hasLineDiscount)<td class="num" style="color:#dc2626;">{{ ($item['discount'] ?? 0) > 0 ? '-' . $money($item['discount']) : '—' }}</td>@endif
                            <td class="num" style="font-weight:700;">{{ $money($item['total'] ?? 0) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="clearfix">
            <div class="totals">
                <table>
                    <tr><td class="label">Subtotal</td><td class="amount">{{ $money($invoice->subtotal) }}</td></tr>
                    @if ($invoice->discount > 0)
                        <tr>
                            <td class="label">Discount{{ $invoice->discount_type === 'percent' ? ' (' . rtrim(rtrim((string) $invoice->discount_value, '0'), '.') . '%)' : '' }}</td>
                            <td class="amount discount-amount">-{{ $money($invoice->discount) }}</td>
                        </tr>
                    @endif
                    @if ($invoice->tax > 0)
                        <tr><td class="label">Tax / VAT ({{ rtrim(rtrim((string) $invoice->tax_rate, '0'), '.') }}%)</td><td class="amount">{{ $money($invoice->tax) }}</td></tr>
                    @endif
                    @if ($invoice->shipping > 0)
                        <tr><td class="label">Delivery charge</td><td class="amount">{{ $money($invoice->shipping) }}</td></tr>
                    @endif
                    <tr class="separator"><td colspan="2"></td></tr>
                    <tr class="total-row"><td class="label">Total</td><td class="amount">{{ $money($invoice->total) }}</td></tr>
                </table>
            </div>
        </div>
        <div class="clearfix"></div>

        @if ($showWords)
            <p class="words-row">In words: <strong>{{ amount_in_words((float) $invoice->total) }} {{ $currencyWordName }} Only</strong></p>
        @endif

        @if (!$isQuote && $invoice->status !== 'draft')
            <table class="pay-strip">
                <tr>
                    <td>
                        <div class="pay-card" style="background:#ecfdf5;">
                            <div class="k" style="color:#047857;">Paid</div>
                            <div class="v" style="color:#065f46;">{{ $money($invoice->paid_amount) }}</div>
                        </div>
                    </td>
                    <td>
                        <div class="pay-card" style="background: {{ $invoice->due_amount > 0 ? '#fef2f2' : '#f9fafb' }};">
                            <div class="k" style="color: {{ $invoice->due_amount > 0 ? '#b91c1c' : '#6b7280' }};">Balance due</div>
                            <div class="v" style="color: {{ $invoice->due_amount > 0 ? '#991b1b' : '#374151' }};">{{ $money($invoice->due_amount) }}</div>
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        @php
            $noteBoxes = array_filter([
                $invoice->notes ? ['title' => 'Notes', 'body' => $invoice->notes] : null,
                $invoice->terms ? ['title' => 'Terms &amp; Conditions', 'body' => $invoice->terms] : null,
                $bankDetails && !$isQuote ? ['title' => 'Bank / Payment Details', 'body' => $bankDetails] : null,
            ]);
        @endphp
        @if ($noteBoxes)
            <table style="width:100%; margin-top:8px; border-collapse:separate; border-spacing:8px 0;">
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

        @if ($signatureAbsolutePath)
            <table class="bottom-strip">
                <tr>
                    <td></td>
                    <td style="width:200px; text-align:center;">
                        <img src="{{ $signatureAbsolutePath }}" class="signature-img" alt="Signature">
                        <div class="signature-line">Authorized Signature</div>
                    </td>
                </tr>
            </table>
        @endif

        <div class="footer">
            <p>
                {{ $footerText }}<br>
                For questions about this {{ strtolower($docLabel) }}, contact us at <strong>{{ $storeEmail }}</strong>@if ($storePhone) · <strong>{{ $storePhone }}</strong>@endif<br>
                This is a computer-generated {{ strtolower($docLabel) }}{{ $signatureAbsolutePath ? '.' : ' and does not require a signature.' }}
            </p>
        </div>

        @if ($stamp)
            <div class="stamp" style="color: {{ $stamp[1] }};">{{ $stamp[0] }}</div>
        @endif
    </div>
</body>

</html>
