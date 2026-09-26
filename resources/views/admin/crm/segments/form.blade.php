@extends('layouts.admin')
@section('title', 'CRM · ' . ($segment->exists ? 'Edit segment' : 'New segment'))

@php
    use App\Support\CrmUi as Ui;
    $sel = 'w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400';
    $initial = old('rules', $segment->rules ?? []);
@endphp

@section('content')
@include('admin.crm._tabs')

<script>
    window.CRM_SEG = {!! Js::from([
        'fields' => $fields, 'ops' => $ops, 'enums' => $enums,
        'rules' => array_values($initial),
        'previewUrl' => route('admin.crm.segments.preview'),
        'token' => csrf_token(),
    ]) !!};
    function segmentBuilder() {
        var D = window.CRM_SEG;
        return {
            fields: D.fields, ops: D.ops, enums: D.enums,
            rules: D.rules.length ? D.rules : [{ field: 'orders_count', op: 'gte', value: 1 }],
            match: {!! Js::from(old('match', $segment->match)) !!},
            count: null, sample: [], loading: false, timer: null, err: '',
            typeOf(f) { return (this.fields[f] || {}).type || 'text'; },
            opsFor(f) { return this.ops[this.typeOf(f)] || {}; },
            add() { this.rules.push({ field: 'total_spent', op: 'gte', value: '' }); },
            remove(i) { this.rules.splice(i, 1); this.refresh(); },
            changed(r) {
                var t = this.typeOf(r.field);
                if (!(r.op in this.opsFor(r.field))) r.op = 'eq';
                if (t === 'bool') r.value = 1;
                else if (t === 'enum' || t === 'tag') r.value = Object.keys(this.enums[r.field] || {})[0] || '';
                else if (t === 'number') r.value = '';
                this.refresh();
            },
            refresh() {
                var self = this; clearTimeout(this.timer);
                this.timer = setTimeout(function () {
                    self.loading = true; self.err = '';
                    fetch(D.previewUrl, { method: 'POST', credentials: 'same-origin', redirect: 'manual',
                        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': D.token, 'X-Requested-With': 'XMLHttpRequest' },
                        body: JSON.stringify({ rules: self.rules, match: self.match }) })
                    .then(function (r) { if (r.type === 'opaqueredirect' || r.status === 401 || r.status === 419) throw new Error('Session expired — reload the page.'); if (!r.ok) throw new Error('Preview failed (' + r.status + ').'); return r.json(); })
                    .then(function (d) { self.count = d.count; self.sample = d.sample; })
                    .catch(function (e) { self.err = e.message; })
                    .then(function () { self.loading = false; });
                }, 350);
            },
        };
    }
</script>

<div class="mb-4"><a href="{{ route('admin.crm.segments.index') }}" class="text-sm text-gray-500 hover:text-orange-600">← All segments</a></div>

