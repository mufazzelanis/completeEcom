@extends('layouts.admin')
@section('title', 'CRM · Settings')

@php
    use App\Support\CrmUi as Ui;
    $sel = 'w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400';
@endphp

@section('content')
@include('admin.crm._tabs')

<div class="grid lg:grid-cols-2 gap-5">
    <div class="space-y-5">
        <form method="POST" action="{{ route('admin.crm.settings.save') }}" class="bg-white rounded-2xl shadow-sm p-5 space-y-4">
            @csrf
            <div>
                <h3 class="font-semibold text-gray-800">Customer lifecycle rules</h3>
                <p class="text-xs text-gray-400 mt-0.5">Decides when a customer counts as Active, At risk or Lost. Saving recalculates everyone.</p>
            </div>
            <label class="block text-sm text-gray-600">“Active” if they ordered within (days)
                <input type="number" name="active_days" min="7" max="365" required value="{{ old('active_days', $settings['active_days']) }}" class="{{ $sel }} mt-1"></label>
            <label class="block text-sm text-gray-600">“Lost” if no order for (days)
                <input type="number" name="lost_days" min="30" max="1000" required value="{{ old('lost_days', $settings['lost_days']) }}" class="{{ $sel }} mt-1"></label>
            <p class="text-xs text-gray-400 -mt-2">Between the two numbers a customer is “At risk”. Choose values that fit how often your customers normally reorder.</p>
            <label class="block text-sm text-gray-600">Auto-flag as VIP at lifetime spend of (৳, 0 = never)
                <input type="number" name="vip_spend" min="0" step="1" required value="{{ old('vip_spend', (int) $settings['vip_spend']) }}" class="{{ $sel }} mt-1"></label>
            <label class="flex items-center gap-2 text-sm text-gray-700"><input type="checkbox" name="auto_tasks" value="1" @checked($settings['auto_tasks']) class="rounded border-gray-300 text-orange-600">
                Create win-back tasks automatically for repeat customers who go quiet</label>
            <button class="bg-orange-600 hover:bg-orange-700 text-white px-5 py-2 rounded-xl text-sm font-medium">Save &amp; recalculate</button>
        </form>

        <div class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            <h3 class="font-semibold text-gray-800">Data sync</h3>
            <p class="text-sm text-gray-500">The CRM keeps itself up to date: every new order, account and newsletter signup is added instantly, and a nightly job re-scores everyone and creates follow-ups. Use this to import history or force a full refresh.</p>
            <p class="text-xs text-gray-400">Last recalculation: {{ $lastRun ? \Illuminate\Support\Carbon::parse($lastRun)->diffForHumans() : 'never' }}</p>
            <form method="POST" action="{{ route('admin.crm.settings.sync') }}">@csrf<button class="border border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-xl text-sm text-gray-700" onclick="this.textContent='Syncing…'">↻ Sync now</button></form>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-5" x-data="{ edit: null }">
        <h3 class="font-semibold text-gray-800 mb-3">Tags</h3>
        <div class="space-y-1.5 mb-4">
            @forelse($tags as $t)
                <div class="flex items-center gap-2 border border-gray-100 rounded-xl px-3 py-2">
                    <template x-if="edit !== {{ $t->id }}">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ Ui::badge($t->color) }}">{{ $t->name }}</span>
                            <span class="text-xs text-gray-400">{{ $t->contacts_count }} contact(s)</span>
                            <button type="button" @click="edit = {{ $t->id }}" class="ml-auto text-xs text-gray-400 hover:text-indigo-600">Edit</button>
                        </div>
                    </template>
                    <template x-if="edit === {{ $t->id }}">
                        <form method="POST" action="{{ route('admin.crm.tags.update', $t) }}" class="flex items-center gap-2 flex-1">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $t->name }}" maxlength="60" required class="{{ $sel }}">
                            <select name="color" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">@foreach($colors as $c)<option value="{{ $c }}" @selected($t->color === $c)>{{ $c }}</option>@endforeach</select>
                            <button class="text-sm bg-gray-800 text-white rounded-lg px-3 py-2">Save</button>
                            <button type="button" @click="edit = null" class="text-gray-400 px-1">✕</button>
                        </form>
                    </template>
                    <form method="POST" action="{{ route('admin.crm.tags.destroy', $t) }}" onsubmit="return confirm('Delete tag {{ e($t->name) }}? It is removed from all contacts.')" x-show="edit !== {{ $t->id }}">@csrf @method('DELETE')<button class="text-gray-300 hover:text-red-500 px-1">✕</button></form>
                </div>
            @empty<p class="text-sm text-gray-400">No tags yet — add one below or type new ones on a customer's profile.</p>@endforelse
        </div>
        <form method="POST" action="{{ route('admin.crm.tags.store') }}" class="flex items-center gap-2 border-t border-gray-100 pt-4">
            @csrf
            <input name="name" required maxlength="60" placeholder="New tag name" class="{{ $sel }}">
            <select name="color" class="border border-gray-200 rounded-lg px-2 py-2 text-sm">@foreach($colors as $c)<option value="{{ $c }}">{{ $c }}</option>@endforeach</select>
            <button class="bg-orange-600 text-white rounded-lg px-4 py-2 text-sm font-medium hover:bg-orange-700">Add</button>
        </form>
    </div>
</div>
@endsection
