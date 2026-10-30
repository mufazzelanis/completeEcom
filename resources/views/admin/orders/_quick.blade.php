{{-- Orders list → quick view (OrderController::quick). Everything about one order and its
     customer in one panel: call/WhatsApp/copy, order history & success rate, courier record,
     items, money, status stepper, payment, internal note, timeline. --}}
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $money = fn ($v) => '৳' . number_format((float) $v, fmod((float) $v, 1.0) == 0.0 ? 0 : 2);
    $name = $order->shipping_name ?: ($order->user->name ?? 'Guest');
    $phone = $order->shipping_phone ?: $order->user?->phone;
    $digits = preg_replace('/\D/', '', (string) $phone);
    $waDigits = str_starts_with($digits, '01') ? '88' . $digits : (strlen($digits) === 10 && str_starts_with($digits, '1') ? '880' . $digits : $digits);
    $email = $order->user->email ?? $order->guest_email;
    $address = collect([$order->shipping_address, $order->shipping_city, $order->shipping_state, $order->shipping_zip])->filter()->implode(', ');
    $zoneLabel = ['dhaka' => 'Inside Dhaka', 'outside_dhaka' => 'Outside Dhaka'][$order->shipping_zone] ?? null;
    $paid = $order->payment_status === 'paid';
    $collect = $paid ? 0 : ($order->is_preorder ? max(0, $order->total - $order->advance_amount) : (float) $order->total);
    $site = setting('site_name', config('app.name'));
    $itemsLine = $order->items->map(fn ($i) => $i->product_name . ($i->variant_label ? " ({$i->variant_label})" : '') . ' × ' . $i->quantity)->implode(', ');
    $copyText = "Order: {$order->order_number}\nName: {$name}\nPhone: {$phone}\nAddress: {$address}\n"
        . ($zoneLabel ? "Area: {$zoneLabel}\n" : '')
        . "Collect (COD): {$money($collect)}\nItems: {$itemsLine}"
        . ($order->notes ? "\nNote: {$order->notes}" : '');
    $waText = "Assalamu alaikum {$name}, this is {$site}. About your order {$order->order_number} ({$money($order->total)}): ";
    $risk = $stats['rate'] === null ? null : ($stats['rate'] >= 80 ? ['#059669', '#ecfdf5', 'Trusted'] : ($stats['rate'] >= 50 ? ['#d97706', '#fffbeb', 'Mixed'] : ['#dc2626', '#fef2f2', 'Risky']));
    $methodLabel = \App\Services\Notifications\OrderEmail::paymentLabel($order->payment_method);
