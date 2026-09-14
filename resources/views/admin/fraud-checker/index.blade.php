@extends('layouts.admin')
@section('title', 'Fraud Checker')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-semibold text-gray-800">Fraud Checker</h1>
        <p class="text-sm text-gray-500 mt-0.5">Check a phone number's delivery history across Bangladesh's courier services before confirming a COD order.</p>
    </div>
    <a href="{{ route('admin.settings.show', 'fraud_checker') }}" class="flex items-center gap-2 text-sm text-gray-500 hover:text-orange-600 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/></svg>
        Settings
    </a>
</div>

@if(! $apiConfigured)
<div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 text-sm mb-6 flex items-start gap-2">
    <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
    <div>
        No API key configured yet, so checks will fail. Get a free/paid API key from
        <a href="https://bdcourier.com" target="_blank" rel="noopener" class="underline font-medium">bdcourier.com</a>
        and add it under <a href="{{ route('admin.settings.show', 'fraud_checker') }}" class="underline font-medium">Settings → Fraud Checker</a>.
    </div>
</div>
@endif

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_checks']) }}</p>
        <p class="text-sm text-gray-500 mt-1">Total Checks Run</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <p class="text-2xl font-bold text-red-600">{{ number_format($stats['high_risk']) }}</p>
        <p class="text-sm text-gray-500 mt-1">High Risk Numbers Found</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['checked_today']) }}</p>
        <p class="text-sm text-gray-500 mt-1">Checked Today</p>
    </div>
</div>

