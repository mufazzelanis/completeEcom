@extends('layouts.admin')
@section('title', 'Incomplete Checkouts')

@section('content')
<style>
    .cl-cards { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1rem; }
    @media (min-width: 1024px) { .cl-cards { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    .cl-xs { font-size: 11px; }
    .cl-act { display: inline-flex; align-items: center; gap: .4rem; padding: .5rem .8rem; border-radius: .7rem; font-size: .8rem; font-weight: 700; transition: filter .15s, transform .15s; }
    .cl-act:hover { filter: brightness(1.05); transform: translateY(-1px); }
</style>
@php
    $money = fn ($v) => '৳' . number_format((float) $v);
    $site = setting('site_name', config('app.name'));
@endphp

<div class="flex flex-wrap items-end justify-between gap-3 mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800 dark:text-gray-100">Incomplete Checkouts</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Shoppers who entered their phone number at checkout but didn't place the order — a quick call usually brings many of them back.</p>
    </div>
</div>

<div class="cl-cards mb-6">
    @foreach([
        ['Waiting for a call', number_format($stats['open']), $money($stats['openValue']) . ' in carts', '#ef4444', '📞'],
        ['Recovered this month', number_format($stats['recovered']), $money($stats['recoveredValue']) . ' in orders', '#10b981', '🎉'],
        ['Recovery rate', $stats['rate'] . '%', 'of ' . number_format($stats['total']) . ' this month', '#6366f1', '📈'],
        ['Reminder SMS', $autoSms ? 'On' : 'Off', $autoSms ? 'after ' . $autoSmsMinutes . ' min' : 'send them yourself', $autoSms ? '#0ea5e9' : '#94a3b8', '✉️'],
    ] as [$label, $value, $sub, $color, $icon])
        <div class="relative overflow-hidden bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5">
            <div class="absolute -right-5 -top-5 w-20 h-20 rounded-full" style="background: {{ $color }}14;"></div>
            <div class="text-2xl mb-2">{{ $icon }}</div>
            <p class="text-2xl font-extrabold tabular-nums" style="color: {{ $color }};">{{ $value }}</p>
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ $label }}</p>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $sub }}</p>
        </div>
    @endforeach
</div>

{{-- tabs + search --}}
<div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-3 mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap gap-1">
        @foreach(['open' => 'To follow up', 'recovered' => 'Ordered', 'dismissed' => 'Dismissed', 'all' => 'All'] as $key => $label)
            <a href="{{ route('admin.checkout-leads.index', array_filter(['status' => $key, 'search' => request('search')])) }}"
               class="px-3.5 py-2 rounded-xl text-sm font-semibold transition {{ $status === $key ? 'bg-indigo-600 text-white' : 'text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800' }}">{{ $label }}</a>
        @endforeach
    </div>
    <form method="GET" class="flex gap-2">
        <input type="hidden" name="status" value="{{ $status }}">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Phone or name…"
               class="border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
    </form>
</div>