<form method="POST" action="{{ $segment->exists ? route('admin.crm.segments.update', $segment) : route('admin.crm.segments.store') }}" x-data="segmentBuilder()" x-init="refresh()" class="grid lg:grid-cols-3 gap-5">
    @csrf @if($segment->exists) @method('PUT') @endif

    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h2 class="font-semibold text-gray-800">{{ $segment->exists ? 'Edit segment' : 'New segment' }}</h2>
            <div class="grid sm:grid-cols-3 gap-3">
                <label class="sm:col-span-2 block text-sm text-gray-600">Name *<input name="name" required maxlength="120" value="{{ old('name', $segment->name) }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm text-gray-600">Colour
                    <select name="color" class="{{ $sel }} mt-1">@foreach($colors as $c)<option value="{{ $c }}" @selected(old('color', $segment->color) === $c)>{{ ucfirst($c) }}</option>@endforeach</select></label>
                <label class="sm:col-span-3 block text-sm text-gray-600">Description<input name="description" maxlength="500" value="{{ old('description', $segment->description) }}" class="{{ $sel }} mt-1"></label>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <h3 class="font-semibold text-gray-800">Rules</h3>
                <span class="text-sm text-gray-500">Match</span>
                <select name="match" x-model="match" @change="refresh()" class="border border-gray-200 rounded-lg px-2.5 py-1.5 text-sm"><option value="all">ALL of these</option><option value="any">ANY of these</option></select>
            </div>
            <div class="space-y-2">
                <template x-for="(r, i) in rules" :key="i">
                    <div class="flex flex-wrap items-center gap-2 bg-gray-50 rounded-xl p-2.5">
                        <select :name="'rules[' + i + '][field]'" x-model="r.field" @change="changed(r)" class="border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white min-w-[190px]">
                            <template x-for="(f, key) in fields" :key="key"><option :value="key" :selected="key === r.field" x-text="f.label"></option></template>
                        </select>
                        <select :name="'rules[' + i + '][op]'" x-model="r.op" @change="refresh()" x-show="typeOf(r.field) !== 'bool'" class="border border-gray-200 rounded-lg pl-2 pr-7 py-2 text-sm bg-white min-w-[5.5rem]">
                            <template x-for="(label, key) in opsFor(r.field)" :key="key"><option :value="key" :selected="key === r.op" x-text="label"></option></template>
                        </select>

                        <input x-show="typeOf(r.field) === 'number'" :disabled="typeOf(r.field) !== 'number'" type="number" step="any" :name="'rules[' + i + '][value]'" x-model="r.value" @input="refresh()" placeholder="value" class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm w-32 bg-white">
                        <input x-show="typeOf(r.field) === 'text'" :disabled="typeOf(r.field) !== 'text'" type="text" :name="'rules[' + i + '][value]'" x-model="r.value" @input="refresh()" placeholder="value" class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm w-40 bg-white">
                        <select x-show="typeOf(r.field) === 'enum' || typeOf(r.field) === 'tag'" :disabled="typeOf(r.field) !== 'enum' && typeOf(r.field) !== 'tag'" :name="'rules[' + i + '][value]'" x-model="r.value" @change="refresh()" class="border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white">
                            <template x-for="(label, key) in (enums[r.field] || {})" :key="key"><option :value="key" :selected="String(key) === String(r.value)" x-text="label"></option></template>
                        </select>
                        <select x-show="typeOf(r.field) === 'bool'" :disabled="typeOf(r.field) !== 'bool'" :name="'rules[' + i + '][value]'" x-model="r.value" @change="refresh()" class="border border-gray-200 rounded-lg px-2 py-2 text-sm bg-white"><option value="1">Yes</option><option value="0">No</option></select>

                        <button type="button" @click="remove(i)" x-show="rules.length > 1" class="ml-auto text-gray-400 hover:text-red-500 px-2" title="Remove rule">✕</button>
                    </div>
                </template>
            </div>
            <button type="button" @click="add(); refresh()" class="mt-3 text-sm text-indigo-600 hover:underline">+ Add rule</button>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-5 lg:sticky lg:top-4">
            <h3 class="font-semibold text-gray-800 text-sm mb-2">Live preview</h3>
            <div class="flex items-end gap-2">
                <span class="text-4xl font-bold text-gray-800" x-text="count === null ? '…' : count.toLocaleString('en-US')"></span>
                <span class="text-sm text-gray-400 pb-1.5">people match</span>
                <span x-show="loading" x-cloak class="text-xs text-gray-300 pb-1.5 ml-auto">updating…</span>
            </div>
            <p x-show="err" x-cloak class="text-xs text-red-500 mt-1" x-text="err"></p>
            <div class="mt-3 space-y-1.5">
                <template x-for="s in sample" :key="s.id">
                    <a :href="'{{ url('/admin/crm/contacts') }}/' + s.id" target="_blank" class="flex items-center justify-between text-sm hover:bg-gray-50 rounded px-1.5 py-1">
                        <span class="truncate text-gray-700" x-text="s.name"></span><span class="text-xs text-gray-400" x-text="'৳' + Math.round(s.spent).toLocaleString('en-US')"></span>
                    </a>
                </template>
            </div>
            <button class="mt-4 w-full bg-orange-600 hover:bg-orange-700 text-white rounded-xl py-2.5 text-sm font-medium">{{ $segment->exists ? 'Save changes' : 'Create segment' }}</button>
            @if($segment->exists)
            <button type="submit" form="delete-segment" class="mt-2 w-full text-xs text-red-500 hover:underline" onclick="return confirm('Delete this segment? Contacts are not affected.')">Delete segment</button>
            @endif
        </div>
    </div>
</form>
@if($segment->exists)<form id="delete-segment" method="POST" action="{{ route('admin.crm.segments.destroy', $segment) }}">@csrf @method('DELETE')</form>@endif
@endsection
