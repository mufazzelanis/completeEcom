{{-- Shared look of order and custom invoices. Rendered by mPDF (App\Support\InvoicePdf): spacing and
     backgrounds sit on table cells because mPDF ignores most of them on a div inside a cell. --}}
<style>
    body { font-family: hind; font-size: 10pt; color: #1f2937; line-height: 1.4; }
    table { border-collapse: collapse; width: 100%; }
    td, th { vertical-align: top; }

    .band td { vertical-align: middle; color: {{ $theme['onDark'] }}; }
        .brand-name { font-size: 20pt; font-weight: bold; }
    .doc-title { font-size: 27pt; font-weight: bold; letter-spacing: 1.5pt; line-height: 1.05; }
    .doc-number { font-size: 11.5pt; font-weight: bold; color: {{ $theme['tintLine'] }}; }

    .label { font-size: 7.5pt; font-weight: bold; letter-spacing: .5pt; text-transform: uppercase; color: {{ $theme['accentText'] }}; }

    /* starts inside the band on purpose: the strip ties the header to the page */
    .facts td { background-color: {{ $theme['tint'] }}; padding: 3mm 4mm; border-left: 0.7mm solid #ffffff; }
    .facts td.first { border-left: none; }
    .fact-value { font-size: 11pt; font-weight: bold; color: #111827; }

    .parties { margin-top: {{ 4.5 + $air }}mm; }
    .parties td.card { width: 49%; border: 0.25mm solid #e5e7eb; padding: 4mm 5mm; }
    .parties td.gap { width: 2%; }
    .party-name { font-size: 13.5pt; font-weight: bold; color: #111827; }
    .party-line { font-size: 10pt; color: #4b5563; }

    .journey { margin-top: {{ 5 + $air }}mm; }
    .journey td.gap { width: 1.5mm; }
    .journey td.step { padding-top: 1.6mm; font-size: 9pt; font-weight: bold; }
    .step-done { border-top: 1mm solid {{ $theme['accent'] }}; color: {{ $theme['accentText'] }}; }
    .step-todo { border-top: 1mm solid #e5e7eb; color: #9ca3af; }
    .alert { margin-top: {{ 5 + $air }}mm; }
    .alert td { background-color: #fef2f2; color: #991b1b; font-weight: bold; padding: 2.5mm 4mm; text-align: center; }

    .items { margin-top: {{ 5 + $air }}mm; }
    .items th { background-color: {{ $theme['dark'] }}; border-left: 0.1mm solid {{ $theme['dark'] }}; border-right: 0.1mm solid {{ $theme['dark'] }}; color: {{ $theme['onDark'] }}; font-size: 8.5pt; font-weight: bold; text-transform: uppercase; padding: 2.8mm 3mm; text-align: left; }
    .items td { padding: 2.6mm 3mm; border-bottom: 0.25mm solid #e5e7eb; vertical-align: middle; font-size: 10pt; }
    .items tr.alt td { background-color: #f9fafb; }
    .items .r { text-align: right; }
    .items .c { text-align: center; }
    .items .photo { width: 17mm; padding: 1.6mm 2mm; text-align: center; }
    .item-name { font-weight: bold; color: #111827; }
    .item-sub { font-size: 8.5pt; color: #6b7280; }

    .summary { margin-top: {{ 5 + $air }}mm; }
    .summary td.left { width: 56%; }
    .summary td.gap { width: 4%; }
    .summary td.right { width: 40%; }

    .panel { margin-bottom: 2.5mm; }
    .panel td { background-color: #f9fafb; padding: 2.6mm 4mm; border-left: 0.9mm solid {{ $theme['accent'] }}; }
    .panel-big { font-size: 12pt; font-weight: bold; color: #111827; }
    .small { font-size: 8.5pt; color: #6b7280; }
    .note { font-size: 8.5pt; color: #4b5563; }

    .totals td { padding: 1.8mm 3mm; font-size: 10pt; color: #4b5563; }
    .totals td.v { text-align: right; color: #111827; }
    .totals .minus { color: #dc2626; }
    .totals tr.grand td { background-color: {{ $theme['dark'] }}; border-left: 0.1mm solid {{ $theme['dark'] }}; border-right: 0.1mm solid {{ $theme['dark'] }}; color: {{ $theme['onDark'] }}; font-size: 13.5pt; font-weight: bold; padding: 3mm; }
    .totals tr.due td { background-color: {{ $theme['tint'] }}; border-left: 0.1mm solid {{ $theme['tint'] }}; border-right: 0.1mm solid {{ $theme['tint'] }}; color: {{ $theme['accentText'] }}; font-size: 11pt; font-weight: bold; padding: 2.6mm 3mm; }
    .totals tr.settled td { background-color: #ecfdf5; border-left: 0.1mm solid #ecfdf5; border-right: 0.1mm solid #ecfdf5; color: #047857; font-size: 11pt; font-weight: bold; padding: 2.6mm 3mm; }

    .boxes td.box { background-color: #f9fafb; padding: 2.8mm 4mm; }
    .boxes td.gap { width: 2.5mm; }

    .closing { margin-top: 3mm; }
    .closing td { background-color: {{ $theme['tint'] }}; padding: 4mm 5mm; vertical-align: middle; }
    .thanks { font-size: 13pt; font-weight: bold; color: {{ $theme['accentText'] }}; }
    .closing td.signature { text-align: center; font-size: 8pt; color: #6b7280; }

    .foot td { font-size: 8pt; color: #6b7280; }
</style>
