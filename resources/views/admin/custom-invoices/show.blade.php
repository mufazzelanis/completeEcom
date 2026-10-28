@extends('layouts.admin')
@section('title', $invoice->number)

@section('content')
<style>
    .ci-grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 1.5rem; align-items: start; }
    @media (min-width: 1200px) { .ci-grid { grid-template-columns: minmax(0, 1fr) 22rem; } }
    .ci-pdf { width: 100%; height: 78vh; min-height: 560px; border: 0; border-radius: 14px; background: #fff; box-shadow: 0 10px 40px -12px rgba(15, 23, 42, .25); }
    .ci-in { width: 100%; border: 1px solid #e5e7eb; border-radius: .6rem; padding: .5rem .7rem; font-size: .875rem; background: transparent; }
    .ci-xs { font-size: 11px; }
</style>
@php
    $money = fn ($v) => '৳' . number_format((float) $v, 2);
    [$docTitle, $typeLabel, $typeColor] = $invoice->typeMeta();
    [$statusLabel, $statusColor] = $invoice->statusMeta();
    $isQuote = in_array($invoice->type, ['quotation', 'proforma'], true);
    $accent = $invoice->accent_color ?: $typeColor;
    // WhatsApp: a ready-to-send summary to the customer's number (BD numbers → 880…)
    $waPhone = preg_replace('/\D/', '', (string) $invoice->customer_phone);
    if (str_starts_with($waPhone, '01') && strlen($waPhone) === 11) { $waPhone = '88' . $waPhone; }
    $waText = "Assalamu Alaikum {$invoice->customer_name},\n{$typeLabel} {$invoice->number} from " . (setting('company_name') ?: setting('site_name')) . "\n"
        . collect($invoice->items)->map(fn ($i) => '• ' . $i['name'] . ' × ' . rtrim(rtrim(number_format((float) $i['qty'], 2, '.', ''), '0'), '.') . ' = ' . $money($i['total'] ?? 0))->implode("\n")
        . "\nTotal: " . $money($invoice->total)
        . (!$isQuote && $invoice->due_amount > 0 ? "\nDue: " . $money($invoice->due_amount) : '')
        . "\nThank you!";
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.custom-invoices.index') }}" class="w-9 h-9 bg-white dark:bg-gray-900 rounded-xl shadow-sm flex items-center justify-center text-gray-500 hover:text-indigo-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100 font-mono">{{ $invoice->number }}</h1>
                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background: {{ $typeColor }}1a; color: {{ $typeColor }};">{{ $typeLabel }}</span>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold" style="background: {{ $statusColor }}1a; color: {{ $statusColor }};"><span class="w-1.5 h-1.5 rounded-full" style="background: {{ $statusColor }};"></span>{{ $statusLabel }}</span>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $invoice->customer_name }} · {{ $invoice->issue_date->format('d M Y') }}@if($invoice->creator) · by {{ $invoice->creator->name }}@endif</p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a data-turbo="false" href="{{ route('admin.custom-invoices.pdf', $invoice) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white shadow-sm hover:opacity-90" style="background: {{ $accent }};">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            Download PDF
        </a>
        <a data-turbo="false" href="{{ route('admin.custom-invoices.pdf', [$invoice, 'inline' => 1]) }}" target="_blank" class="px-4 py-2.5 rounded-xl text-sm font-medium bg-white dark:bg-gray-900 shadow-sm text-gray-700 dark:text-gray-200 hover:text-indigo-600">🖨️ Print</a>
        <a href="{{ route('admin.custom-invoices.edit', $invoice) }}" class="px-4 py-2.5 rounded-xl text-sm font-medium bg-white dark:bg-gray-900 shadow-sm text-gray-700 dark:text-gray-200 hover:text-indigo-600">✏️ Edit</a>
    </div>
</div>

