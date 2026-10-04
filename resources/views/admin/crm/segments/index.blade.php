@extends('layouts.admin')
@section('title', 'CRM · Segments')

@php
    use App\Support\CrmUi as Ui;
    $canManage = auth()->user()->hasPermission('crm.manage');
@endphp

@section('content')
@include('admin.crm._tabs')

<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
    <p class="text-sm text-gray-500">Segments are saved audiences that update themselves — e.g. “spent over ৳5,000 and silent for 60 days”.</p>
    @if($canManage)<a href="{{ route('admin.crm.segments.create') }}" class="bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-700">+ New segment</a>@endif
</div>

<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
    @forelse($segments as $s)
        <div class="bg-white rounded-2xl shadow-sm p-5 hover:shadow-md transition">
            <div class="flex items-start justify-between gap-2">
                <a href="{{ route('admin.crm.segments.show', $s) }}" class="font-semibold text-gray-800 hover:text-orange-600 flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full {{ Ui::dot($s->color) }}"></span>{{ $s->name }}</a>
                <span class="text-2xl font-bold text-gray-800">{{ number_format($s->contact_count) }}</span>
            </div>
            @if($s->description)<p class="text-xs text-gray-500 mt-1">{{ $s->description }}</p>@endif
            <div class="mt-3 flex flex-wrap gap-1">
                @foreach($s->rules as $r)
                    <span class="text-[11px] bg-gray-100 text-gray-600 rounded px-1.5 py-0.5">{{ \App\Services\Crm\CrmSegmentEngine::describe($r) }}</span>
                @endforeach
            </div>
            <div class="mt-3 flex items-center gap-3 text-xs">
                <a href="{{ route('admin.crm.segments.show', $s) }}" class="text-indigo-600 hover:underline">View people</a>
                <a href="{{ route('admin.crm.segments.export', $s) }}" data-turbo="false" class="text-gray-500 hover:underline">Export CSV</a>
                @if($canManage)<a href="{{ route('admin.crm.segments.edit', $s) }}" class="text-gray-500 hover:underline">Edit</a>@endif
                <span class="ml-auto text-gray-300">{{ $s->match === 'any' ? 'ANY rule' : 'ALL rules' }}</span>
            </div>
        </div>
    @empty
        <div class="sm:col-span-2 xl:col-span-3 bg-white rounded-2xl shadow-sm py-14 text-center text-gray-400 text-sm">No segments yet — add a ready-made one below or build your own.</div>
    @endforelse
</div>

@if($canManage && $presets->isNotEmpty())
<div class="flex items-center justify-between mb-3">
    <h3 class="font-semibold text-gray-800">Ready-made segments</h3>
    <form method="POST" action="{{ route('admin.crm.segments.presets') }}">@csrf<button class="text-sm text-indigo-600 hover:underline">Add all</button></form>
</div>
<div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-3">
    @foreach($presets as $p)
        <form method="POST" action="{{ route('admin.crm.segments.presets') }}" class="bg-white border border-dashed border-gray-200 rounded-2xl p-4 flex items-start justify-between gap-3">
            @csrf <input type="hidden" name="only" value="{{ $p['name'] }}">
            <div><div class="font-medium text-gray-700 text-sm flex items-center gap-2"><span class="w-2 h-2 rounded-full {{ Ui::dot($p['color']) }}"></span>{{ $p['name'] }}</div><p class="text-xs text-gray-400 mt-0.5">{{ $p['description'] }}</p></div>
            <button class="text-xs bg-gray-800 text-white rounded-lg px-3 py-1.5 hover:bg-gray-700 flex-shrink-0">Add</button>
        </form>
    @endforeach
</div>
@endif
@endsection
