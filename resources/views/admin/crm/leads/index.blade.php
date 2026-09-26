@extends('layouts.admin')
@section('title', 'CRM · Leads')

@php
    use App\Support\CrmUi as Ui;
    use App\Models\Crm\CrmLead;
    $canManage = auth()->user()->hasPermission('crm.manage');
    $sel = 'w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400';
    $stages = CrmLead::STAGES;
    $cards = [];
    foreach ($stages as $key => $meta) {
        $cards[$key] = ($leads[$key] ?? collect())->map(fn ($l) => [
            'id' => $l->id, 'name' => $l->name, 'company' => $l->company, 'phone' => $l->phone, 'value' => (float) $l->value,
            'source' => CrmLead::SOURCES[$l->source] ?? $l->source, 'owner' => $l->owner?->name, 'contact' => $l->contact_id,
            'close' => optional($l->expected_close_on)->format('d M'), 'overdue' => $l->expected_close_on && $l->expected_close_on->isPast() && ! in_array($l->stage, ['won', 'lost']),
            'age' => $l->created_at->diffForHumans(null, true), 'interest' => \Illuminate\Support\Str::limit((string) $l->interest, 80),
        ])->values();
    }
@endphp

@section('content')
@include('admin.crm._tabs')

<script>
    window.CRM_BOARD = {!! Js::from(['stages' => collect($stages)->map(fn ($s, $k) => ['key' => $k, 'label' => $s['label'], 'color' => $s['color'], 'p' => $s['probability']])->values(), 'cards' => $cards, 'moveUrl' => url('/admin/crm/leads/__ID__/move'), 'showUrl' => url('/admin/crm/leads/__ID__'), 'token' => csrf_token(), 'canManage' => $canManage]) !!};
    function leadBoard() {
        var D = window.CRM_BOARD;
        return {
            stages: D.stages, cards: D.cards, dragging: null, over: null, error: '', showAdd: false, lostFor: null, lostReason: '',
            money(n) { return '৳' + Math.round(n).toLocaleString('en-US'); },
            total(k) { return this.cards[k].reduce(function (s, c) { return s + c.value; }, 0); },
            url(t, id) { return D[t].replace('__ID__', id); },
            start(e, card, from) { if (!D.canManage) { e.preventDefault(); return; } this.dragging = { card: card, from: from }; e.dataTransfer.effectAllowed = 'move'; try { e.dataTransfer.setData('text/plain', String(card.id)); } catch (x) {} },
            drop(to) {
                var d = this.dragging; this.over = null; this.dragging = null;
                if (!d || d.from === to) return;
                if (to === 'lost') { this.lostFor = { card: d.card, from: d.from }; this.lostReason = ''; return; }
                this.commit(d.card, d.from, to, null);
            },
            confirmLost() { var l = this.lostFor; this.lostFor = null; if (l) this.commit(l.card, l.from, 'lost', this.lostReason); },
            commit(card, from, to, reason) {
                var self = this;
                // Optimistic: move it now, put it back if the server says no.
                this.cards[from] = this.cards[from].filter(function (c) { return c.id !== card.id; });
                this.cards[to] = [card].concat(this.cards[to]);
                this.error = '';
                fetch(this.url('moveUrl', card.id), { method: 'POST', credentials: 'same-origin', redirect: 'manual',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': D.token, 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ stage: to, lost_reason: reason }) })
                .then(function (r) { if (r.type === 'opaqueredirect' || r.status === 401 || r.status === 419) throw new Error('Your session expired — reload the page.'); if (!r.ok) throw new Error('Could not move the lead (' + r.status + ').'); return r.json(); })
                .catch(function (e) {
                    self.cards[to] = self.cards[to].filter(function (c) { return c.id !== card.id; });
                    self.cards[from] = [card].concat(self.cards[from]);
                    self.error = e.message;
                });
            },
        };
    }
</script>

<div x-data="leadBoard()">
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <form method="GET" class="flex flex-wrap items-center gap-2 flex-1">
            <input name="q" value="{{ request('q') }}" placeholder="Search leads…" class="border border-gray-200 rounded-xl px-3.5 py-2 text-sm w-52 focus:outline-none focus:ring-2 focus:ring-orange-400">
            <select name="owner" class="border border-gray-200 rounded-xl px-3 py-2 text-sm"><option value="">All owners</option>@foreach($staff as $u)<option value="{{ $u->id }}" @selected((string) request('owner') === (string) $u->id)>{{ $u->name }}</option>@endforeach</select>
            <select name="source" class="border border-gray-200 rounded-xl px-3 py-2 text-sm"><option value="">All sources</option>@foreach(CrmLead::SOURCES as $k => $l)<option value="{{ $k }}" @selected(request('source') === $k)>{{ $l }}</option>@endforeach</select>
            <button class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700">Filter</button>
            @if(request()->hasAny(['q', 'owner', 'source']))<a href="{{ route('admin.crm.leads.index') }}" class="text-sm text-gray-500 px-2">Reset</a>@endif
        </form>
        @if($canManage)<button type="button" @click="showAdd = true" class="bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-700">+ New lead</button>@endif
    </div>

    <div x-show="error" x-cloak class="mb-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-2 text-sm" x-text="error"></div>

    <div class="flex gap-3 overflow-x-auto pb-4 -mx-1 px-1 snap-x">
        <template x-for="s in stages" :key="s.key">
            <div class="w-72 flex-shrink-0 snap-start flex flex-col rounded-2xl bg-gray-100/70 border border-gray-200/70"
                 :class="over === s.key ? 'ring-2 ring-orange-400 bg-orange-50/60' : ''"
                 @dragover.prevent="over = s.key" @dragleave="over === s.key && (over = null)" @drop.prevent="drop(s.key)">
                <div class="px-3.5 pt-3 pb-2">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-sm text-gray-700 flex items-center gap-2"><span class="w-2 h-2 rounded-full" :class="{'bg-blue-500': s.color==='blue','bg-indigo-500': s.color==='indigo','bg-purple-500': s.color==='purple','bg-amber-500': s.color==='amber','bg-orange-500': s.color==='orange','bg-green-500': s.color==='green','bg-red-500': s.color==='red'}"></span><span x-text="s.label"></span></span>
                        <span class="text-xs bg-white text-gray-500 rounded-full px-2 py-0.5 font-medium" x-text="cards[s.key].length"></span>
                    </div>
                    <div class="text-xs text-gray-400 mt-0.5"><span x-text="money(total(s.key))"></span><template x-if="s.p > 0 && s.p < 100"><span> · <span x-text="s.p"></span>% likely</span></template></div>
                </div>
                <div class="px-2.5 pb-2.5 space-y-2 overflow-y-auto max-h-[62vh] min-h-[60px]">
                    <template x-for="c in cards[s.key]" :key="c.id">
                        <div :draggable="{{ $canManage ? 'true' : 'false' }}" @dragstart="start($event, c, s.key)" @dragend="dragging = null; over = null"
                             class="bg-white rounded-xl border border-gray-100 shadow-sm p-3 cursor-grab active:cursor-grabbing hover:shadow-md transition">
                            <a :href="url('showUrl', c.id)" class="block">
                                <div class="font-medium text-sm text-gray-800 truncate" x-text="c.name"></div>
                                <div class="text-xs text-gray-400 truncate" x-show="c.company" x-text="c.company"></div>
                                <p class="text-xs text-gray-500 mt-1 line-clamp-2" x-show="c.interest" x-text="c.interest"></p>
                            </a>
                            <div class="flex items-center justify-between mt-2 text-xs">
                                <span class="font-semibold text-gray-700" x-text="c.value > 0 ? money(c.value) : ''"></span>
                                <span class="text-gray-400" x-text="c.source"></span>
                            </div>
                            <div class="flex items-center justify-between mt-1 text-[11px] text-gray-400">
                                <span x-text="c.age + ' old'"></span>
                                <span x-show="c.close" :class="c.overdue ? 'text-red-500 font-medium' : ''" x-text="'close ' + c.close"></span>
                            </div>
                        </div>
                    </template>
                    <p x-show="cards[s.key].length === 0" class="text-center text-xs text-gray-400 py-4">Drop leads here</p>
                </div>
            </div>
        </template>
    </div>
    <p class="text-xs text-gray-400 mt-1">Won/Lost columns show the last 30 days. Drag a card between columns to move it.</p>

    {{-- Lost reason --}}
    <div x-show="lostFor" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 space-y-3">
            <h3 class="font-semibold text-gray-800">Why was this lead lost?</h3>
            <select x-model="lostReason" class="{{ $sel }}">
                <option value="">Select a reason…</option>
                @foreach(['Price too high', 'Chose a competitor', 'No response', 'Not interested', 'Bad timing', 'Wrong fit', 'Other'] as $r)<option>{{ $r }}</option>@endforeach
            </select>
            <div class="flex justify-end gap-2"><button type="button" @click="lostFor = null" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button><button type="button" @click="confirmLost()" class="px-5 py-2 text-sm bg-red-600 text-white rounded-lg font-medium">Mark lost</button></div>
        </div>
    </div>

    @if($canManage)
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="showAdd = false">
        <form method="POST" action="{{ route('admin.crm.leads.store') }}" @click.outside="showAdd = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-3 max-h-[90vh] overflow-y-auto">
            @csrf
            <h3 class="text-lg font-semibold text-gray-800">New lead</h3>
            @include('admin.crm.leads._fields', ['lead' => null])
            <div class="flex justify-end gap-2 pt-2"><button type="button" @click="showAdd = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button><button class="px-5 py-2 text-sm bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700">Add lead</button></div>
        </form>
    </div>
    @endif
</div>
@endsection
