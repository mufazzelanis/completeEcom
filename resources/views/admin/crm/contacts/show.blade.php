@extends('layouts.admin')
@section('title', 'CRM · ' . $contact->name)

@php
    use App\Support\CrmUi as Ui;
    use App\Models\Crm\CrmActivity;
    $canManage = auth()->user()->hasPermission('crm.manage');
    $lc = Ui::lifecycle($contact->lifecycle_stage);
    $sel = 'w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400';
    $waNumber = $contact->phone ? '88' . ltrim($contact->phone, '+') : null; // 01XXXXXXXXX → 8801XXXXXXXXX
    if ($waNumber && str_starts_with($contact->phone, '880')) { $waNumber = $contact->phone; }
    $icons = [
        'note' => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
        'call' => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
        'email' => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
        'sms' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'whatsapp' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
        'meeting' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        'order' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z',
        'task' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
        'lead' => 'M13 10V3L4 14h7v7l9-11h-7z',
    ];
@endphp

@section('content')
@include('admin.crm._tabs')

<script>
    window.CRM_CFG = {!! Js::from([
        'name' => $contact->name,
        'first' => explode(' ', trim($contact->name))[0],
        'wa' => $waNumber,
        'phone' => $contact->phone,
        'email' => $contact->email,
        'site' => setting('site_name', 'our store'),
        'siteUrl' => url('/'),
        'logUrl' => route('admin.crm.contacts.activities.store', $contact),
        'token' => csrf_token(),
        'coupons' => $coupons->map(fn ($c) => ['code' => $c->code, 'label' => $c->code . ' — ' . ($c->type === 'percentage' ? rtrim(rtrim(number_format($c->value, 2), '0'), '.') . '% off' : '৳' . number_format($c->value) . ' off')])->values(),
        'name_tag' => $contact->name,
    ]) !!};
    function contactPage() {
        var cfg = window.CRM_CFG;
        return {
            tab: 'timeline', logType: 'note', showEdit: false, showTask: false, showMsg: false, msgChannel: 'whatsapp', coupon: '', custom: '', busy: false,
            get message() {
                if (this.custom !== '') return this.custom;
                var c = this.coupon ? ' Use code ' + this.coupon + ' on your next order' : '';
                return 'Assalamu alaikum ' + cfg.first + ', this is ' + cfg.site + '. Thank you for shopping with us!' + c + ' — ' + cfg.siteUrl;
            },
            get msgLink() {
                if (this.msgChannel === 'whatsapp') return cfg.wa ? 'https://wa.me/' + cfg.wa + '?text=' + encodeURIComponent(this.message) : '#';
                if (this.msgChannel === 'sms') return cfg.phone ? 'sms:' + cfg.phone + '?body=' + encodeURIComponent(this.message) : '#';
                return cfg.email ? 'mailto:' + cfg.email + '?subject=' + encodeURIComponent('A little something from ' + cfg.site) + '&body=' + encodeURIComponent(this.message) : '#';
            },
            // Every "reach out" click is written to the timeline so nobody double-calls the same customer.
            track(type, subject) {
                try {
                    fetch(cfg.logUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': cfg.token, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ type: type, subject: subject, direction: 'out' }), keepalive: true, credentials: 'same-origin' });
                } catch (e) {}
            },
            sendMsg() {
                var t = this.msgChannel === 'email' ? 'email' : this.msgChannel;
                this.track(t, 'Opened ' + this.msgChannel + (this.coupon ? ' with coupon ' + this.coupon : ''));
                this.showMsg = false;
            },
        };
    }
</script>

<div x-data="contactPage()" class="space-y-5">

    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow-sm p-5">
        <div class="flex flex-wrap items-start gap-4">
            <span class="w-16 h-16 rounded-2xl flex items-center justify-center text-white text-xl font-bold flex-shrink-0 {{ Ui::avatar($contact->name) }}">{{ $contact->initials }}</span>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-xl font-bold text-gray-800">{{ $contact->name }}</h1>
                    @if($contact->is_vip)<span class="text-xs font-bold bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">★ VIP</span>@endif
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ Ui::badge($lc['color']) }}">{{ $lc['label'] }}</span>
                    @if($contact->rfm_segment)<span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ Ui::badge(Ui::rfmColor($contact->rfm_segment)) }}" title="{{ $segmentInfo['tip'] ?? '' }}">{{ $contact->rfm_segment }}</span>@endif
                    @if($contact->status !== 'active')<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-700">{{ $contact->status === 'blocked' ? 'Blocked' : 'Do not contact' }}</span>@endif
                </div>
                <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1 text-sm text-gray-500">
                    @if($contact->phone)<span>📞 {{ $contact->phone }}</span>@endif
                    @if($contact->email)<span>✉️ {{ $contact->email }}</span>@endif
                    @if($contact->city)<span>📍 {{ $contact->city }}</span>@endif
                    @if($contact->birthday)<span>🎂 {{ $contact->birthday->format('d M') }}</span>@endif
                    <span class="text-gray-400">Contact since {{ $contact->created_at->format('d M Y') }} · via {{ str_replace('_', ' ', $contact->source) }}</span>
                </div>
                @if($segmentInfo)<p class="mt-2 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2 inline-block">💡 {{ $segmentInfo['tip'] }}</p>@endif
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($contact->status === 'active' || true)
                    @if($contact->phone)
                        <a href="tel:{{ $contact->phone }}" @click="track('call', 'Started a call')" class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white px-3.5 py-2 rounded-xl text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['call'] }}"/></svg> Call
                        </a>
                    @endif
                    @if($contact->phone || $contact->email)
                    <button type="button" @click="showMsg = true" class="flex items-center gap-1.5 bg-emerald-500 hover:bg-emerald-600 text-white px-3.5 py-2 rounded-xl text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['sms'] }}"/></svg> Message / coupon
                    </button>
                    @endif
                @endif
                @if($canManage)
                <button type="button" @click="showTask = true" class="flex items-center gap-1.5 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 px-3.5 py-2 rounded-xl text-sm font-medium">+ Task</button>
                <button type="button" @click="showEdit = true" class="flex items-center gap-1.5 border border-gray-200 bg-white hover:bg-gray-50 text-gray-700 px-3.5 py-2 rounded-xl text-sm font-medium">Edit</button>
                @endif
                @if($contact->orders_count > 0 && $contact->phone)
                    <a href="{{ route('admin.fraud-checker.index', ['phone' => $contact->phone]) }}" class="text-xs text-gray-500 hover:text-orange-600 underline px-1">Fraud check</a>
                @endif
            </div>
        </div>
    </div>

    {{-- Metrics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-6 gap-3">
        @php
            $m = [
                ['Lifetime spend', Ui::money($contact->total_spent), $contact->orders_count . ' order' . ($contact->orders_count === 1 ? '' : 's')],
                ['Avg order', Ui::money($contact->avg_order_value), $contact->cancelled_count ? $contact->cancelled_count . ' cancelled/refunded' : 'no cancellations'],
                ['Last order', $contact->last_order_at ? $contact->last_order_at->diffForHumans() : '—', $contact->last_order_at?->format('d M Y')],
                ['Buys every', $contact->avg_days_between_orders ? '~' . $contact->avg_days_between_orders . ' days' : '—', $contact->predicted_next_order_on ? 'next ≈ ' . $contact->predicted_next_order_on->format('d M') : 'needs 2+ orders'],
                ['Delivered', $contact->delivered_count, $contact->orders_count ? round($contact->delivered_count / max(1, $contact->orders_count) * 100) . '% of orders' : ''],
                ['Last contacted', $contact->last_contacted_at ? $contact->last_contacted_at->diffForHumans() : 'never', $contact->owner ? 'owner: ' . $contact->owner->name : 'no owner'],
            ];
        @endphp
        @foreach($m as [$l, $v, $s])
            <div class="bg-white rounded-2xl shadow-sm p-4">
                <div class="text-[11px] font-medium text-gray-400 uppercase tracking-wide">{{ $l }}</div>
                <div class="mt-1 text-lg font-bold text-gray-800">{{ $v }}</div>
                <div class="text-xs text-gray-400 truncate">{{ $s }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid lg:grid-cols-3 gap-5">
        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-2xl shadow-sm">
                <div class="flex items-center gap-1 px-3 pt-3 border-b border-gray-100 overflow-x-auto scrollbar-hide">
                    @foreach(['timeline' => 'Timeline', 'orders' => 'Orders (' . $orders->count() . ')', 'tasks' => 'Tasks (' . $tasks->where('status', 'open')->count() . ')'] as $k => $label)
                        <button type="button" @click="tab = '{{ $k }}'" :class="tab === '{{ $k }}' ? 'border-orange-600 text-orange-600' : 'border-transparent text-gray-500 hover:text-gray-700'" class="px-4 py-2.5 text-sm font-medium border-b-2 -mb-px whitespace-nowrap">{{ $label }}</button>
                    @endforeach
                </div>

                {{-- Timeline --}}
                <div x-show="tab === 'timeline'" class="p-5">
                    @if($canManage)
                    <form method="POST" action="{{ route('admin.crm.contacts.activities.store', $contact) }}" class="mb-6 bg-gray-50 rounded-xl p-4 space-y-3">
                        @csrf
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(CrmActivity::TYPES as $k => $label)
                                <label class="cursor-pointer"><input type="radio" name="type" value="{{ $k }}" x-model="logType" class="sr-only peer"><span class="px-3 py-1 rounded-full text-xs font-medium border border-gray-200 bg-white text-gray-600 peer-checked:bg-orange-600 peer-checked:text-white peer-checked:border-orange-600 inline-block">{{ $label }}</span></label>
                            @endforeach
                        </div>
                        <textarea name="body" rows="2" placeholder="What happened? Notes, what the customer said, next steps…" class="{{ $sel }}">{{ old('body') }}</textarea>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 items-end">
                            <label x-show="logType !== 'note'" x-cloak class="block text-xs text-gray-500">Outcome
                                <select name="outcome" class="{{ $sel }} mt-0.5"><option value="">—</option>@foreach(CrmActivity::OUTCOMES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
                            <label x-show="logType !== 'note'" x-cloak class="block text-xs text-gray-500">Direction
                                <select name="direction" class="{{ $sel }} mt-0.5"><option value="out">Outgoing (we contacted them)</option><option value="in">Incoming (they contacted us)</option></select></label>
                            <label class="block text-xs text-gray-500 col-span-2 md:col-span-1">Remind me to follow up
                                <input type="datetime-local" name="follow_up_at" min="{{ now()->format('Y-m-d\TH:i') }}" class="{{ $sel }} mt-0.5"></label>
                            <button class="bg-orange-600 hover:bg-orange-700 text-white rounded-lg px-4 py-2 text-sm font-medium col-span-2 md:col-span-1">Save entry</button>
                        </div>
                    </form>
                    @endif

                    <ol class="relative border-l-2 border-gray-100 ml-3 space-y-5">
                        @forelse($timeline as $item)
                            @php $model = $item['model']; @endphp
                            <li class="ml-6 relative">
                                @switch($item['kind'])
                                    @case('order')
                                        <span class="absolute -left-[2.15rem] top-0.5 w-6 h-6 rounded-full bg-orange-100 text-orange-600 flex items-center justify-center ring-4 ring-white"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['order'] }}"/></svg></span>
                                        <div class="flex flex-wrap items-center gap-2 text-sm">
                                            <a href="{{ route('admin.orders.show', $model->id) }}" class="font-semibold text-indigo-600 hover:underline">Order {{ $model->order_number }}</a>
                                            <span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $model->status_badge }}">{{ ucfirst($model->status) }}</span>
                                            <span class="text-gray-700 font-medium">{{ Ui::money($model->total) }}</span>
                                            <span class="text-gray-400 text-xs">{{ $model->items_count }} item(s) · {{ strtoupper($model->payment_method) }}</span>
                                        </div>
                                        @break
                                    @case('task')
                                        <span class="absolute -left-[2.15rem] top-0.5 w-6 h-6 rounded-full bg-green-100 text-green-600 flex items-center justify-center ring-4 ring-white"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['task'] }}"/></svg></span>
                                        <div class="text-sm text-gray-600">Task completed: <span class="font-medium text-gray-800">{{ $model->title }}</span></div>
                                        @break
                                    @case('lead')
                                        <span class="absolute -left-[2.15rem] top-0.5 w-6 h-6 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center ring-4 ring-white"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons['lead'] }}"/></svg></span>
                                        <div class="text-sm text-gray-600">Lead created (<a href="{{ route('admin.crm.leads.show', $model) }}" class="text-indigo-600 hover:underline">{{ \App\Models\Crm\CrmLead::STAGES[$model->stage]['label'] }}</a>) — {{ \Illuminate\Support\Str::limit($model->interest, 90) }}</div>
                                        @break
                                    @default
                                        @php $system = $model->meta['system'] ?? false; @endphp
                                        <span class="absolute -left-[2.15rem] top-0.5 w-6 h-6 rounded-full flex items-center justify-center ring-4 ring-white {{ $system ? 'bg-gray-100 text-gray-400' : 'bg-indigo-100 text-indigo-600' }}"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icons[$model->type] ?? $icons['note'] }}"/></svg></span>
                                        <div class="flex items-start gap-2">
                                            <div class="min-w-0 flex-1 {{ $model->is_pinned ? 'bg-amber-50 border border-amber-200 rounded-lg p-2.5 -m-1' : '' }}">
                                                <div class="flex flex-wrap items-center gap-2 text-sm">
                                                    @if(! $system)<span class="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700">{{ CrmActivity::TYPES[$model->type] ?? $model->type }}{{ $model->direction === 'in' ? ' ← in' : ($model->direction === 'out' ? ' → out' : '') }}</span>@endif
                                                    <span class="{{ $system ? 'text-gray-500' : 'font-medium text-gray-800' }}">{{ $model->subject }}</span>
                                                    @if($model->outcome)<span class="text-[11px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ CrmActivity::OUTCOMES[$model->outcome] ?? $model->outcome }}</span>@endif
                                                    @if($model->is_pinned)<span class="text-amber-500 text-xs">📌</span>@endif
                                                </div>
                                                @if($model->body)<p class="mt-1 text-sm text-gray-600 whitespace-pre-line">{{ $model->body }}</p>@endif
                                            </div>
                                            @if($canManage && ! $system)
                                            <div class="flex items-center gap-1 text-xs text-gray-300">
                                                <form method="POST" action="{{ route('admin.crm.activities.pin', $model) }}">@csrf<button title="Pin" class="hover:text-amber-500 px-1">📌</button></form>
                                                <form method="POST" action="{{ route('admin.crm.activities.destroy', $model) }}" onsubmit="return confirm('Delete this entry?')">@csrf @method('DELETE')<button title="Delete" class="hover:text-red-500 px-1">✕</button></form>
                                            </div>
                                            @endif
                                        </div>
                                @endswitch
                                <div class="text-[11px] text-gray-400 mt-1">{{ $item['at']?->format('d M Y, h:i A') }}@if(($item['kind'] === 'activity') && $model->user) · {{ $model->user->name }}@endif</div>
                            </li>
                        @empty
                            <li class="ml-6 text-sm text-gray-400">Nothing yet — log the first call or note above.</li>
                        @endforelse
                    </ol>
                </div>

                {{-- Orders --}}
                <div x-show="tab === 'orders'" x-cloak class="p-5 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="text-xs text-gray-400 uppercase text-left"><th class="pb-2">Order</th><th class="pb-2">Date</th><th class="pb-2">Status</th><th class="pb-2 text-right">Items</th><th class="pb-2 text-right">Total</th></tr></thead>
                        <tbody class="divide-y divide-gray-50">
                            @forelse($orders as $o)
                                <tr>
                                    <td class="py-2.5"><a href="{{ route('admin.orders.show', $o->id) }}" class="text-indigo-600 font-medium hover:underline">{{ $o->order_number }}</a></td>
                                    <td class="py-2.5 text-gray-500">{{ $o->created_at->format('d M Y') }}</td>
                                    <td class="py-2.5"><span class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $o->status_badge }}">{{ ucfirst($o->status) }}</span></td>
                                    <td class="py-2.5 text-right text-gray-500">{{ $o->items_count }}</td>
                                    <td class="py-2.5 text-right font-medium text-gray-800">{{ Ui::money($o->total) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="py-8 text-center text-gray-400">No orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Tasks --}}
                <div x-show="tab === 'tasks'" x-cloak class="p-5 space-y-2">
                    @forelse($tasks as $t)
                        <div class="flex items-center gap-3 border border-gray-100 rounded-xl p-3 {{ $t->status === 'done' ? 'opacity-60' : '' }}">
                            @if($canManage && $t->status === 'open')
                                <form method="POST" action="{{ route('admin.crm.tasks.complete', $t) }}">@csrf<button title="Mark done" class="w-5 h-5 rounded-full border-2 border-gray-300 hover:border-green-500 hover:bg-green-50"></button></form>
                            @else<span class="w-5 h-5 rounded-full bg-green-500 text-white text-[10px] flex items-center justify-center">✓</span>@endif
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-800 {{ $t->status === 'done' ? 'line-through' : '' }}">{{ $t->title }}</div>
                                <div class="text-xs {{ $t->isOverdue() ? 'text-red-500 font-medium' : 'text-gray-400' }}">{{ $t->due_at ? $t->due_at->format('d M, h:i A') : 'No due date' }} · {{ $t->assignee?->name ?? 'Unassigned' }} · {{ \App\Models\Crm\CrmTask::PRIORITIES[$t->priority] }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-400 text-center py-6">No tasks for this customer.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Side column --}}
        <div class="space-y-5">
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-3 text-sm">Health & score</h3>
                @if($contact->orders_count)
                    <div class="mb-4">
                        <div class="flex items-center justify-between text-xs text-gray-500 mb-1"><span>Churn risk</span><span class="font-semibold text-gray-700">{{ $contact->churn_risk }}%</span></div>
                        <div class="h-2 bg-gray-100 rounded-full overflow-hidden"><div class="h-full rounded-full {{ Ui::dot(Ui::churnColor($contact->churn_risk)) }}" style="width: {{ $contact->churn_risk }}%"></div></div>
                    </div>
                    <div class="grid grid-cols-3 gap-2 text-center">
                        @foreach([['Recency', $contact->rfm_r], ['Frequency', $contact->rfm_f], ['Monetary', $contact->rfm_m]] as [$l, $v])
                            <div class="bg-gray-50 rounded-xl py-2.5"><div class="text-xl font-bold text-gray-800">{{ $v }}<span class="text-xs text-gray-400">/5</span></div><div class="text-[10px] uppercase text-gray-400 tracking-wide">{{ $l }}</div></div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-400">Scores appear after the first order.</p>
                @endif
                @if($canManage)<form method="POST" action="{{ route('admin.crm.contacts.refresh', $contact) }}" class="mt-3">@csrf<button class="text-xs text-indigo-600 hover:underline">↻ Recalculate now</button></form>@endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-3 text-sm">Tags</h3>
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @forelse($contact->tags as $t)<span class="text-xs font-medium px-2 py-0.5 rounded-full {{ Ui::badge($t->color) }}">{{ $t->name }}</span>@empty<span class="text-xs text-gray-400">No tags</span>@endforelse
                </div>
                @if($canManage)
                <form method="POST" action="{{ route('admin.crm.contacts.tags', $contact) }}" class="flex gap-2">
                    @csrf
                    <input name="tags" value="{{ $contact->tags->pluck('name')->implode(', ') }}" list="allTags" placeholder="comma, separated" class="{{ $sel }}">
                    <datalist id="allTags">@foreach($allTags as $t)<option value="{{ $t->name }}">@endforeach</datalist>
                    <button class="bg-gray-800 text-white px-3 rounded-lg text-sm hover:bg-gray-700">Save</button>
                </form>
                @endif
            </div>

            @if($topProducts->isNotEmpty())
            <div class="bg-white rounded-2xl shadow-sm p-5">
                <h3 class="font-semibold text-gray-800 mb-3 text-sm">Favourite products</h3>
                <ul class="space-y-1.5 text-sm">
                    @foreach($topProducts as $p)<li class="flex justify-between gap-3"><span class="text-gray-700 truncate">{{ $p->name }}</span><span class="text-gray-400 flex-shrink-0">×{{ $p->qty }}</span></li>@endforeach
                </ul>
            </div>
            @endif

            @if($contact->leads->isNotEmpty() || $contact->user)
            <div class="bg-white rounded-2xl shadow-sm p-5 text-sm space-y-2">
                <h3 class="font-semibold text-gray-800 text-sm">Links</h3>
                @if($contact->user)<div class="text-gray-600">Registered account: <a class="text-indigo-600 hover:underline" href="{{ route('admin.users.show', $contact->user_id) }}">{{ $contact->user->name }}</a></div>@endif
                @foreach($contact->leads as $l)<div><a href="{{ route('admin.crm.leads.show', $l) }}" class="text-indigo-600 hover:underline">Lead #{{ $l->id }}</a> <span class="text-gray-400">· {{ \App\Models\Crm\CrmLead::STAGES[$l->stage]['label'] }}</span></div>@endforeach
            </div>
            @endif

            @if($canManage)
            <form method="POST" action="{{ route('admin.crm.contacts.destroy', $contact) }}" onsubmit="return confirm('Delete this contact? This cannot be undone.')" class="text-right">
                @csrf @method('DELETE')
                <button class="text-xs text-red-500 hover:underline">Delete contact</button>
            </form>
            @endif
        </div>
    </div>

    {{-- Message / coupon modal --}}
    <div x-show="showMsg" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="showMsg = false">
        <div @click.outside="showMsg = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-3">
            <h3 class="text-lg font-semibold text-gray-800">Reach out to {{ $contact->name }}</h3>
            <div class="flex gap-1.5">
                @foreach(['whatsapp' => 'WhatsApp', 'sms' => 'SMS', 'email' => 'E-mail'] as $k => $l)
                    <button type="button" @click="msgChannel = '{{ $k }}'" :class="msgChannel === '{{ $k }}' ? 'bg-emerald-500 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-full text-xs font-medium">{{ $l }}</button>
                @endforeach
            </div>
            @if($coupons->isNotEmpty())
            <label class="block text-sm text-gray-600">Attach a coupon
                <select x-model="coupon" class="{{ $sel }} mt-1"><option value="">None</option>
                    @foreach($coupons as $c)<option value="{{ $c->code }}">{{ $c->code }} — {{ $c->type === 'percentage' ? rtrim(rtrim(number_format($c->value, 2), '0'), '.') . '% off' : '৳' . number_format($c->value) . ' off' }}</option>@endforeach
                </select></label>
            @endif
            <label class="block text-sm text-gray-600">Message
                <textarea rows="4" :value="message" @input="custom = $event.target.value" class="{{ $sel }} mt-1"></textarea></label>
            <p class="text-xs text-gray-400">Opens your WhatsApp / SMS / mail app with this text ready to send, and logs it on the timeline.</p>
            <div class="flex justify-end gap-2">
                <button type="button" @click="showMsg = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <a :href="msgLink" target="_blank" rel="noopener" @click="sendMsg()" class="px-5 py-2 text-sm bg-emerald-500 hover:bg-emerald-600 text-white rounded-lg font-medium">Open &amp; log</a>
            </div>
        </div>
    </div>

    {{-- Add task --}}
    @if($canManage)
    <div x-show="showTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="showTask = false">
        <form method="POST" action="{{ route('admin.crm.tasks.store') }}" @click.outside="showTask = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-3">
            @csrf <input type="hidden" name="contact_id" value="{{ $contact->id }}">
            <h3 class="text-lg font-semibold text-gray-800">New task for {{ $contact->name }}</h3>
            <label class="block text-sm text-gray-600">Title *<input name="title" required maxlength="250" class="{{ $sel }} mt-1"></label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm text-gray-600">Type<select name="type" class="{{ $sel }} mt-1">@foreach(\App\Models\Crm\CrmTask::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
                <label class="block text-sm text-gray-600">Priority<select name="priority" class="{{ $sel }} mt-1">@foreach(\App\Models\Crm\CrmTask::PRIORITIES as $k => $l)<option value="{{ $k }}" @selected($k === 'normal')>{{ $l }}</option>@endforeach</select></label>
                <label class="block text-sm text-gray-600">Due<input type="datetime-local" name="due_at" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm text-gray-600">Assign to<select name="assigned_to" class="{{ $sel }} mt-1">@foreach($staff as $u)<option value="{{ $u->id }}" @selected($u->id === auth()->id())>{{ $u->name }}</option>@endforeach</select></label>
            </div>
            <label class="block text-sm text-gray-600">Details<textarea name="description" rows="2" class="{{ $sel }} mt-1"></textarea></label>
            <div class="flex justify-end gap-2"><button type="button" @click="showTask = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button><button class="px-5 py-2 text-sm bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700">Add task</button></div>
        </form>
    </div>

    {{-- Edit --}}
    <div x-show="showEdit" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="showEdit = false">
        <form method="POST" action="{{ route('admin.crm.contacts.update', $contact) }}" @click.outside="showEdit = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-3 max-h-[90vh] overflow-y-auto">
            @csrf @method('PUT')
            <h3 class="text-lg font-semibold text-gray-800">Edit contact</h3>
            <div class="grid grid-cols-2 gap-3">
                <label class="col-span-2 block text-sm text-gray-600">Name *<input name="name" value="{{ old('name', $contact->name) }}" required class="{{ $sel }} mt-1"></label>
                <label class="block text-sm text-gray-600">Phone<input name="phone" value="{{ old('phone', $contact->phone) }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm text-gray-600">E-mail<input type="email" name="email" value="{{ old('email', $contact->email) }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm text-gray-600">City<input name="city" value="{{ old('city', $contact->city) }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm text-gray-600">Birthday<input type="date" name="birthday" value="{{ old('birthday', optional($contact->birthday)->toDateString()) }}" max="{{ now()->subDay()->toDateString() }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm text-gray-600">Owner<select name="owner_id" class="{{ $sel }} mt-1"><option value="">—</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected($contact->owner_id === $u->id)>{{ $u->name }}</option>@endforeach</select></label>
                <label class="block text-sm text-gray-600">Status<select name="status" class="{{ $sel }} mt-1">@foreach(['active' => 'Active', 'blocked' => 'Blocked', 'do_not_contact' => 'Do not contact'] as $k => $l)<option value="{{ $k }}" @selected($contact->status === $k)>{{ $l }}</option>@endforeach</select></label>
                <label class="flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="is_vip" value="1" @checked($contact->is_vip) class="rounded border-gray-300 text-orange-600"> VIP customer</label>
            </div>
            <div class="flex justify-end gap-2"><button type="button" @click="showEdit = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button><button class="px-5 py-2 text-sm bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700">Save</button></div>
        </form>
    </div>
    @endif
</div>
@endsection
