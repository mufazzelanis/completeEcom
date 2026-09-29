@extends('layouts.admin')
@section('title', 'Orders')

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-900 dark:text-white">Orders</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $orders->total() }} order{{ $orders->total() === 1 ? '' : 's' }} total</p>
    </div>
    <div class="flex items-center gap-2 flex-wrap">
        @if($flaggedCount > 0)
        <a href="{{ route('admin.orders.index', ['fraud' => 1]) }}"
           class="flex items-center gap-2 bg-red-50 dark:bg-red-500/10 border border-red-200 dark:border-red-900 text-red-700 dark:text-red-400 px-3.5 py-2 rounded-xl text-sm font-medium hover:bg-red-100 dark:hover:bg-red-500/20 transition {{ request('fraud') ? 'ring-2 ring-red-400 dark:ring-red-600' : '' }}">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
            Fraud Flagged <span class="bg-red-600 text-white text-xs px-1.5 py-0.5 rounded-full font-bold">{{ $flaggedCount }}</span>
        </a>
        @endif
        <a href="{{ route('admin.orders.create') }}" class="group relative overflow-hidden flex items-center gap-1.5 bg-gradient-to-br from-orange-500 to-orange-700 text-white px-4 py-2 rounded-xl text-sm font-semibold shadow-md hover:shadow-lg transition-shadow">
            <span class="pointer-events-none absolute inset-y-0 left-0 w-1/3 -skew-x-12 bg-white/25 -translate-x-[150%] group-hover:translate-x-[350%] transition-transform duration-700 ease-out"></span>
            <svg class="relative w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span class="relative">Create Order</span>
        </a>
    </div>
</div>

