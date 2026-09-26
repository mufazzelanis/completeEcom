@extends('layouts.admin')
@section('title', 'CRM · Customers')

@php
    use App\Support\CrmUi as Ui;
    $canManage = auth()->user()->hasPermission('crm.manage');
    $filterKeys = ['q', 'lifecycle_stage', 'rfm_segment', 'tag', 'owner', 'city', 'source', 'status', 'vip', 'account', 'min_spent', 'max_spent', 'inactive_days', 'segment', 'sort'];
    $filterCount = collect($filterKeys)->reject(fn ($k) => in_array($k, ['sort', 'q']) || ! request()->filled($k))->count();
    $activeFilters = collect($filterKeys)->reject(fn ($k) => in_array($k, ['sort']) || ! request()->filled($k))->count();
@endphp

@section('content')
@include('admin.crm._tabs')

<div x-data="{
        selected: [], scope: 'selected', showFilters: {{ $filterCount > 0 ? 'true' : 'false' }}, showAdd: {{ $errors->any() && old('_form') === 'add' ? 'true' : 'false' }},
        pageIds: {{ Js::from($contacts->pluck('id')) }},
        get allOnPage() { return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id)); },
        toggleAll() { this.selected = this.allOnPage ? this.selected.filter(id => !this.pageIds.includes(id)) : [...new Set([...this.selected, ...this.pageIds])]; },
        action: '', tagName: '',
     }">

    {{-- Toolbar --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <form method="GET" class="flex-1 min-w-[220px] flex items-center gap-2">
            @foreach(['sort'] as $keep)@if(request()->filled($keep))<input type="hidden" name="{{ $keep }}" value="{{ request($keep) }}">@endif @endforeach
            <div class="relative flex-1">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search name, phone or e-mail…" class="w-full border border-gray-200 rounded-xl pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400">
            </div>
            <button class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm hover:bg-gray-700">Search</button>
        </form>
        <button type="button" @click="showFilters = !showFilters" class="flex items-center gap-1.5 border border-gray-200 bg-white px-3.5 py-2 rounded-xl text-sm text-gray-600 hover:bg-gray-50">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h18M6 12h12M10 20h4"/></svg>
            Filters @if($activeFilters)<span class="bg-orange-600 text-white text-[10px] rounded-full px-1.5 py-0.5 font-bold">{{ $activeFilters }}</span>@endif
        </button>
        <a href="{{ route('admin.crm.contacts.export', request()->query()) }}" class="flex items-center gap-1.5 border border-gray-200 bg-white px-3.5 py-2 rounded-xl text-sm text-gray-600 hover:bg-gray-50" title="Download everything matching the current filters">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
            CSV
        </a>
        @if($canManage)
        <button type="button" @click="showAdd = true" class="flex items-center gap-1.5 bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-700">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add contact
        </button>
        @endif
    </div>

    {{-- Filters --}}
    <form method="GET" x-show="showFilters" x-cloak class="bg-white rounded-2xl shadow-sm p-4 mb-4 grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
        <input type="hidden" name="q" value="{{ request('q') }}">
        @php $sel = 'w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400'; @endphp
        <label class="block"><span class="text-xs text-gray-500">Lifecycle</span>
            <select name="lifecycle_stage" class="{{ $sel }}"><option value="">Any</option>
                @foreach(\App\Models\Crm\CrmContact::LIFECYCLE as $k => $m)<option value="{{ $k }}" @selected(request('lifecycle_stage') === $k)>{{ $m['label'] }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-xs text-gray-500">RFM segment</span>
            <select name="rfm_segment" class="{{ $sel }}"><option value="">Any</option>
                @foreach($rfmSegments as $r)<option value="{{ $r }}" @selected(request('rfm_segment') === $r)>{{ $r }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-xs text-gray-500">Tag</span>
            <select name="tag" class="{{ $sel }}"><option value="">Any</option>
                @foreach($tags as $t)<option value="{{ $t->id }}" @selected((string) request('tag') === (string) $t->id)>{{ $t->name }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-xs text-gray-500">Saved segment</span>
            <select name="segment" class="{{ $sel }}"><option value="">Any</option>
                @foreach($segments as $s)<option value="{{ $s->id }}" @selected((string) request('segment') === (string) $s->id)>{{ $s->name }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-xs text-gray-500">Owner</span>
            <select name="owner" class="{{ $sel }}"><option value="">Anyone</option><option value="none" @selected(request('owner') === 'none')>Unassigned</option>
                @foreach($staff as $u)<option value="{{ $u->id }}" @selected((string) request('owner') === (string) $u->id)>{{ $u->name }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-xs text-gray-500">City</span>
            <select name="city" class="{{ $sel }}"><option value="">Any</option>
                @foreach($cities as $c)<option value="{{ $c }}" @selected(request('city') === $c)>{{ $c }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-xs text-gray-500">Source</span>
            <select name="source" class="{{ $sel }}"><option value="">Any</option>
                @foreach(['order' => 'Order', 'registration' => 'Registration', 'newsletter' => 'Newsletter', 'lead' => 'Lead', 'contact_form' => 'Contact form', 'manual' => 'Manual'] as $k => $v)<option value="{{ $k }}" @selected(request('source') === $k)>{{ $v }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-xs text-gray-500">Status</span>
            <select name="status" class="{{ $sel }}"><option value="">Any</option>
                @foreach(['active' => 'Active', 'blocked' => 'Blocked', 'do_not_contact' => 'Do not contact'] as $k => $v)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $v }}</option>@endforeach
            </select></label>
        <label class="block"><span class="text-xs text-gray-500">Spent at least (৳)</span><input type="number" min="0" name="min_spent" value="{{ request('min_spent') }}" class="{{ $sel }}"></label>
        <label class="block"><span class="text-xs text-gray-500">Spent at most (৳)</span><input type="number" min="0" name="max_spent" value="{{ request('max_spent') }}" class="{{ $sel }}"></label>
        <label class="block"><span class="text-xs text-gray-500">No order for (days)</span><input type="number" min="0" name="inactive_days" value="{{ request('inactive_days') }}" class="{{ $sel }}"></label>
        <label class="block"><span class="text-xs text-gray-500">Account</span>
            <select name="account" class="{{ $sel }}"><option value="">Any</option><option value="yes" @selected(request('account') === 'yes')>Registered</option><option value="no" @selected(request('account') === 'no')>Guest only</option></select></label>
        <label class="flex items-center gap-2 mt-4"><input type="checkbox" name="vip" value="1" @checked(request('vip')) class="rounded border-gray-300 text-orange-600"><span class="text-gray-600">VIP only</span></label>
        <label class="block"><span class="text-xs text-gray-500">Sort by</span>
            <select name="sort" class="{{ $sel }}">
                @foreach(['recent' => 'Newest', 'spent' => 'Highest spend', 'orders' => 'Most orders', 'last_order' => 'Latest order', 'churn' => 'Highest churn risk', 'name' => 'Name A-Z'] as $k => $v)<option value="{{ $k }}" @selected(request('sort', 'recent') === $k)>{{ $v }}</option>@endforeach
            </select></label>
        <div class="col-span-2 md:col-span-4 flex items-center gap-2 justify-end">
            <a href="{{ route('admin.crm.contacts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">Reset</a>
            <button class="bg-orange-600 text-white px-5 py-2 rounded-lg text-sm font-medium hover:bg-orange-700">Apply filters</button>
        </div>
    </form>

    {{-- Bulk bar --}}
    @if($canManage)
    <div x-show="selected.length > 0" x-cloak class="sticky top-2 z-20 mb-3 bg-gray-900 text-white rounded-xl px-4 py-3 shadow-lg">
        <form method="POST" action="{{ route('admin.crm.contacts.bulk') }}" class="flex flex-wrap items-center gap-2 text-sm"
              onsubmit="return this.action.value !== '' && confirm('Apply this action?')">
            @csrf
            @foreach(request()->query() as $k => $v)@if(is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif @endforeach
            <template x-if="scope === 'selected'"><span><template x-for="id in selected" :key="id"><input type="hidden" name="ids[]" :value="id"></template></span></template>
            <input type="hidden" name="scope" :value="scope">
            <span class="font-semibold"><span x-text="scope === 'selected' ? selected.length : '{{ $contacts->total() }}'"></span> selected</span>
            @if($contacts->total() > $contacts->count())
                <button type="button" @click="scope = scope === 'selected' ? 'filtered' : 'selected'" class="underline text-orange-300 text-xs" x-text="scope === 'selected' ? 'Select all {{ $contacts->total() }} matching' : 'Only the ticked rows'"></button>
            @endif
            <select name="action" x-model="action" class="bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5 text-sm ml-auto">
                <option value="">Choose action…</option>
                <option value="tag">Add tag</option><option value="untag">Remove tag</option>
                <option value="owner">Assign owner</option><option value="vip">Mark VIP</option><option value="unvip">Remove VIP</option>
                <option value="status">Set status</option><option value="delete_empty">Delete (no-order contacts only)</option>
            </select>
            <input x-show="action === 'tag' || action === 'untag'" x-cloak name="tag_name" list="tagList" placeholder="Tag name" class="bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5 text-sm w-36">
            <datalist id="tagList">@foreach($tags as $t)<option value="{{ $t->name }}">@endforeach</datalist>
            <select x-show="action === 'owner'" x-cloak name="owner_id" class="bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5 text-sm"><option value="">Unassign</option>@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
            <select x-show="action === 'status'" x-cloak name="new_status" class="bg-gray-800 border border-gray-700 rounded-lg px-2 py-1.5 text-sm"><option value="active">Active</option><option value="blocked">Blocked</option><option value="do_not_contact">Do not contact</option></select>
            <button class="bg-orange-600 hover:bg-orange-700 px-4 py-1.5 rounded-lg font-medium">Apply</button>
            <button type="button" @click="selected = []; scope = 'selected'" class="text-gray-400 hover:text-white px-2">✕</button>
        </form>
    </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr class="text-xs text-gray-500 uppercase tracking-wider">
                    @if($canManage)<th class="pl-4 pr-1 py-3 w-8"><input type="checkbox" :checked="allOnPage" @change="toggleAll()" class="rounded border-gray-300 text-orange-600"></th>@endif
                    <th class="px-4 py-3 text-left">Customer</th>
                    <th class="px-4 py-3 text-left">Segment</th>
                    <th class="px-4 py-3 text-right">Orders</th>
                    <th class="px-4 py-3 text-right">Spent</th>
                    <th class="px-4 py-3 text-left">Last order</th>
                    <th class="px-4 py-3 text-left">Churn</th>
                    <th class="px-4 py-3 text-left">Tags</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse($contacts as $c)
                    @php $lc = Ui::lifecycle($c->lifecycle_stage); @endphp
                    <tr class="hover:bg-gray-50 transition {{ $c->status !== 'active' ? 'opacity-60' : '' }}">
                        @if($canManage)<td class="pl-4 pr-1 py-3"><input type="checkbox" value="{{ $c->id }}" x-model.number="selected" class="rounded border-gray-300 text-orange-600"></td>@endif
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.crm.contacts.show', $c) }}" class="flex items-center gap-3 group">
                                <span class="w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0 {{ Ui::avatar($c->name) }}">{{ $c->initials }}</span>
                                <span class="min-w-0">
                                    <span class="flex items-center gap-1.5 font-medium text-gray-800 group-hover:text-orange-600 truncate">
                                        {{ $c->name }}
                                        @if($c->is_vip)<span title="VIP" class="text-amber-500">★</span>@endif
                                        @if($c->status !== 'active')<span class="text-[10px] font-semibold bg-gray-200 text-gray-600 px-1.5 rounded">{{ $c->status === 'blocked' ? 'BLOCKED' : 'DNC' }}</span>@endif
                                    </span>
                                    <span class="block text-xs text-gray-400 truncate">{{ $c->phone ?: '—' }}@if($c->email) · {{ $c->email }}@endif</span>
                                </span>
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-block text-[11px] font-semibold px-2 py-0.5 rounded-full {{ Ui::badge($lc['color']) }}">{{ $lc['label'] }}</span>
                            @if($c->rfm_segment)<div class="text-[11px] text-gray-400 mt-0.5">{{ $c->rfm_segment }} <span class="text-gray-300">({{ $c->rfm_r }}{{ $c->rfm_f }}{{ $c->rfm_m }})</span></div>@endif
                        </td>
                        <td class="px-4 py-3 text-right text-gray-700">{{ $c->orders_count }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-800">{{ Ui::money($c->total_spent) }}</td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $c->last_order_at ? $c->last_order_at->diffForHumans() : '—' }}</td>
                        <td class="px-4 py-3">
                            @if($c->orders_count)
                                <div class="flex items-center gap-2"><div class="w-14 h-1.5 bg-gray-100 rounded-full overflow-hidden"><div class="h-full {{ Ui::dot(Ui::churnColor($c->churn_risk)) }}" style="width: {{ $c->churn_risk }}%"></div></div><span class="text-xs text-gray-500">{{ $c->churn_risk }}%</span></div>
                            @else<span class="text-xs text-gray-300">—</span>@endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-1 max-w-[220px]">
                                @foreach($c->tags->take(3) as $t)<span class="text-[10px] font-medium px-1.5 py-0.5 rounded {{ Ui::badge($t->color) }}">{{ $t->name }}</span>@endforeach
                                @if($c->tags->count() > 3)<span class="text-[10px] text-gray-400">+{{ $c->tags->count() - 3 }}</span>@endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-16 text-center text-gray-400">
                        @if($totals['all'] === 0)
                            No contacts yet. Open <a class="text-indigo-600 underline" href="{{ route('admin.crm.settings') }}">CRM Settings</a> and press “Sync now” to import your existing customers.
                        @else No contacts match these filters. @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 text-sm text-gray-500">
        <span>{{ number_format($contacts->total()) }} contact(s) · {{ number_format($totals['customers']) }} have ordered</span>
        {{ $contacts->links() }}
    </div>

    {{-- Add contact --}}
    @if($canManage)
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="showAdd = false">
        <form method="POST" action="{{ route('admin.crm.contacts.store') }}" @click.outside="showAdd = false" class="bg-white rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-3">
            @csrf <input type="hidden" name="_form" value="add">
            <h3 class="text-lg font-semibold text-gray-800">Add contact</h3>
            <p class="text-xs text-gray-400">A phone number is what identifies a person here — if it already exists, that customer's profile opens instead of a duplicate.</p>
            <div class="grid grid-cols-2 gap-3">
                <label class="col-span-2 block text-sm"><span class="text-gray-600">Name *</span><input name="name" value="{{ old('name') }}" required class="{{ $sel }} mt-1"></label>
                <label class="block text-sm"><span class="text-gray-600">Phone</span><input name="phone" value="{{ old('phone') }}" placeholder="01XXXXXXXXX" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm"><span class="text-gray-600">E-mail</span><input type="email" name="email" value="{{ old('email') }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm"><span class="text-gray-600">City</span><input name="city" value="{{ old('city') }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm"><span class="text-gray-600">Birthday</span><input type="date" name="birthday" value="{{ old('birthday') }}" max="{{ now()->subDay()->toDateString() }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm"><span class="text-gray-600">Owner</span><select name="owner_id" class="{{ $sel }} mt-1"><option value="">—</option>@foreach($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></label>
                <label class="flex items-center gap-2 text-sm mt-6"><input type="checkbox" name="is_vip" value="1" class="rounded border-gray-300 text-orange-600"> VIP</label>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="showAdd = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button>
                <button class="px-5 py-2 text-sm bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700">Save contact</button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
