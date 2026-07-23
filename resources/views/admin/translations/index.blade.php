@extends('layouts.admin')
@section('title', 'Translations')

@section('content')
<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Translations</h1>
    <a href="{{ route('admin.languages.index') }}" class="text-sm text-orange-600 hover:text-orange-800 font-medium">Manage Languages &rarr;</a>
</div>

@if(session('success'))<div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">{{ session('error') }}</div>@endif

@if($languages->isEmpty())
    <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-xl text-sm">
        Add at least one language first — see <a href="{{ route('admin.languages.index') }}" class="underline font-medium">Languages</a>.
    </div>
@else

<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-4 mb-5">
    <form method="GET" class="flex flex-wrap gap-3 items-center">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search key…"
            class="border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500 flex-1 min-w-[180px]">
        <select name="group" class="border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            <option value="">All groups</option>
            @foreach($groups as $g)
                <option value="{{ $g }}" @selected(request('group')===$g)>{{ ucfirst($g) }}</option>
            @endforeach
        </select>
        <button type="submit" class="bg-orange-600 text-white px-4 py-2 rounded-xl text-sm hover:bg-orange-700">Filter</button>
        @if(request('search') || request('group'))<a href="{{ route('admin.translations.index') }}" class="px-4 py-2 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-600 dark:text-gray-300">Clear</a>@endif
    </form>
</div>

{{-- Add new key --}}
<div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5 mb-5">
    <h3 class="font-semibold text-gray-800 dark:text-gray-100 mb-3 text-sm">Add New Translation Key</h3>
    <form action="{{ route('admin.translations.store') }}" method="POST" class="space-y-3">
        @csrf
        <div class="flex flex-wrap gap-3">
            <input type="text" name="key" required placeholder="e.g. checkout.place_order"
                class="border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2 text-sm flex-1 min-w-[200px] focus:outline-none focus:ring-2 focus:ring-orange-500">
            <input type="text" name="group" placeholder="Group (e.g. checkout)"
                class="border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2 text-sm w-52 focus:outline-none focus:ring-2 focus:ring-orange-500">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            @foreach($languages as $lang)
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">{{ $lang->flag_emoji }} {{ $lang->name }} ({{ $lang->code }})</label>
                <input type="text" name="value_{{ $lang->code }}" dir="{{ $lang->direction }}"
                    class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
            </div>
            @endforeach
        </div>
        <button type="submit" class="bg-orange-600 text-white px-5 py-2 rounded-xl text-sm font-semibold hover:bg-orange-700 transition">Add Key</button>
    </form>
</div>

{{-- Existing keys --}}
<div class="space-y-3">
    @forelse($keys as $key)
        @php $entries = $rows->get($key, collect()); @endphp
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <p class="text-sm font-mono font-semibold text-gray-700 dark:text-gray-200">{{ $key }}</p>
                    <p class="text-xs text-gray-400">{{ ucfirst($entries->first()->group ?? 'common') }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button type="submit" form="translation-{{ $loop->index }}" class="text-xs bg-orange-600 text-white px-3 py-1.5 rounded-lg font-medium">Save</button>
                    <form action="{{ route('admin.translations.destroy') }}" method="POST" onsubmit="return confirm('Delete this translation key for all languages?')">
                        @csrf @method('DELETE')
                        <input type="hidden" name="key" value="{{ $key }}">
                        <button type="submit" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                    </form>
                </div>
            </div>
            <form id="translation-{{ $loop->index }}" action="{{ route('admin.translations.update') }}" method="POST">
                @csrf @method('PATCH')
                <input type="hidden" name="key" value="{{ $key }}">
                <input type="hidden" name="group" value="{{ $entries->first()->group ?? 'common' }}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @foreach($languages as $lang)
                        @php $existing = $entries->firstWhere('locale', $lang->code); @endphp
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">{{ $lang->flag_emoji }} {{ $lang->name }} ({{ $lang->code }})</label>
                            <input type="text" name="value_{{ $lang->code }}" value="{{ $existing->value ?? '' }}" dir="{{ $lang->direction }}"
                                class="w-full border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        </div>
                    @endforeach
                </div>
            </form>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm p-12 text-center text-gray-400 text-sm">
            No translation keys yet — they also get created automatically the first time a page uses one.
        </div>
    @endforelse
</div>
@endif
@endsection
