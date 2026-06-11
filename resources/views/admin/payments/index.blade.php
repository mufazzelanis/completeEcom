@extends('layouts.admin')
@section('title', 'Payments')

@section('content')
{{-- Stats --}}
@if($stats['pending_verification'] > 0)
<div class="mb-4 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 flex items-center space-x-3">
    <svg class="w-5 h-5 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <p class="text-sm text-orange-700 font-medium">{{ $stats['pending_verification'] }} payment(s) awaiting verification</p>
    <a href="{{ route('admin.payments.index', ['status' => 'pending_verification']) }}" class="ml-auto text-xs text-orange-600 underline">View</a>
</div>
@endif

<div class="grid grid-cols-3 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Pending Verification</p>
        <p class="text-2xl font-bold text-orange-600">{{ $stats['pending_verification'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Completed Today</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['completed_today'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-4">
        <p class="text-xs text-gray-500 mb-1">Revenue Today (৳)</p>
        <p class="text-2xl font-bold text-indigo-600">{{ number_format($stats['total_today'], 0) }}</p>
    </div>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif

{{-- Filters --}}
<div class="bg-white rounded-2xl shadow-sm p-4 mb-4">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Order no. / TXN ID / sender…"
            class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 w-56">
        <select name="status" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Status</option>
            @foreach(['pending','pending_verification','completed','failed','refunded'] as $st)
                <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$st)) }}</option>
            @endforeach
        </select>
        <select name="method" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">All Methods</option>
            @foreach($methods as $m)
                <option value="{{ $m->payment_method_slug }}" {{ request('method') == $m->payment_method_slug ? 'selected' : '' }}>{{ $m->payment_method_name }}</option>
            @endforeach
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-indigo-700 transition">Filter</button>
        @if(request()->hasAny(['search','status','method','date_from','date_to']))
            <a href="{{ route('admin.payments.index') }}" class="px-4 py-2 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Clear</a>
        @endif
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr class="text-xs text-gray-500 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Order</th>
                <th class="px-6 py-3 text-left">Method</th>
                <th class="px-6 py-3 text-left">TXN / Sender</th>
                <th class="px-6 py-3 text-right">Amount</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Date</th>
                <th class="px-6 py-3 text-right">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($payments as $payment)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-3">
                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-indigo-600 hover:underline text-sm font-medium">
                        {{ $payment->order->order_number }}
                    </a>
                    <p class="text-xs text-gray-400">{{ $payment->order->user->name ?? '—' }}</p>
                </td>
                <td class="px-6 py-3 text-sm text-gray-700">{{ $payment->payment_method_name }}</td>
                <td class="px-6 py-3">
                    @if($payment->transaction_id)
                        <p class="text-xs font-mono text-gray-700">{{ $payment->transaction_id }}</p>
                    @endif
                    @if($payment->sender_number)
                        <p class="text-xs text-gray-400">{{ $payment->sender_number }}</p>
                    @endif
                    @if(!$payment->transaction_id && !$payment->sender_number)
                        <span class="text-gray-300 text-xs">—</span>
                    @endif
                </td>
                <td class="px-6 py-3 text-right">
                    <p class="text-sm font-semibold text-gray-900">৳{{ number_format($payment->amount, 2) }}</p>
                    @if($payment->charge > 0)<p class="text-xs text-gray-400">+৳{{ number_format($payment->charge, 2) }} fee</p>@endif
                </td>
                <td class="px-6 py-3 text-center">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payment->statusBadge() }}">
                        {{ $payment->statusLabel() }}
                    </span>
                </td>
                <td class="px-6 py-3 text-center text-xs text-gray-500">{{ $payment->created_at->format('d M Y') }}</td>
                <td class="px-6 py-3 text-right">
                    @if($payment->status === 'pending_verification')
                    <a href="{{ route('admin.payments.show', $payment) }}" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white rounded-lg text-xs font-medium hover:bg-green-700 transition">
                        Verify
                    </a>
                    @else
                    <a href="{{ route('admin.payments.show', $payment) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a>
                    @endif
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="px-6 py-12 text-center text-gray-400 text-sm">No payments found.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($payments->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">{{ $payments->withQueryString()->links() }}</div>
    @endif
</div>
@endsection