<div class="ci-grid">
    <iframe src="{{ route('admin.custom-invoices.pdf', [$invoice, 'inline' => 1]) }}#toolbar=0&view=FitH" class="ci-pdf" title="Invoice preview"></iframe>

    <div class="space-y-5">
        {{-- money --}}
        <div class="rounded-2xl p-5 text-white shadow-lg" style="background: linear-gradient(135deg, {{ $accent }}, {{ $accent }}cc);">
            <p class="ci-xs uppercase tracking-wider opacity-80">Total</p>
            <p class="text-3xl font-extrabold tabular-nums">{{ $money($invoice->total) }}</p>
            @unless($isQuote)
                <div class="mt-4 h-2 rounded-full overflow-hidden" style="background: rgba(255,255,255,.25);">
                    <div class="h-full rounded-full" style="background:#fff; width: {{ $invoice->total > 0 ? min(100, round($invoice->paid_amount / $invoice->total * 100)) : 0 }}%;"></div>
                </div>
                <div class="flex justify-between text-sm mt-2">
                    <span>Paid <b class="tabular-nums">{{ $money($invoice->paid_amount) }}</b></span>
                    <span>Due <b class="tabular-nums">{{ $money($invoice->due_amount) }}</b></span>
                </div>
                @if($invoice->due_date && $invoice->due_amount > 0 && !in_array($invoice->status, ['draft', 'cancelled']))
                    <p class="ci-xs mt-2 opacity-90">{{ $invoice->due_date->isPast() ? '⚠️ Overdue since' : '⏳ Due' }} {{ $invoice->due_date->format('d M Y') }}</p>
                @endif
            @else
                <p class="text-sm mt-2 opacity-90">{{ count($invoice->items) }} items @if($invoice->due_date)· valid until {{ $invoice->due_date->format('d M Y') }}@endif</p>
            @endunless
        </div>

        {{-- record a payment --}}
        @if(!$isQuote && $invoice->due_amount > 0 && $invoice->status !== 'cancelled')
            <form method="POST" action="{{ route('admin.custom-invoices.payment', $invoice) }}" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5 space-y-3">
                @csrf
                <h3 class="font-semibold text-gray-800 dark:text-gray-100">💰 Record a payment</h3>
                <div class="flex gap-2">
                    <input type="number" name="amount" step="any" min="0.01" max="{{ $invoice->due_amount }}" value="{{ $invoice->due_amount }}" class="ci-in text-right" required>
                    <input type="text" name="payment_method" list="ci-methods" placeholder="Method" value="{{ $invoice->payment_method }}" class="ci-in">
                    <datalist id="ci-methods">@foreach(\App\Models\CustomInvoice::PAYMENT_METHODS as $m)<option value="{{ $m }}">@endforeach</datalist>
                </div>
                <button class="w-full py-2.5 rounded-xl text-sm font-semibold text-white" style="background:#10b981;">Add payment</button>
            </form>
        @endif

        {{-- customer --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5 text-sm space-y-1">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-2">Customer</h3>
            <p class="font-medium text-gray-800 dark:text-gray-200">{{ $invoice->customer_name }}
                @if($invoice->user)<a href="{{ route('admin.users.show', $invoice->user) }}" class="ci-xs ml-1 hover:underline" style="color:#6366f1">account →</a>@endif
            </p>
            @if($invoice->customer_phone)<p class="font-mono text-gray-500">{{ $invoice->customer_phone }}</p>@endif
            @if($invoice->customer_email)<p class="text-gray-500">{{ $invoice->customer_email }}</p>@endif
            @if($invoice->customer_address)<p class="text-gray-500 whitespace-pre-line">{{ $invoice->customer_address }}</p>@endif
            @if(strlen($waPhone) >= 10)
                <a href="https://wa.me/{{ $waPhone }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener"
                   class="mt-3 inline-flex items-center gap-2 px-3 py-2 rounded-xl text-sm font-semibold text-white" style="background:#25d366;">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1-.3-.1-.5-.1-.7.1-.2.3-.8 1-.9 1.2-.2.2-.3.2-.6.1-.3-.1-1.3-.5-2.4-1.5-.9-.8-1.5-1.8-1.7-2.1-.2-.3 0-.5.1-.6l.4-.5c.2-.2.2-.3.3-.5.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6c-.2 0-.5.1-.8.4-.3.3-1 1-1 2.5s1.1 2.9 1.2 3.1c.1.2 2.1 3.2 5.1 4.5 2.5 1 3 .8 3.6.8.6-.1 1.8-.7 2-1.4.2-.7.2-1.3.2-1.4-.1-.2-.3-.3-.6-.4zM12 2a10 10 0 00-8.6 15.1L2 22l5-1.3A10 10 0 1012 2z"/></svg>
                    Send summary on WhatsApp
                </a>
                <p class="ci-xs text-gray-400">Opens WhatsApp with the items and total typed in — attach the PDF there if you like.</p>
            @endif
        </div>

        {{-- status & more --}}
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5 space-y-3" x-data="{ confirmDelete: false }">
            <h3 class="font-semibold text-gray-800 dark:text-gray-100">Actions</h3>
            <div class="flex flex-wrap gap-2">
                @foreach(\App\Models\CustomInvoice::STATUSES as $key => [$label, $color])
                    @continue($key === $invoice->status || ($key === 'partial') || ($isQuote && in_array($key, ['paid', 'partial'])))
                    <form method="POST" action="{{ route('admin.custom-invoices.status', $invoice) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $key }}">
                        <button class="px-3 py-1.5 rounded-lg ci-xs font-semibold" style="background: {{ $color }}1a; color: {{ $color }};">Mark {{ strtolower($label) }}</button>
                    </form>
                @endforeach
            </div>
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <p class="ci-xs text-gray-400 mb-2">Make a copy as…</p>
                <div class="flex flex-wrap gap-2">
                    @foreach(\App\Models\CustomInvoice::TYPES as $type => [, $label, $color])
                        <form method="POST" action="{{ route('admin.custom-invoices.duplicate', $invoice) }}">
                            @csrf
                            <input type="hidden" name="type" value="{{ $type }}">
                            <button class="px-3 py-1.5 rounded-lg ci-xs font-semibold border" style="border-color: {{ $color }}55; color: {{ $color }};">{{ $type === $invoice->type ? '⧉ Duplicate' : '→ ' . $label }}</button>
                        </form>
                    @endforeach
                </div>
            </div>
            <div class="pt-3 border-t border-gray-100 dark:border-gray-800">
                <button type="button" x-show="!confirmDelete" @click="confirmDelete = true" class="ci-xs text-red-500 hover:underline">Delete this {{ strtolower($typeLabel) }}…</button>
                <form x-show="confirmDelete" x-cloak method="POST" action="{{ route('admin.custom-invoices.destroy', $invoice) }}" class="flex items-center gap-2">
                    @csrf @method('DELETE')
                    <span class="ci-xs text-gray-600 dark:text-gray-300">Delete permanently?</span>
                    <button class="px-3 py-1.5 rounded-lg ci-xs font-semibold text-white" style="background:#ef4444;">Yes, delete</button>
                    <button type="button" @click="confirmDelete = false" class="ci-xs text-gray-500">Cancel</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