<div class="space-y-3">
    @forelse($leads as $lead)
        @php
            [$statusLabel, $statusColor] = $lead->statusMeta();
            $items = collect($lead->items);
            $live = $lead->isStillOnCheckout();
            $first = trim(strtok((string) $lead->name, ' ')) ?: '';
            $waText = 'আসসালামু আলাইকুম' . ($first ? ' ' . $first : '') . ", {$site} থেকে বলছি। আপনি আমাদের সাইটে " . \Illuminate\Support\Str::limit($items->first()['name'] ?? 'পণ্য', 50) . ($items->count() > 1 ? ' সহ ' . $items->count() . 'টি পণ্য' : '') . " অর্ডার করতে চেয়েছিলেন — কোনো সমস্যা হয়েছে কি? আমরা অর্ডারটি সম্পন্ন করতে সাহায্য করতে পারি।";
        @endphp
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-4 sm:p-5" style="border-left: 4px solid {{ $live ? '#0ea5e9' : $statusColor }};"
             x-data="{ status: @js($lead->status), label: @js($statusLabel), color: @js($statusColor), note: @js($lead->note ?? ''), editing: false, saving: false,
                async save(payload) {
                    this.saving = true;
                    try {
                        const r = await fetch(@js(route('admin.checkout-leads.update', $lead)), { method: 'PATCH', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }, body: JSON.stringify(payload) });
                        if (r.ok) { const d = await r.json(); this.status = d.status; this.label = d.label; this.color = d.color; this.editing = false; }
                    } finally { this.saving = false; }
                } }">
            <div class="flex flex-wrap items-start gap-4">
                {{-- who --}}
                <div class="min-w-0" style="flex: 1 1 14rem;">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-bold text-gray-900 dark:text-gray-100">{{ $lead->name ?: 'No name yet' }}</p>
                        <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 cl-xs font-semibold" :style="'background:' + color + '1a;color:' + color">
                            <span class="w-1.5 h-1.5 rounded-full" :style="'background:' + color"></span><span x-text="label">{{ $statusLabel }}</span>
                        </span>
                        @if($live)
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 cl-xs font-semibold" style="background:#0ea5e91a;color:#0284c7;">
                                <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full" style="background:#38bdf8"></span><span class="relative inline-flex rounded-full h-2 w-2" style="background:#0ea5e9"></span></span>
                                on checkout now
                            </span>
                        @endif
                    </div>
                    <a href="tel:{{ $lead->phone }}" class="font-mono text-lg font-semibold hover:underline" style="color:#6366f1;">{{ $lead->phone }}</a>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $lead->last_activity_at->diffForHumans() }}
                        @if($lead->zone) · {{ $lead->zone === 'dhaka' ? 'Inside Dhaka' : 'Outside Dhaka' }}@endif
                        @if($lead->sms_sent_at) · ✉️ SMS {{ $lead->sms_sent_at->diffForHumans() }}@endif
                    </p>
                    @if($lead->address || $lead->city)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">📍 {{ collect([$lead->address, $lead->city])->filter()->implode(', ') }}</p>
                    @endif
                </div>

                {{-- cart --}}
                <div class="min-w-0" style="flex: 2 1 18rem;">
                    <div class="flex flex-wrap gap-2">
                        @foreach($items->take(4) as $item)
                            <div class="flex items-center gap-2 rounded-xl bg-gray-50 dark:bg-gray-800 pr-3 max-w-full">
                                <div class="w-11 h-11 flex-shrink-0 rounded-xl overflow-hidden bg-white dark:bg-gray-700">
                                    @if(!empty($item['image']))<img src="{{ image_thumb($item['image'], 200) }}" alt="" class="w-full h-full object-contain" loading="lazy">@endif
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate" style="max-width: 12rem;">{{ $item['name'] }}</p>
                                    <p class="cl-xs text-gray-500 dark:text-gray-400">{{ $item['qty'] }} × {{ $money($item['price']) }}{{ !empty($item['variant']) ? ' · ' . $item['variant'] : '' }}</p>
                                </div>
                            </div>
                        @endforeach
                        @if($items->count() > 4)<span class="text-xs text-gray-400 self-center">+{{ $items->count() - 4 }} more</span>@endif
                    </div>
                    <p class="text-sm mt-2 text-gray-600 dark:text-gray-300">Cart total <strong class="text-gray-900 dark:text-gray-100">{{ $money($lead->subtotal) }}</strong>
                        @if($lead->order)· <a href="{{ route('admin.orders.show', $lead->order) }}" class="font-semibold hover:underline" style="color:#059669;">ordered {{ $lead->order->order_number }} →</a>@endif
                    </p>
                </div>

                {{-- actions --}}
                <div class="flex flex-wrap gap-2 items-start" style="flex: 0 1 auto;">
                    <a href="tel:{{ $lead->phone }}" @click="if (status === 'open') save({ status: 'contacted' })" class="cl-act text-white" style="background:#4f46e5;">📞 Call</a>
                    <a href="https://wa.me/{{ $lead->waPhone() }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener" @click="if (status === 'open') save({ status: 'contacted' })" class="cl-act text-white" style="background:#25d366;">WhatsApp</a>
                    <form method="POST" action="{{ route('admin.checkout-leads.sms', $lead) }}">
                        @csrf
                        <button class="cl-act" style="background:#0ea5e91a;color:#0284c7;{{ $smsReady ? '' : 'opacity:.5;cursor:not-allowed;' }}"
                                @if(!$smsReady) disabled title="Set up an SMS gateway first (SMS_DRIVER in .env)" @else title="Send the reminder SMS" @endif>✉️ SMS</button>
                    </form>
                    <template x-if="status === 'open' || status === 'contacted'">
                        <button type="button" @click="save({ status: 'dismissed' })" class="cl-act text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-300" title="Not interested / wrong number">Dismiss</button>
                    </template>
                    <template x-if="status === 'dismissed'">
                        <button type="button" @click="save({ status: 'open' })" class="cl-act text-gray-500 bg-gray-100 dark:bg-gray-800 dark:text-gray-300">Reopen</button>
                    </template>
                </div>
            </div>

            {{-- note --}}
            <div class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 text-sm">
                <p x-show="!editing" class="text-gray-600 dark:text-gray-300">
                    <span x-show="note" x-text="'📝 ' + note"></span>
                    <button type="button" @click="editing = true; $nextTick(() => $refs.note.focus())" class="text-xs font-semibold hover:underline" style="color:#6366f1;" x-text="note ? 'edit' : '+ Add a note (e.g. will order tomorrow)'"></button>
                </p>
                <form x-show="editing" x-cloak @submit.prevent="save({ note: note })" class="flex gap-2">
                    <input x-ref="note" x-model="note" maxlength="500" placeholder="What did they say?" class="flex-1 border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <button class="cl-act text-white" style="background:#4f46e5;" :disabled="saving">Save</button>
                </form>
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-12 text-center">
            <div class="text-5xl mb-3">{{ $status === 'open' ? '🎉' : '📭' }}</div>
            <p class="font-semibold text-gray-800 dark:text-gray-200">{{ $status === 'open' ? 'No one to follow up right now' : 'Nothing here yet' }}</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">When a shopper enters a valid phone number at checkout and leaves without ordering, they show up here within seconds.</p>
        </div>
    @endforelse