{{-- Search --}}
<div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
    <h2 class="font-semibold text-gray-800 mb-3">Check a Phone Number</h2>
    <form method="POST" action="{{ route('admin.fraud-checker.check') }}" class="flex flex-col sm:flex-row gap-3">
        @csrf
        <input type="tel" name="phone" value="{{ $phone }}" required placeholder="01XXXXXXXXX"
               class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
        <button type="submit" class="px-6 py-2.5 bg-orange-600 text-white rounded-xl text-sm font-medium hover:bg-orange-700 transition whitespace-nowrap">
            Check Number
        </button>
    </form>

    @if($phone && $result)
        @php $style = $result->getRiskStyle(); @endphp
        <div class="mt-5 border-t border-gray-100 pt-5">
            <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                <div class="flex items-center gap-3">
                    <span class="w-2.5 h-2.5 rounded-full {{ $style['dot'] }}"></span>
                    <span class="font-mono text-sm text-gray-700">{{ $result->phone }}</span>
                    <span class="text-xs px-2.5 py-1 rounded-full font-semibold {{ $style['bg'] }} {{ $style['text'] }}">{{ $style['label'] }}</span>
                </div>
                <form method="POST" action="{{ route('admin.fraud-checker.check') }}">
                    @csrf
                    <input type="hidden" name="phone" value="{{ $result->phone }}">
                    <input type="hidden" name="force" value="1">
                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1 px-2 py-1 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Re-check
                    </button>
                </form>
            </div>

            @if(! $result->success)
                <p class="text-sm text-red-600">Check failed: {{ $result->error }}</p>
            @elseif($result->total_orders === 0)
                <p class="text-sm text-gray-500">No delivery history found for this number on any covered courier — likely a first-time customer.</p>
            @else
                <div class="grid grid-cols-3 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-bold text-gray-900">{{ $result->total_orders }}</p>
                        <p class="text-xs text-gray-500">Total Parcels</p>
                    </div>
                    <div class="bg-green-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-bold text-green-600">{{ $result->total_delivered }}</p>
                        <p class="text-xs text-gray-500">Delivered</p>
                    </div>
                    <div class="bg-red-50 rounded-xl p-3 text-center">
                        <p class="text-lg font-bold text-red-600">{{ $result->total_cancelled }}</p>
                        <p class="text-xs text-gray-500">Cancelled / Returned</p>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>Success Rate</span>
                        <span class="font-semibold {{ $style['text'] }}">{{ $result->success_rate }}%</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="{{ str_replace('text-', 'bg-', $style['text']) }} h-2 rounded-full" style="width:{{ $result->success_rate }}%"></div>
                    </div>
                </div>
                @if(! empty($result->breakdown))
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">By Courier</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-xs text-gray-400 uppercase">
                                <th class="pb-2">Courier</th>
                                <th class="pb-2 text-center">Delivered</th>
                                <th class="pb-2 text-center">Cancelled</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($result->breakdown as $courier => $counts)
                            <tr>
                                <td class="py-1.5 capitalize">{{ str_replace('_', ' ', $courier) }}</td>
                                <td class="py-1.5 text-center text-green-600">{{ $counts['delivered'] ?? 0 }}</td>
                                <td class="py-1.5 text-center text-red-600">{{ $counts['cancelled'] ?? 0 }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            @endif

            @php $providerErrors = $result->raw_response['errors'] ?? []; @endphp
            @if(! empty($providerErrors))
            <div class="mt-4 pt-3 border-t border-gray-100">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Provider Status</p>
                <div class="space-y-1">
                    @foreach($providerErrors as $providerKey => $providerError)
                    <p class="text-xs text-gray-400"><span class="capitalize font-medium text-gray-500">{{ $providerKey }}</span>: {{ $providerError }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <p class="text-xs text-gray-400 mt-3">Checked {{ $result->created_at->diffForHumans() }} @if($result->checkedBy)by {{ $result->checkedBy->name }}@endif</p>
        </div>
    @elseif($phone && ! $result)
        <p class="mt-4 text-sm text-gray-500">No check on file yet for this number — click "Check Number" above.</p>
    @endif
</div>

{{-- Recent Checks --}}
<div class="bg-white rounded-2xl shadow-sm">
    <div class="flex items-center justify-between p-6 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Recent Checks</h2>
        <div class="flex gap-1.5">
            @foreach(['' => 'All', 'high' => 'High Risk', 'medium' => 'Risky', 'low' => 'Safe', 'unknown' => 'Unknown'] as $val => $label)
            <a href="{{ route('admin.fraud-checker.index', array_filter(['risk' => $val])) }}"
               class="text-xs px-3 py-1.5 rounded-full font-medium transition {{ request('risk', '') === $val ? 'bg-orange-600 text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}">
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    <th class="px-6 py-3 text-left">Phone</th>
                    <th class="px-6 py-3 text-center">Parcels</th>
                    <th class="px-6 py-3 text-center">Success Rate</th>
                    <th class="px-6 py-3 text-center">Risk</th>
                    <th class="px-6 py-3 text-left">Order</th>
                    <th class="px-6 py-3 text-left">Checked</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($recentChecks as $check)
                @php $s = $check->getRiskStyle(); @endphp
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-6 py-3">
                        <a href="{{ route('admin.fraud-checker.index', ['phone' => $check->phone]) }}" class="font-mono text-sm text-indigo-600 hover:underline">{{ $check->phone }}</a>
                    </td>
                    <td class="px-6 py-3 text-center text-sm text-gray-600">{{ $check->success ? $check->total_orders : '—' }}</td>
                    <td class="px-6 py-3 text-center text-sm text-gray-600">{{ $check->success_rate !== null ? $check->success_rate.'%' : '—' }}</td>
                    <td class="px-6 py-3 text-center">
                        <span class="text-xs px-2.5 py-1 rounded-full font-medium {{ $s['bg'] }} {{ $s['text'] }}">{{ $s['label'] }}</span>
                    </td>
                    <td class="px-6 py-3 text-sm">
                        @if($check->order)
                            <a href="{{ route('admin.orders.show', $check->order_id) }}" class="text-indigo-600 hover:underline">{{ $check->order->order_number }}</a>
                        @else
                            <span class="text-gray-300">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-3 text-sm text-gray-500">
                        {{ $check->created_at->diffForHumans() }}
                        @if($check->checkedBy)<span class="text-gray-300">· {{ $check->checkedBy->name }}</span>@endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-gray-400">No checks run yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($recentChecks->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $recentChecks->links() }}
    </div>
    @endif
</div>
@endsection
