@extends('layouts.admin')
@section('title', 'Promo Campaign')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.promo-codes.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
        </a>
        <h1 class="text-xl font-bold text-gray-800">{{ $batch->name }}</h1>
        <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $batch->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
            {{ $batch->is_active ? 'Active' : 'Inactive' }}
        </span>
    </div>
    <a href="{{ route('admin.promo-codes.download', $batch) }}" class="flex items-center gap-2 bg-green-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        Download Unused CSV
    </a>
</div>

<div class="grid grid-cols-4 gap-4 mb-6">
    @foreach([
        ['Generated', number_format($batch->generated_count), 'text-gray-700'],
        ['Used', number_format($batch->used_count), 'text-indigo-600'],
        ['Remaining', number_format($batch->generated_count - $batch->used_count), 'text-green-600'],
        ['Usage Rate', $batch->usage_rate.'%', 'text-purple-600'],
    ] as [$label, $value, $color])
    <div class="bg-white rounded-2xl shadow-sm p-5 text-center">
        <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">{{ $label }}</p>
        <p class="text-2xl font-bold {{ $color }}">{{ $value }}</p>
    </div>
    @endforeach
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Codes (showing latest 200)</h2>
        <span class="text-sm text-gray-400">
            {{ $batch->discount_type === 'percentage' ? $batch->discount_value.'% off' : '৳'.number_format($batch->discount_value).' off' }}
            @if($batch->min_order_amount > 0) · Min ৳{{ number_format($batch->min_order_amount) }} @endif
            @if($batch->expires_at) · Expires {{ $batch->expires_at->format('M d, Y') }} @endif
        </span>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50"><tr class="text-xs text-gray-500 uppercase">
            <th class="px-6 py-3 text-left">Code</th>
            <th class="px-6 py-3 text-center">Status</th>
            <th class="px-6 py-3 text-left">Used By</th>
            <th class="px-6 py-3 text-center">Used At</th>
            <th class="px-6 py-3 text-left">Order</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($batch->codes as $code)
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-2.5 font-mono text-xs font-semibold text-gray-800">{{ $code->code }}</td>
                <td class="px-6 py-2.5 text-center">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $code->isUsed() ? 'bg-gray-100 text-gray-500' : 'bg-green-100 text-green-700' }}">
                        {{ $code->isUsed() ? 'Used' : 'Available' }}
                    </span>
                </td>
                <td class="px-6 py-2.5 text-xs text-gray-600">{{ $code->user?->name ?? '—' }}</td>
                <td class="px-6 py-2.5 text-center text-xs text-gray-400">{{ $code->used_at?->format('M d, Y') ?? '—' }}</td>
                <td class="px-6 py-2.5 text-xs">
                    @if($code->order)
                    <a href="{{ route('admin.orders.show', $code->order) }}" class="text-indigo-600 hover:text-indigo-800 font-mono">{{ $code->order->order_number }}</a>
                    @else —
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