@endphp
<div x-data="orderQuick(@js([
        'id' => $order->id,
        'status' => $order->status,
        'payment_status' => $order->payment_status,
        'note' => (string) $order->admin_note,
        'copy' => $copyText,
        'courier' => $courierCheck?->toCheckPayload(),
        'urls' => [
            'status' => route('admin.orders.status', $order->id),
            'payment' => route('admin.orders.update', $order->id),
            'note' => route('admin.orders.note', $order->id),
            'courier' => route('admin.orders.fraud-checker', $order->id),
        ],
    ]))" class="oq">

    {{-- Header --}}
    <div class="oq-head">
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                <span class="oq-num">{{ $order->order_number }}</span>
                @if($order->is_preorder)<span class="oq-tag" style="background:#7c3aed;color:#fff">PRE-ORDER</span>@endif
                @if($order->source === 'phone')<span class="oq-tag" style="background:#f3e8ff;color:#7e22ce">Phone order</span>@endif
                @if($order->is_fraud_flagged)<span class="oq-tag" style="background:#fee2e2;color:#b91c1c">⚠ Fraud risk {{ $order->fraud_score }}</span>
                @elseif($order->fraud_score >= 20)<span class="oq-tag" style="background:#fef3c7;color:#b45309">Risk {{ $order->fraud_score }}</span>@endif
            </div>
            <p class="oq-sub">{{ $order->created_at->timezone($tz)->format('d M Y, g:i A') }} · {{ $order->created_at->diffForHumans() }}@if($order->createdBy) · by {{ $order->createdBy->name }}@endif</p>
        </div>
    </div>

    {{-- Status stepper --}}
    <div class="oq-card">
        <div class="oq-steps">
            @foreach(['pending' => 'Pending', 'processing' => 'Confirmed', 'shipped' => 'Shipped', 'delivered' => 'Delivered'] as $key => $label)
                <button type="button" class="oq-step" @click="setStatus('{{ $key }}')" :disabled="busy"
                        :class="{ 'is-done': stepIndex >= {{ $loop->index }} && !closed, 'is-now': status === '{{ $key }}' }">
                    <span class="oq-dot">{{ $loop->iteration }}</span><span class="oq-step-l">{{ $label }}</span>
                </button>
            @endforeach
        </div>
        <div class="flex flex-wrap items-center gap-2 mt-3">
            <template x-if="closed">
                <span class="oq-tag" :style="status === 'cancelled' ? 'background:#fee2e2;color:#b91c1c' : 'background:#e5e7eb;color:#374151'" x-text="status === 'cancelled' ? '✖ Cancelled' : '↩ Refunded'"></span>
            </template>
            <template x-if="nextStep">
                <button type="button" class="oq-btn oq-primary" @click="setStatus(nextStep.key)" :disabled="busy" x-text="nextStep.label"></button>
            </template>
            <button type="button" class="oq-btn" x-show="status !== 'cancelled'" @click="setStatus('cancelled')" :disabled="busy" style="color:#dc2626">Cancel order</button>
            <button type="button" class="oq-btn" x-show="status === 'delivered' || (status === 'cancelled' && payment === 'paid')" x-cloak @click="setStatus('refunded')" :disabled="busy">Refund</button>
            <span class="text-xs text-gray-400 ml-auto" x-show="busy" x-cloak>Saving…</span>
        </div>
    </div>

    {{-- Customer --}}
    <div class="oq-card">
        <div class="flex items-start gap-3">
            <span class="oq-avatar">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
            <div class="min-w-0 flex-1">
                <p class="oq-name">{{ $name }}
                    @if($stats['orders'] <= 1)<span class="oq-tag" style="background:#e0f2fe;color:#0369a1">New customer</span>
                    @elseif($risk)<span class="oq-tag" style="background:{{ $risk[1] }};color:{{ $risk[0] }}">{{ $risk[2] }} buyer</span>@endif
                </p>
                @if($phone)<p class="oq-line"><a href="tel:{{ $digits }}" class="font-semibold" style="color:#2563eb">{{ $phone }}</a></p>@endif
                @if($email)<p class="oq-line truncate">{{ $email }}</p>@endif
                @if($order->user)<p class="oq-line" style="color:#9ca3af">Registered account</p>@else<p class="oq-line" style="color:#9ca3af">Guest checkout</p>@endif
            </div>
        </div>
        @if($phone)
        <div class="grid grid-cols-3 gap-2 mt-3">
            <a href="tel:{{ $digits }}" class="oq-act" style="color:#2563eb">📞 Call</a>
            <a href="https://wa.me/{{ $waDigits }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener" class="oq-act" style="color:#16a34a">💬 WhatsApp</a>
            <button type="button" class="oq-act" @click="copy(@js((string) $phone), 'Phone copied')">📋 Copy</button>
        </div>
        @endif

        <div class="oq-stats">
            <div><b>{{ $stats['orders'] }}</b><span>Orders</span></div>
            <div><b style="color:#059669">{{ $stats['delivered'] }}</b><span>Delivered</span></div>
            <div><b style="color:#dc2626">{{ $stats['cancelled'] }}</b><span>Cancelled</span></div>
            <div><b>{{ $stats['rate'] === null ? '—' : $stats['rate'] . '%' }}</b><span>Success</span></div>
        </div>
        @if($stats['spent'] > 0 || $stats['first'])
            <p class="oq-line mt-2">Spent {{ $money($stats['spent']) }} on delivered orders @if($stats['first'])· customer since {{ $stats['first']->timezone($tz)->format('M Y') }}@endif</p>
        @endif

        {{-- courier record (all BD couriers) --}}
        <div class="oq-courier">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Courier record</span>
                @if($order->shipping_phone)
                <button type="button" class="oq-mini" @click="checkCourier()" :disabled="courierBusy" x-text="courierBusy ? 'Checking…' : (courier ? 'Re-check' : 'Check now')"></button>
                @endif
            </div>
            <template x-if="!courier"><p class="oq-line mt-1">Not checked yet.</p></template>
            <template x-if="courier && !courier.success"><p class="oq-line mt-1" style="color:#dc2626" x-text="'Check failed: ' + courier.error"></p></template>
            <template x-if="courier && courier.success && courier.total_orders === 0"><p class="oq-line mt-1">No courier history — likely first-time buyer.</p></template>
            <template x-if="courier && courier.success && courier.total_orders > 0">
                <div class="mt-1.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold" x-text="courier.style.label"></span>
                        <span class="text-gray-500" x-text="courier.success_rate + '% · ' + courier.total_delivered + '/' + courier.total_orders + ' delivered'"></span>
                    </div>
                    <div class="oq-bar"><span :style="'width:' + courier.success_rate + '%;background:' + (courier.success_rate >= 80 ? '#10b981' : courier.success_rate >= 50 ? '#f59e0b' : '#ef4444')"></span></div>
                </div>
            </template>
        </div>
    </div>

    {{-- Delivery address --}}
    <div class="oq-card">
        <div class="flex items-center justify-between gap-2 mb-1">
            <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Deliver to</span>
            <button type="button" class="oq-mini" @click="copy(cfg.copy, 'Courier details copied')" title="Name, phone, address, COD amount and items — ready to paste into the courier panel">Copy for courier</button>
        </div>
        <p class="text-sm text-gray-800 dark:text-gray-100 font-medium">{{ $order->shipping_name }}</p>
        <p class="text-sm text-gray-600 dark:text-gray-300">{{ $address ?: '—' }}</p>
        @if($zoneLabel)<p class="oq-line mt-1">🚚 {{ $zoneLabel }}</p>@endif
        @if($order->notes)
            <div class="oq-note-c">📝 <b>Customer note:</b> {{ $order->notes }}</div>
        @endif
    </div>

    {{-- Items --}}
    <div class="oq-card">
        <span class="text-xs font-bold uppercase tracking-wide text-gray-500">{{ $order->items->sum('quantity') }} item(s)</span>
        <div class="mt-2 space-y-2.5">
            @foreach($order->items as $item)
                <div class="flex items-center gap-3">
                    <div class="oq-thumb">@if($item->product?->image)<img src="{{ image_thumb($item->product->image, 96) }}" alt="" loading="lazy">@endif</div>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100 oq-clamp">{{ $item->product_name }}</p>
                        <p class="oq-line">@if($item->variant_label){{ $item->variant_label }} · @endif{{ $money($item->price) }} × {{ $item->quantity }}</p>
                    </div>
                    <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $money($item->subtotal) }}</p>
                </div>
            @endforeach
        </div>

        <dl class="oq-sum">
            <div><dt>Subtotal</dt><dd>{{ $money($order->subtotal) }}</dd></div>
            @if($order->discount > 0)<div><dt>Discount @if($order->coupon_code)<span class="oq-tag" style="background:#ecfdf5;color:#047857">{{ $order->coupon_code }}</span>@endif</dt><dd style="color:#059669">−{{ $money($order->discount) }}</dd></div>@endif
            @if($order->points_discount_value > 0)<div><dt>Points</dt><dd style="color:#059669">−{{ $money($order->points_discount_value) }}</dd></div>@endif
            <div><dt>Delivery</dt><dd>{{ $order->shipping > 0 ? $money($order->shipping) : 'Free' }}</dd></div>
            @if($order->payment_charge > 0)<div><dt>Payment charge</dt><dd>{{ $money($order->payment_charge) }}</dd></div>@endif
            @if($order->tax > 0)<div><dt>Tax</dt><dd>{{ $money($order->tax) }}</dd></div>@endif
            <div class="oq-total"><dt>Total</dt><dd>{{ $money($order->total) }}</dd></div>
            @if($order->is_preorder)
                <div><dt>Advance paid</dt><dd style="color:#7c3aed">{{ $money($order->advance_amount) }}</dd></div>
            @endif
        </dl>
        <div class="oq-collect" :style="payment === 'paid' ? 'background:#ecfdf5;color:#047857' : ''">
            <span x-text="payment === 'paid' ? '✓ Fully paid' : 'Collect on delivery'"></span>
            <b x-text="payment === 'paid' ? '' : @js($money($order->is_preorder ? max(0, $order->total - $order->advance_amount) : $order->total))"></b>
        </div>
    </div>

    {{-- Payment --}}
    <div class="oq-card">
        <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Payment · {{ $methodLabel }}</span>
            @if($order->payment && $order->payment->status === 'pending_verification')
                <a href="{{ route('admin.payments.show', $order->payment->id) }}" class="oq-mini" style="color:#ea580c">Verify →</a>
            @endif
        </div>
        @if($order->payment && ($order->payment->transaction_id || $order->payment->sender_number))
            <p class="oq-line mt-1">
                @if($order->payment->transaction_id)TrxID <b class="select-all">{{ $order->payment->transaction_id }}</b>@endif
                @if($order->payment->sender_number) · from {{ $order->payment->sender_number }}@endif
                · {{ $money($order->payment->amount) }}
            </p>
        @endif
        <div class="oq-seg mt-2">
            @foreach(['pending' => 'Pending', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $key => $label)
                <button type="button" @click="setPayment('{{ $key }}')" :class="payment === '{{ $key }}' && 'is-on is-{{ $key }}'" :disabled="busy">{{ $label }}</button>
            @endforeach
        </div>
    </div>

    {{-- Internal note --}}
    <div class="oq-card">
        <div class="flex items-center justify-between">
            <label for="oq-note-{{ $order->id }}" class="text-xs font-bold uppercase tracking-wide text-gray-500">Internal note</label>
            <span class="text-[11px] text-gray-400" x-text="noteState"></span>
        </div>
        <textarea id="oq-note-{{ $order->id }}" x-model="note" @input.debounce.900ms="saveNote()" rows="2" class="oq-textarea"
                  placeholder="Only staff see this — e.g. “Called, confirmed for Friday”"></textarea>
    </div>

    {{-- Other orders by this customer --}}
    @if($history->isNotEmpty())
    <div class="oq-card">
        <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Other orders by this customer</span>
        <div class="mt-2 divide-y divide-gray-100 dark:divide-gray-800">
            @foreach($history as $h)
                <button type="button" class="oq-hist" @click="$dispatch('oq-open', {{ $h->id }})">
                    <span class="font-semibold" style="color:#ea580c">{{ $h->order_number }}</span>
                    <span class="text-gray-400 text-xs">{{ $h->created_at->timezone($tz)->format('d M y') }}</span>
                    <span class="ml-auto font-semibold text-gray-800 dark:text-gray-100">{{ $money($h->total) }}</span>
                    <span class="oq-st oq-st-{{ $h->status }}">{{ ucfirst($h->status) }}</span>
                </button>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Timeline --}}
    <div class="oq-card">
        <span class="text-xs font-bold uppercase tracking-wide text-gray-500">Timeline</span>
        <ol class="oq-tl mt-2">
            @foreach($timeline as $t)
                <li><span class="oq-tl-dot"></span>
                    <p class="text-sm text-gray-700 dark:text-gray-200">{{ \Illuminate\Support\Str::of($t->description)->replace('Order ' . $order->order_number . ' ', '')->ucfirst() }}</p>
                    <p class="oq-line">{{ $t->created_at->timezone($tz)->format('d M, g:i A') }}@if($t->user) · {{ $t->user->name }}@endif</p>
                </li>
            @endforeach
            <li><span class="oq-tl-dot" style="background:#f97316"></span>
                <p class="text-sm text-gray-700 dark:text-gray-200">Order placed ({{ $methodLabel }})</p>
                <p class="oq-line">{{ $order->created_at->timezone($tz)->format('d M, g:i A') }}</p>
            </li>
        </ol>
    </div>

    {{-- Footer actions --}}
    <div class="oq-foot">
        <a href="{{ route('admin.orders.show', $order->id) }}" class="oq-btn">Full details</a>
        <a href="{{ route('admin.orders.invoice', $order->id) }}" data-turbo="false" class="oq-btn">⬇ Invoice PDF</a>
        @if($order->guest_token)
            <button type="button" class="oq-btn" @click="copy(@js(route('guest.order.track', ['order_number' => $order->order_number, 'token' => $order->guest_token])), 'Tracking link copied')">🔗 Tracking link</button>
        @endif
    </div>
</div>