</div>
<div class="mt-4">{{ $leads->links() }}</div>

{{-- reminder settings --}}
<details class="mt-8 bg-white dark:bg-gray-900 rounded-2xl shadow-sm p-5" @if($errors->any()) open @endif>
    <summary class="cursor-pointer font-semibold text-gray-800 dark:text-gray-100">✉️ Automatic reminder SMS</summary>
    @unless($smsReady)
        <p class="mt-3 text-sm rounded-xl px-4 py-3" style="background:#fef3c7;color:#92400e;">No SMS gateway is set up yet (SMS_DRIVER in .env), so SMS can't be sent — Call and WhatsApp work as usual.</p>
    @endunless
    <form method="POST" action="{{ route('admin.checkout-leads.settings') }}" class="mt-4 space-y-4 max-w-2xl">
        @csrf
        <label class="flex items-center gap-3 text-sm text-gray-700 dark:text-gray-200">
            <input type="checkbox" name="auto_sms" value="1" @checked($autoSms) class="rounded">
            Send one reminder SMS automatically (9 AM – 9:30 PM, once per number per day)
        </label>
        <label class="block text-sm text-gray-700 dark:text-gray-200">
            Minutes after they leave the checkout
            <input type="number" name="minutes" min="15" max="1440" value="{{ old('minutes', $autoSmsMinutes) }}" class="mt-1 block w-32 border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl px-3 py-2 text-sm">
        </label>
        <label class="block text-sm text-gray-700 dark:text-gray-200">
            Message <span class="text-gray-400">— {name} {items} {site} {link} {phone}</span>
            <textarea name="text" rows="3" maxlength="300" class="mt-1 block w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-800 rounded-xl px-3 py-2 text-sm">{{ old('text', $smsTemplate) }}</textarea>
        </label>
        @error('text')<p class="text-xs text-red-500">{{ $message }}</p>@enderror
        <button class="cl-act text-white" style="background:#4f46e5;">Save settings</button>
    </form>
</details>
@endsection