{{-- Filters --}}
<form action="{{ route('admin.orders.index') }}" method="GET"
      class="flex items-center flex-wrap gap-2.5 mb-5 bg-white dark:bg-gray-900 ring-1 ring-gray-100 dark:ring-gray-800 rounded-2xl shadow-sm p-3">
    <div class="relative flex-1 min-w-[180px]">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search order or customer..."
            class="w-full border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-800 dark:text-gray-100 rounded-xl pl-9 pr-4 py-2 text-sm focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-400 transition">
    </div>
    <select name="status" class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-400 transition">
        <option value="">All Status</option>
        @foreach(['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
        @endforeach
        <option value="cancelled,refunded" {{ request('status') === 'cancelled,refunded' ? 'selected' : '' }}>Cancelled / Refunded</option>
    </select>
    <select name="payment_status" class="border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-200 rounded-xl px-3.5 py-2 text-sm focus:outline-none focus:ring-4 focus:ring-orange-500/10 focus:border-orange-400 transition">
        <option value="">All Payments</option>
        @foreach(['pending', 'paid', 'failed', 'refunded'] as $ps)
            <option value="{{ $ps }}" {{ request('payment_status') === $ps ? 'selected' : '' }}>{{ ucfirst($ps) }}</option>
        @endforeach
    </select>
    <button type="submit" class="bg-gray-800 dark:bg-gray-700 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-gray-900 dark:hover:bg-gray-600 transition">Filter</button>
    @if(request()->hasAny(['search', 'status', 'payment_status']))
        <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition px-1">Clear</a>
    @endif
</form>

<div class="bg-white dark:bg-gray-900 ring-1 ring-gray-100 dark:ring-gray-800 rounded-2xl shadow-sm overflow-x-auto">
    <table class="w-full">
        <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-800">
            <tr class="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                <th class="px-6 py-3 text-left">Order</th>
                <th class="px-6 py-3 text-left">Customer</th>
                <th class="px-6 py-3 text-right">Total</th>
                <th class="px-6 py-3 text-center">Payment</th>
                <th class="px-6 py-3 text-center">Status</th>
                <th class="px-6 py-3 text-center">Date</th>
                <th class="px-6 py-3 text-center">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50 dark:divide-gray-800/60">
            @forelse($orders as $order)
                @php
                    $custName = $order->user->name ?? 'Guest';
                    $avatarPalette = ['bg-blue-500', 'bg-emerald-500', 'bg-violet-500', 'bg-amber-500', 'bg-rose-500', 'bg-cyan-500'];
                    $avatarColor = $avatarPalette[crc32($custName) % count($avatarPalette)];
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="font-semibold text-orange-600 dark:text-orange-400 text-sm hover:text-orange-700 dark:hover:text-orange-300">{{ $order->order_number }}</a>
                            @if($order->source === 'phone')
                            <span title="Manually entered — phone/offline order" class="text-[10px] font-semibold bg-purple-50 dark:bg-purple-500/15 text-purple-600 dark:text-purple-400 px-1.5 py-0.5 rounded-full flex items-center gap-0.5">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                Phone
                            </span>
                            @endif
                            @if($order->is_fraud_flagged)
                            <span title="Fraud Flagged — Score: {{ $order->fraud_score }}">
                                <svg class="w-4 h-4 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            </span>
                            @elseif($order->fraud_checked_at && $order->fraud_score >= 20)
                            <span class="text-xs text-yellow-500 font-medium" title="Medium fraud risk — Score: {{ $order->fraud_score }}">⚠</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2.5">
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-white text-[11px] font-bold flex-shrink-0 {{ $avatarColor }}">{{ strtoupper(substr($custName, 0, 1)) }}</span>
                            <span class="text-sm text-gray-700 dark:text-gray-300 truncate">{{ $custName }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right font-semibold text-gray-900 dark:text-gray-100 text-sm">৳{{ number_format($order->total) }}</td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-1.5">
                            <div x-data="inlineStatusDropdown({
                                    url: '{{ route('admin.orders.update', $order->id) }}',
                                    method: 'PUT', field: 'payment_status',
                                    current: '{{ $order->payment_status }}',
                                    options: { pending: 'Pending', paid: 'Paid', failed: 'Failed', refunded: 'Refunded' },
                                    badgeMap: PAYMENT_BADGE_CLASSES,
                                 })">
                                <button type="button" @click="toggle($event)" :disabled="saving"
                                        class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-1 rounded-full text-xs font-medium capitalize transition disabled:opacity-60"
                                        :class="badgeMap[current]">
                                    <span x-text="options[current]"></span>
                                    <svg class="w-3 h-3 opacity-60 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <template x-teleport="body">
                                    <div x-show="open" x-cloak @click.outside="close()" :style="panelStyle"
                                         class="fixed z-[200] w-36 bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 py-1.5 text-sm"
                                         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                        <template x-for="(label, key) in options" :key="key">
                                            <button type="button" @click="select(key)"
                                                    class="w-full flex items-center justify-between px-3.5 py-2 text-left capitalize hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
                                                    :class="key === current ? 'text-gray-900 dark:text-white font-semibold' : 'text-gray-600 dark:text-gray-300'">
                                                <span x-text="label"></span>
                                                <svg x-show="key === current" x-cloak class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                            @if($order->payment && $order->payment->status === 'pending_verification')
                            <a href="{{ route('admin.payments.show', $order->payment->id) }}"
                               title="Manual verification needed — {{ $order->payment->payment_method_name }}, Txn: {{ $order->payment->transaction_id ?? 'N/A' }}"
                               class="flex-shrink-0 inline-flex items-center gap-1 bg-orange-50 dark:bg-orange-500/15 text-orange-600 dark:text-orange-400 text-[11px] font-semibold px-2 py-1 rounded-full hover:bg-orange-100 dark:hover:bg-orange-500/25 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Verify
                            </a>
                            @endif
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <div x-data="inlineStatusDropdown({
                                url: '{{ route('admin.orders.status', $order->id) }}',
                                method: 'PATCH', field: 'status',
                                current: '{{ $order->status }}',
                                options: { pending: 'Pending', processing: 'Processing', shipped: 'Shipped', delivered: 'Delivered', cancelled: 'Cancelled', refunded: 'Refunded' },
                                badgeMap: STATUS_BADGE_CLASSES,
                             })" class="inline-block">
                            <button type="button" @click="toggle($event)" :disabled="saving"
                                    class="inline-flex items-center gap-1 pl-2.5 pr-1.5 py-1 rounded-full text-xs font-medium capitalize transition disabled:opacity-60"
                                    :class="badgeMap[current]">
                                <span x-text="options[current]"></span>
                                <svg class="w-3 h-3 opacity-60 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <template x-teleport="body">
                                <div x-show="open" x-cloak @click.outside="close()" :style="panelStyle"
                                     class="fixed z-[200] w-36 bg-white dark:bg-gray-800 rounded-xl shadow-xl ring-1 ring-gray-200 dark:ring-gray-700 py-1.5 text-sm"
                                     x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                                    <template x-for="(label, key) in options" :key="key">
                                        <button type="button" @click="select(key)"
                                                class="w-full flex items-center justify-between px-3.5 py-2 text-left capitalize hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors"
                                                :class="key === current ? 'text-gray-900 dark:text-white font-semibold' : 'text-gray-600 dark:text-gray-300'">
                                            <span x-text="label"></span>
                                            <svg x-show="key === current" x-cloak class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </td>
                    <td class="px-6 py-4 text-center text-xs text-gray-500 dark:text-gray-400">{{ $order->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4 text-center">
                        <div class="flex items-center justify-center gap-3">
                            <a href="{{ route('admin.orders.show', $order->id) }}" class="text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 text-sm font-medium">View</a>
                            <a href="{{ route('admin.orders.invoice', $order->id) }}" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 text-sm font-medium flex items-center gap-1" title="Download Invoice PDF">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                PDF
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-16 text-center">
                    <svg class="w-10 h-10 mx-auto text-gray-200 dark:text-gray-700 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    <p class="text-sm text-gray-400 dark:text-gray-500">No orders found.</p>
                </td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800">{{ $orders->links() }}</div>
</div>

@push('scripts')
<script>
    // Custom dropdown (not a native <select>) for the inline Status/Payment editors on
    // the orders list — a native select's own arrow rendered awkwardly cramped against
    // the colored pill, and its options menu can't be styled at all. Submits via fetch
    // to the SAME admin.orders.status / admin.orders.update routes the detail page's own
    // forms use, then recolors the pill in place. These exact class strings already exist
    // literally in Order::getStatusBadgeAttribute()/getPaymentStatusBadgeAttribute(), so
    // Tailwind has already compiled them — nothing here is an unsafelisted dynamic class.
    window.STATUS_BADGE_CLASSES = {
        pending:    'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-400',
        processing: 'bg-blue-100 text-blue-800 dark:bg-blue-500/15 dark:text-blue-400',
        shipped:    'bg-purple-100 text-purple-800 dark:bg-purple-500/15 dark:text-purple-400',
        delivered:  'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-400',
        cancelled:  'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-400',
        refunded:   'bg-gray-100 text-gray-800 dark:bg-gray-500/15 dark:text-gray-400',
    };
    window.PAYMENT_BADGE_CLASSES = {
        pending:  'bg-yellow-100 text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-400',
        paid:     'bg-green-100 text-green-800 dark:bg-green-500/15 dark:text-green-400',
        failed:   'bg-red-100 text-red-800 dark:bg-red-500/15 dark:text-red-400',
        refunded: 'bg-gray-100 text-gray-800 dark:bg-gray-500/15 dark:text-gray-400',
    };

    // The panel is x-teleport'd to <body> (escaping the table's overflow-x-auto, which
    // would otherwise clip it exactly like the admin sidebar's flyout menus did) so its
    // position has to be computed from the trigger button's own screen position on open.
    function inlineStatusDropdown({ url, method, field, current, options, badgeMap }) {
        return {
            open: false,
            saving: false,
            current,
            options,
            badgeMap,
            panelTop: 0,
            panelLeft: 0,
            toggle(event) {
                if (this.saving) return;
                this.open ? this.close() : this.openMenu(event);
            },
            openMenu(event) {
                const rect = event.currentTarget.getBoundingClientRect();
                this.panelTop = rect.bottom + 6;
                this.panelLeft = rect.left;
                this.open = true;
            },
            close() { this.open = false; },
            get panelStyle() {
                return `top:${this.panelTop}px; left:${this.panelLeft}px;`;
            },
            async select(key) {
                this.open = false;
                if (key === this.current) return;
                const prev = this.current;
                this.current = key;
                this.saving = true;

                const body = new FormData();
                body.append('_token', document.querySelector('meta[name="csrf-token"]').content);
                body.append('_method', method);
                body.append(field, key);

                try {
                    const res = await fetch(url, { method: 'POST', body, headers: { 'Accept': 'application/json' } });
                    if (!res.ok) throw new Error('Request failed');
                    ordersListToast(true, (field === 'status' ? 'Order status' : 'Payment status') + ' updated.');
                } catch (err) {
                    this.current = prev;
                    ordersListToast(false, 'Could not update — please try again.');
                } finally {
                    this.saving = false;
                }
            },
        };
    }

    function ordersListToast(success, message) {
        document.getElementById('orders-list-toast')?.remove();
        const toast = document.createElement('div');
        toast.id = 'orders-list-toast';
        toast.className = `fixed top-20 right-5 z-[100] px-4 py-3 rounded-xl text-sm font-medium shadow-lg text-white ${success ? 'bg-green-600' : 'bg-red-600'}`;
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
</script>
@endpush
@endsection
