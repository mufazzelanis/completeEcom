@extends('layouts.admin')
@section('title', 'Order Details')

@section('content')
@php
$riskColors = [
    'critical' => ['bg'=>'bg-red-600',    'text'=>'text-red-600',    'light'=>'bg-red-50',    'border'=>'border-red-400'],
    'high'     => ['bg'=>'bg-orange-500', 'text'=>'text-orange-600', 'light'=>'bg-orange-50', 'border'=>'border-orange-400'],
    'medium'   => ['bg'=>'bg-yellow-500', 'text'=>'text-yellow-600', 'light'=>'bg-yellow-50', 'border'=>'border-yellow-400'],
    'low'      => ['bg'=>'bg-green-500',  'text'=>'text-green-600',  'light'=>'bg-green-50',  'border'=>'border-green-400'],
];
$riskLevel = match(true) {
    ($order->fraud_score ?? 0) >= 60 => 'critical',
    ($order->fraud_score ?? 0) >= 50 => 'high',
    ($order->fraud_score ?? 0) >= 20 => 'medium',
    default => 'low',
};
$rc = $riskColors[$riskLevel];
@endphp

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center space-x-4">
        <a href="{{ route('admin.orders.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center space-x-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Back</span>
        </a>
        <h1 class="font-semibold text-gray-800">{{ $order->order_number }}</h1>
        @if($order->is_fraud_flagged)
        <span class="flex items-center gap-1 bg-red-100 text-red-700 text-xs font-semibold px-2.5 py-1 rounded-full">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            Fraud Flagged
        </span>
        @endif
    </div>
    <a href="{{ route('admin.orders.invoice', $order) }}"
       class="flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-red-700 transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Download Invoice PDF
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Order Items</h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center space-x-4 pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                        <div class="w-16 h-16 bg-gray-100 rounded-xl overflow-hidden flex-shrink-0">
                            @if($item->product && $item->product->image)
                                <img src="{{ Storage::url($item->product->image) }}" class="w-full h-full object-cover">
                            @endif
                        </div>
                        <div class="flex-1">
                            <p class="font-medium text-gray-800 text-sm">{{ $item->product_name }}</p>
                            @if($item->variant_label)
                                <p class="text-xs text-gray-500">{{ $item->variant_label }}</p>
                            @endif
                            <p class="text-xs text-gray-400">৳{{ number_format($item->price) }} × {{ $item->quantity }}</p>
                            @if($item->product?->isDigital())
                                <p class="text-xs text-indigo-500 mt-0.5">
                                    {{ $item->download_count > 0 ? "Downloaded {$item->download_count}×" : 'Not downloaded yet' }}
                                    @if($item->download_expires_at)
                                        · expires {{ $item->download_expires_at->format('M d, Y') }}
                                    @endif
                                </p>
                            @endif
                        </div>
                        <p class="font-bold text-gray-900">৳{{ number_format($item->subtotal) }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-3">Customer</h2>
                <p class="font-medium text-gray-800 text-sm">{{ $order->user->name ?? 'Guest' }}</p>
                <p class="text-sm text-gray-500">{{ $order->user->email ?? $order->guest_email ?? 'N/A' }}</p>
                <p class="text-sm text-gray-500">{{ $order->user->phone ?? 'N/A' }}</p>
            </div>
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <h2 class="font-semibold text-gray-800 mb-3">Shipping Address</h2>
                <div class="text-sm text-gray-600 space-y-1">
                    <p class="font-medium text-gray-800">{{ $order->shipping_name }}</p>
                    <p>{{ $order->shipping_phone }}</p>
                    <p>{{ $order->shipping_address }}</p>
                    <p>{{ $order->shipping_city }}, {{ $order->shipping_state }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        {{-- Fraud Detection Panel --}}
        <div class="bg-white rounded-2xl shadow-sm p-6 border-l-4 {{ $rc['border'] }}">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                        <svg class="w-4 h-4 {{ $rc['text'] }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        Fraud Analysis
                    </h2>
                    @if($order->fraud_checked_at)
                    <p class="text-xs text-gray-400 mt-0.5">Last checked {{ $order->fraud_checked_at->diffForHumans() }}</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.orders.fraud-recheck', $order) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1 px-2 py-1 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Re-check
                    </button>
                </form>
            </div>

            {{-- Score Gauge --}}
            <div class="flex items-center gap-4 mb-4">
                <div class="flex-shrink-0 w-16 h-16 rounded-full {{ $rc['light'] }} flex flex-col items-center justify-center border-2 {{ $rc['border'] }}">
                    <span class="text-xl font-bold {{ $rc['text'] }}">{{ $order->fraud_score ?? 0 }}</span>
                    <span class="text-xs {{ $rc['text'] }}">/ 100</span>
                </div>
                <div class="flex-1">
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-sm font-semibold text-gray-700">Risk Score</span>
                        <span class="text-xs font-bold uppercase tracking-wide {{ $rc['text'] }}">{{ strtoupper($riskLevel) }}</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2">
                        <div class="{{ $rc['bg'] }} h-2 rounded-full transition-all" style="width:{{ $order->fraud_score ?? 0 }}%"></div>
                    </div>
                    <div class="flex justify-between text-xs text-gray-300 mt-1">
                        <span>Low</span><span>Medium</span><span>High</span><span>Critical</span>
                    </div>
                </div>
            </div>

            {{-- Flags --}}
            @if(! empty($order->fraud_flags))
            <div class="space-y-1.5">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Risk Factors</p>
                @foreach($order->fraud_flags as $flag)
                <div class="flex items-start gap-2 text-xs {{ $rc['light'] }} rounded-lg px-3 py-2">
                    <svg class="w-3.5 h-3.5 {{ $rc['text'] }} flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span class="text-gray-700">{{ $flag }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="flex items-center gap-2 text-green-600 text-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                No risk factors detected
            </div>
            @endif
        </div>

        {{-- Courier Fraud Check — the customer's real-world delivery history across
             Bangladeshi couriers, separate from the behavioral score above. Starts from
             whatever was last checked for this phone (server-rendered, no API call just to
             view the page); the button below calls the API fresh via fetch(). --}}
        <div class="bg-white rounded-2xl shadow-sm p-6"
             x-data="courierFraudCheck(@js($courierCheck?->toCheckPayload()))">
            <div class="flex items-start justify-between mb-3">
                <h2 class="font-semibold text-gray-800 flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                    Courier Delivery History
                </h2>
                <button type="button" @click="check()" :disabled="loading"
                        class="text-xs text-indigo-600 hover:text-indigo-800 flex items-center gap-1 px-2 py-1 border border-indigo-200 rounded-lg hover:bg-indigo-50 transition disabled:opacity-50">
                    <svg class="w-3 h-3" :class="loading && 'animate-spin'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span x-text="result ? 'Re-check' : 'Check'"></span>
                </button>
            </div>

            <template x-if="!result">
                <p class="text-sm text-gray-400">{{ $order->shipping_phone ? 'Not checked yet.' : 'No shipping phone on this order to check.' }}</p>
            </template>

            <template x-if="result && !result.success">
                <p class="text-sm text-red-600" x-text="'Check failed: ' + result.error"></p>
            </template>

            <template x-if="result && result.success && result.total_orders === 0">
                <p class="text-sm text-gray-500">No delivery history found on any covered courier — likely a first-time customer.</p>
            </template>

            <template x-if="result && result.success && result.total_orders > 0">
                <div>
                    <div class="flex items-center gap-3 mb-3">
                        <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" :class="result.style.dot"></span>
                        <span class="text-xs px-2.5 py-1 rounded-full font-semibold" :class="[result.style.bg, result.style.text]" x-text="result.style.label"></span>
                        <span class="text-sm text-gray-600" x-text="result.success_rate + '% success (' + result.total_delivered + '/' + result.total_orders + ')'"></span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-2 mb-3">
                        <div class="h-2 rounded-full" :class="result.style.dot" :style="'width:' + result.success_rate + '%'"></div>
                    </div>
                    <template x-if="result.breakdown && Object.keys(result.breakdown).length">
                        <div class="space-y-1">
                            <template x-for="(counts, courier) in result.breakdown" :key="courier">
                                <div class="flex items-center justify-between text-xs text-gray-600">
                                    <span class="capitalize" x-text="courier.replace('_',' ')"></span>
                                    <span><span class="text-green-600" x-text="counts.delivered"></span> / <span class="text-red-600" x-text="counts.cancelled"></span></span>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <template x-if="result && result.provider_errors && Object.keys(result.provider_errors).length">
                <div class="mt-3 pt-3 border-t border-gray-100 space-y-1">
                    <template x-for="(err, providerKey) in (result ? result.provider_errors : {})" :key="providerKey">
                        <p class="text-xs text-gray-400"><span class="capitalize font-medium text-gray-500" x-text="providerKey"></span>: <span x-text="err"></span></p>
                    </template>
                </div>
            </template>

            <p class="text-xs text-gray-400 mt-3" x-show="result && result.checked_at" x-text="result && ('Checked ' + result.checked_at)"></p>
        </div>

        {{-- Update Status — submits via fetch so this doesn't reload the whole order page
             just to change one field; falls back to a normal form post (full reload) if JS
             is unavailable, since the <form> itself is still a real, working form. --}}
        <div class="bg-white rounded-2xl shadow-sm p-6" x-data="ajaxStatusForm()">
            <h2 class="font-semibold text-gray-800 mb-4">Update Status</h2>
            <form action="{{ route('admin.orders.status', $order->id) }}" method="POST" @submit.prevent="submit($event)">
                @csrf @method('PATCH')
                <select name="status" :disabled="saving" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-50">
                    @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $s)
                        <option value="{{ $s }}" {{ $order->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button type="submit" :disabled="saving" class="w-full bg-indigo-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-indigo-700 transition disabled:opacity-60 flex items-center justify-center gap-2">
                    <svg x-show="saving" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="saving ? 'Updating…' : 'Update Status'"></span>
                </button>
                <p x-show="message" x-cloak x-text="message" class="text-xs mt-2 text-center" :class="error ? 'text-red-600' : 'text-green-600'"></p>
            </form>
        </div>

        {{-- Update Payment — same fetch-based pattern as Update Status above --}}
        <div class="bg-white rounded-2xl shadow-sm p-6" x-data="ajaxStatusForm()">
            <h2 class="font-semibold text-gray-800 mb-4">Payment Status</h2>
            <form action="{{ route('admin.orders.update', $order->id) }}" method="POST" @submit.prevent="submit($event)">
                @csrf @method('PUT')
                <select name="payment_status" :disabled="saving" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm mb-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:bg-gray-50">
                    @foreach(['pending', 'paid', 'failed', 'refunded'] as $s)
                        <option value="{{ $s }}" {{ $order->payment_status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
                <button type="submit" :disabled="saving" class="w-full bg-green-600 text-white py-2 rounded-xl text-sm font-medium hover:bg-green-700 transition disabled:opacity-60 flex items-center justify-center gap-2">
                    <svg x-show="saving" x-cloak class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <span x-text="saving ? 'Updating…' : 'Update Payment'"></span>
                </button>
                <p x-show="message" x-cloak x-text="message" class="text-xs mt-2 text-center" :class="error ? 'text-red-600' : 'text-green-600'"></p>
            </form>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-2xl shadow-sm p-6">
            <h2 class="font-semibold text-gray-800 mb-4">Order Summary</h2>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-gray-600"><span>Subtotal</span><span>৳{{ number_format($order->subtotal) }}</span></div>
                @if(($order->discount - $order->points_discount_value) > 0)
                    <div class="flex justify-between text-green-600"><span>Discount</span><span>-৳{{ number_format($order->discount - $order->points_discount_value) }}</span></div>
                @endif
                @if($order->points_discount_value > 0)
                    <div class="flex justify-between text-green-600"><span>Points Redeemed ({{ number_format($order->points_redeemed) }} pts)</span><span>-৳{{ number_format($order->points_discount_value) }}</span></div>
                @endif
                <div class="flex justify-between text-gray-600"><span>Shipping</span><span>৳{{ number_format($order->shipping) }}</span></div>
                <div class="border-t border-gray-100 pt-2 flex justify-between font-bold text-gray-900">
                    <span>Total</span><span>৳{{ number_format($order->total) }}</span>
                </div>
                <div class="pt-2 flex justify-between text-sm text-gray-500">
                    <span>Payment Method</span>
                    <span class="capitalize">{{ $order->payment_method === 'cod' ? 'Cash on Delivery' : strtoupper($order->payment_method) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Shared by the Update Status and Payment Status forms above — plain fetch() POST
    // (with Laravel's _method spoofing field, already in the form via the method directive)
    // instead of a normal form submit, so picking a new status doesn't reload the whole page.
    function ajaxStatusForm() {
        return {
            saving: false,
            message: null,
            error: false,
            submit(event) {
                this.saving = true;
                this.message = null;
                this.error = false;
                const form = event.target;

                fetch(form.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json' },
                    body: new FormData(form),
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        this.saving = false;
                        this.error = !response.ok;
                        this.message = data.message
                            || (data.errors ? Object.values(data.errors)[0][0] : null)
                            || (response.ok ? 'Updated.' : 'Something went wrong.');
                        setTimeout(() => { this.message = null; }, 4000);
                    })
                    .catch(() => {
                        this.saving = false;
                        this.error = true;
                        this.message = 'Network error — please try again.';
                        setTimeout(() => { this.message = null; }, 4000);
                    });
            },
        };
    }

    // Courier fraud-check card above — starts from whatever was server-rendered (last known
    // check for this phone, if any) and calls the check endpoint fresh on click.
    function courierFraudCheck(initial) {
        return {
            result: initial,
            loading: false,
            check() {
                this.loading = true;
                fetch(@js(route('admin.orders.fraud-checker', $order)), {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                })
                    .then(async (response) => {
                        this.result = await response.json().catch(() => ({ success: false, error: 'Unexpected response from server.' }));
                    })
                    .catch(() => {
                        this.result = { success: false, error: 'Network error — please try again.' };
                    })
                    .finally(() => { this.loading = false; });
            },
        };
    }
</script>
@endpush
@endsection
