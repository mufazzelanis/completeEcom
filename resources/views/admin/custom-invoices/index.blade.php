@extends('layouts.admin')
@section('title', 'Custom Invoices')

@section('content')
<style>
    .ci-cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    @media (min-width: 1024px) { .ci-cards { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .ci-xs { font-size: 11px; }
</style>
@php $money = fn ($v) => '৳' . number_format((float) $v, 2); @endphp

<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Custom Invoices</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Invoices, quotations, proformas and money receipts you write yourself. These don't create an order or change stock.</p>
    </div>
    <div class="flex flex-wrap gap-2" x-data="{ open: false }">
        <div class="relative" @click.outside="open = false">
            <button type="button" @click="open = !open" class="inline-flex items-center gap-2 text-white px-4 py-2.5 rounded-xl text-sm font-semibold shadow-sm hover:opacity-90 transition"
                    style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div x-show="open" x-cloak x-transition class="absolute right-0 z-20 mt-2 w-60 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 py-1">
                @foreach(\App\Models\CustomInvoice::TYPES as $type => [$title, $label, $color])
                    <a href="{{ route('admin.custom-invoices.create', ['type' => $type]) }}" class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <span class="w-2.5 h-2.5 rounded-full" style="background: {{ $color }};"></span>
                        <span class="flex-1">{{ $label }}</span>
                        <span class="text-gray-400 ci-xs">{{ ['invoice' => 'bill a customer', 'quotation' => 'price offer', 'proforma' => 'before payment', 'receipt' => 'payment received'][$type] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>

{{-- money at a glance (invoices + receipts; drafts, cancelled and quotations excluded) --}}
<div class="ci-cards mb-6">
    @foreach([
        ['Total billed', $money($stats['billed']), '#6366f1', 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
        ['Collected', $money($stats['collected']), '#10b981', 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['Outstanding', $money($stats['due']), '#ef4444', 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['This month', $money($stats['thisMonth']), '#f59e0b', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    ] as [$label, $value, $color, $icon])
        <div class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full" style="background: {{ $color }}14;"></div>
            <div class="w-10 h-10 rounded-xl flex items-center justify-center mb-3" style="background: {{ $color }}1f; color: {{ $color }};">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"/></svg>
            </div>
            <p class="text-xl font-bold text-gray-900 dark:text-gray-100 tabular-nums">{{ $value }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $label }}</p>
        </div>
    @endforeach
</div>

{{-- filters --}}
<form method="GET" class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-4 mb-4 flex flex-wrap items-end gap-3" x-data @change="$el.submit()">
    <div class="flex-1 min-w-40">
        <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Search</label>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Number, customer name or phone…"
               class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </div>
    <div>
        <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Type</label>
        <select name="type" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All types</option>
            @foreach(\App\Models\CustomInvoice::TYPES as $type => [, $label])
                <option value="{{ $type }}" @selected(request('type') === $type)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-medium text-gray-500 uppercase mb-1 block">Status</label>
        <select name="status" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All statuses</option>
            @foreach(\App\Models\CustomInvoice::STATUSES as $status => [$label])
                <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700">Search</button>
    @if(request()->hasAny(['search', 'type', 'status']))
        <a href="{{ route('admin.custom-invoices.index') }}" class="px-3 py-2 text-sm text-gray-500 hover:underline">Clear</a>
    @endif
</form>

<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                    <th class="px-5 py-3">Number</th>
                    <th class="px-5 py-3">Customer</th>
                    <th class="px-5 py-3">Date</th>
                    <th class="px-5 py-3 text-right">Total</th>
                    <th class="px-5 py-3 text-right">Due</th>
                    <th class="px-5 py-3">Status</th>
                    <th class="px-5 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                @forelse($invoices as $inv)
                    @php [, $typeLabel, $typeColor] = $inv->typeMeta(); [$statusLabel, $statusColor] = $inv->statusMeta(); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                        <td class="px-5 py-3">
                            <a href="{{ route('admin.custom-invoices.show', $inv) }}" class="font-semibold text-gray-900 dark:text-gray-100 hover:underline font-mono">{{ $inv->number }}</a>
                            <span class="ml-1 inline-block rounded-full px-2 py-0.5 ci-xs font-semibold" style="background: {{ $typeColor }}1a; color: {{ $typeColor }};">{{ $typeLabel }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <p class="font-medium text-gray-800 dark:text-gray-200">{{ $inv->customer_name }}</p>
                            @if($inv->customer_phone)<p class="text-xs text-gray-400 font-mono">{{ $inv->customer_phone }}</p>@endif
                        </td>
                        <td class="px-5 py-3 text-gray-600 dark:text-gray-300 whitespace-nowrap">
                            {{ $inv->issue_date->format('d M Y') }}
                            @if($inv->due_date && $inv->due_amount > 0 && $inv->due_date->isPast() && !in_array($inv->type, ['quotation', 'proforma']) && !in_array($inv->status, ['draft', 'cancelled']))
                                <p class="ci-xs font-semibold" style="color:#ef4444;">Overdue {{ $inv->due_date->diffForHumans(null, true) }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right font-semibold text-gray-900 dark:text-gray-100 tabular-nums whitespace-nowrap">{{ $money($inv->total) }}</td>
                        <td class="px-5 py-3 text-right tabular-nums whitespace-nowrap" style="color: {{ $inv->due_amount > 0 && !in_array($inv->type, ['quotation', 'proforma']) ? '#ef4444' : '#9ca3af' }};">
                            {{ in_array($inv->type, ['quotation', 'proforma']) ? '—' : $money($inv->due_amount) }}
                        </td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" style="background: {{ $statusColor }}1a; color: {{ $statusColor }};">
                                <span class="w-1.5 h-1.5 rounded-full" style="background: {{ $statusColor }};"></span>{{ $statusLabel }}
                            </span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                <a data-turbo="false" href="{{ route('admin.custom-invoices.pdf', $inv) }}" title="Download PDF" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                </a>
                                <a data-turbo="false" href="{{ route('admin.custom-invoices.pdf', [$inv, 'inline' => 1]) }}" target="_blank" title="Print" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                </a>
                                <a href="{{ route('admin.custom-invoices.edit', $inv) }}" title="Edit" class="p-2 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-5 py-16 text-center">
                            <div class="text-5xl mb-3">🧾</div>
                            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ request()->hasAny(['search', 'type', 'status']) ? 'Nothing matches these filters' : 'No custom invoices yet' }}</p>
                            <p class="text-sm text-gray-500 mt-1">Create one for a phone or showroom sale, a wholesale quote, or a payment you received.</p>
                            <a href="{{ route('admin.custom-invoices.create') }}" class="inline-block mt-4 bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700">Create invoice</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invoices->hasPages())
        <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection
