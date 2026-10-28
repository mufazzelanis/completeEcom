{{-- Shared PDF invoice styles (order invoices + custom invoices). Expects $accentColor and $darkColor. --}}
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Hind Siliguri', DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            background: #fff;
        }

        /* Spacing throughout this file is deliberately tight — with every optional section
           (BIN, bank details, terms, order notes, QR + signature) switched on at once, this
           still needs to fit a single-item order on one page rather than spilling a couple
           of lines onto a near-empty second page. */
        .page {
            padding: 20px 34px;
        }

        /* Top accent bar */
        .accent-bar {
            height: 5px;
            background-color: {{ $accentColor }};
            border-radius: 3px;
            margin-bottom: 16px;
        }

        /* Header */
        .header-table {
            width: 100%;
            margin-bottom: 16px;
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
            font-family: 'Hind Siliguri', DejaVu Sans, sans-serif;
            font-size: 22px;
            font-weight: 700;
            color: {{ $accentColor }};
        }

        .brand-sub {
            font-size: 10.5px;
            color: #6b7280;
            margin-top: 2px;
        }

        .brand-contact {
            margin-top: 6px;
            font-size: 10px;
            color: #6b7280;
            line-height: 1.5;
        }

        .invoice-title {
            text-align: right;
        }

        .invoice-title h1 {
            font-family: 'Hind Siliguri', DejaVu Sans, sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            letter-spacing: 2.5px;
            line-height: 1;
        }

        .invoice-title .inv-number {
            font-size: 11.5px;
            color: {{ $accentColor }};
            font-weight: 700;
            margin-top: 4px;
        }

        .invoice-title .inv-meta {
            margin-top: 5px;
            font-size: 10px;
            color: #6b7280;
            line-height: 1.5;
        }

        /* Personal greeting line, between the header and the Ship To / Order Details cards —
           the one line that makes this read as written to this customer, not a generic form. */
        .greeting {
            font-size: 11.5px;
            color: #374151;
            margin-bottom: 14px;
            line-height: 1.5;
        }

        .greeting strong {
            color: #111827;
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
            margin-bottom: 14px;
        }

        /* Info grid */
        .info-grid {
            width: 100%;
            margin-bottom: 16px;
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
            padding: 10px 14px;
            border-left: 3px solid {{ $accentColor }};
        }

        .info-box h4 {
            font-size: 9.5px;
            font-weight: 700;
            color: {{ $accentColor }};
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .info-box p {
            font-size: 11px;
            color: #374151;
            line-height: 1.55;
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
            margin-bottom: 0;
        }

        .items-wrap {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 14px;
        }

        .items-wrap .items-table {
            margin-bottom: 0;
        }

        .items-table thead th {
            background: {{ $darkColor }};
            color: #fff;
            padding: 8px 12px;
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
            padding: 7px 12px;
            font-size: 11px;
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
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 4px 14px;
        }

        /* Amount spelled out in words — a standard courtesy on Bangladeshi invoices,
           printed right under the totals card. */
        .words-row {
            clear: both;
            padding-top: 5px;
            font-size: 10px;
            color: #6b7280;
            font-style: italic;
        }

        .words-row strong {
            color: #374151;
            font-style: normal;
        }

        .totals table {
            width: 100%;
        }

        .totals td {
            padding: 4px 0;
            font-size: 11px;
            font-family: 'Hind Siliguri', DejaVu Sans, sans-serif;
        }

        .totals .label {
            color: #6b7280;
        }

        .totals .amount {
            text-align: right;
            font-weight: normal;
            color: #111827;
            font-family: 'Hind Siliguri', DejaVu Sans, sans-serif;
        }

        .totals .discount-amount {
            color: #dc2626;
        }

        .totals .separator td {
            border-top: 1px solid #e5e7eb;
            padding: 0;
        }

        .totals .total-row td {
            font-size: 13px;
            font-weight: normal;
            padding: 6px 14px;
            font-family: 'Hind Siliguri', DejaVu Sans, sans-serif;
        }

        .totals .total-row {
            background: {{ $darkColor }};
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
            margin-top: 6px;
            padding: 6px 14px;
            background: #f9fafb;
            border-radius: 8px;
            border-left: 3px solid {{ $accentColor }};
        }

        .note-box h4 {
            font-size: 9.5px;
            font-weight: 700;
            color: {{ $accentColor }};
            margin-bottom: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .note-box p {
            font-size: 10px;
            color: #6b7280;
            line-height: 1.45;
            white-space: pre-line;
        }

        /* Footer */
        .footer {
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }

        .footer p {
            font-size: 9px;
            color: #9ca3af;
            line-height: 1.6;
        }

        .footer strong {
            color: {{ $accentColor }};
        }

        /* Bottom strip: QR code (left) and signature (right) share one row so the footer
           reads as a single designed block instead of two unrelated add-ons. */
        .bottom-strip {
            width: 100%;
            margin-top: 6px;
        }

        .bottom-strip td {
            vertical-align: bottom;
        }

        .qr-code {
            width: 52px;
            height: 52px;
        }

        .qr-caption {
            margin-top: 3px;
            font-size: 8px;
            color: #9ca3af;
            max-width: 90px;
            line-height: 1.3;
        }

        .signature-img {
            max-height: 38px;
            max-width: 140px;
            margin-bottom: 3px;
        }

        .signature-line {
            border-top: 1px solid #9ca3af;
            padding-top: 3px;
            font-size: 9px;
            color: #6b7280;
            text-align: center;
        }

        /* Diagonal status stamp for cancelled/refunded orders — `position: fixed` repeats it
           on every page dompdf renders, same trick used for running headers/footers. */
        .watermark {
            position: fixed;
            top: 470px;
            left: 0;
            width: 100%;
            text-align: center;
            font-size: 90px;
            font-weight: 800;
            color: #dc2626;
            opacity: 0.12;
            letter-spacing: 12px;
            transform: rotate(-25deg);
        }
    </style>
