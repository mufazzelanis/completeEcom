@extends('layouts.admin')
@section('title', 'CRM · Tasks')

@php
    use App\Models\Crm\CrmTask;
    $canManage = auth()->user()->hasPermission('crm.manage');
    $sel = 'w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400';
    $prio = ['urgent' => 'bg-red-100 text-red-700', 'high' => 'bg-orange-100 text-orange-700', 'normal' => 'bg-gray-100 text-gray-600', 'low' => 'bg-blue-100 text-blue-700'];
@endphp

@section('content')
@include('admin.crm._tabs')

<div x-data="{ showAdd: {{ $prefillContact || ($errors->any() && old('_form') === 'task') ? 'true' : 'false' }}, edit: null }">
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <div class="flex items-center gap-1.5 flex-wrap">
            @foreach(['mine' => 'My open', 'today' => 'Due today', 'overdue' => 'Overdue', 'all' => 'All open', 'done' => 'Done (30d)'] as $k => $l)
                <a href="{{ route('admin.crm.tasks.index', ['view' => $k]) }}" class="px-3.5 py-1.5 rounded-full text-sm font-medium border {{ $view === $k ? 'bg-gray-800 text-white border-gray-800' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                    {{ $l }}@if(isset($counts[$k]) && $counts[$k] > 0)<span class="ml-1 text-[11px] {{ $k === 'overdue' && $view !== $k ? 'text-red-500 font-bold' : 'opacity-70' }}">{{ $counts[$k] }}</span>@endif
                </a>
            @endforeach
        </div>
        @if($canManage)<button type="button" @click="showAdd = true" class="ml-auto bg-orange-600 text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-orange-700">+ New task</button>@endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm divide-y divide-gray-50">
        @forelse($tasks as $t)
            <div class="flex items-center gap-3 px-4 py-3.5 {{ $t->status === 'done' ? 'opacity-60' : '' }}">
                @if($canManage)
                    @if($t->status === 'open')
                        <form method="POST" action="{{ route('admin.crm.tasks.complete', $t) }}">@csrf<button title="Mark done" class="w-6 h-6 rounded-full border-2 border-gray-300 hover:border-green-500 hover:bg-green-50 transition"></button></form>
                    @else
                        <form method="POST" action="{{ route('admin.crm.tasks.reopen', $t) }}">@csrf<button title="Reopen" class="w-6 h-6 rounded-full bg-green-500 text-white text-xs flex items-center justify-center">✓</button></form>
                    @endif
                @else
                    <span class="w-6 h-6 rounded-full border-2 {{ $t->status === 'done' ? 'bg-green-500 border-green-500' : 'border-gray-300' }}"></span>
                @endif
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-sm font-medium text-gray-800 {{ $t->status === 'done' ? 'line-through' : '' }}">{{ $t->title }}</span>
                        <span class="text-[10px] font-semibold uppercase px-1.5 py-0.5 rounded {{ $prio[$t->priority] }}">{{ $t->priority }}</span>
                        <span class="text-[10px] text-gray-400 uppercase">{{ CrmTask::TYPES[$t->type] ?? $t->type }}</span>
                        @if($t->is_auto)<span class="text-[10px] bg-purple-100 text-purple-700 px-1.5 py-0.5 rounded font-semibold">AUTO</span>@endif
                    </div>
                    <div class="text-xs mt-0.5 flex flex-wrap gap-x-3 text-gray-400">
                        <span class="{{ $t->isOverdue() ? 'text-red-500 font-semibold' : '' }}">{{ $t->due_at ? ($t->isOverdue() ? 'Overdue · ' : '') . $t->due_at->format('d M, h:i A') : 'No due date' }}</span>
                        @if($t->contact)<a href="{{ route('admin.crm.contacts.show', $t->contact_id) }}" class="text-indigo-600 hover:underline">{{ $t->contact->name }}</a>@endif
                        @if($t->lead)<a href="{{ route('admin.crm.leads.show', $t->lead_id) }}" class="text-indigo-600 hover:underline">Lead: {{ $t->lead->name }}</a>@endif
                        <span>{{ $t->assignee?->name ?? 'Unassigned' }}</span>
                    </div>
                    @if($t->description)<p class="text-xs text-gray-500 mt-1">{{ \Illuminate\Support\Str::limit($t->description, 160) }}</p>@endif
                </div>
                @if($t->contact?->phone && $t->status === 'open')<a href="tel:{{ $t->contact->phone }}" title="Call" class="text-green-600 hover:bg-green-50 rounded-lg p-2">📞</a>@endif
                @if($canManage)
                <form method="POST" action="{{ route('admin.crm.tasks.destroy', $t) }}" onsubmit="return confirm('Delete this task?')">@csrf @method('DELETE')<button class="text-gray-300 hover:text-red-500 px-1.5" title="Delete">✕</button></form>
                @endif
            </div>
        @empty
            <div class="py-16 text-center text-gray-400 text-sm">{{ $view === 'mine' ? 'You have no open tasks. 🎉' : 'Nothing here.' }}</div>
        @endforelse
    </div>
    <div class="mt-4">{{ $tasks->links() }}</div>

    @if($canManage)
    <div x-show="showAdd" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50" @keydown.escape.window="showAdd = false">
        <form method="POST" action="{{ route('admin.crm.tasks.store') }}" @click.outside="showAdd = false" class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 space-y-3">
            @csrf <input type="hidden" name="_form" value="task">
            @if($prefillContact)<input type="hidden" name="contact_id" value="{{ $prefillContact->id }}">@endif
            <h3 class="text-lg font-semibold text-gray-800">{{ $prefillContact ? 'New task for ' . $prefillContact->name : 'New task' }}</h3>
            <label class="block text-sm text-gray-600">Title *<input name="title" required maxlength="250" value="{{ old('title') }}" class="{{ $sel }} mt-1"></label>
            <div class="grid grid-cols-2 gap-3">
                <label class="block text-sm text-gray-600">Type<select name="type" class="{{ $sel }} mt-1">@foreach(CrmTask::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></label>
                <label class="block text-sm text-gray-600">Priority<select name="priority" class="{{ $sel }} mt-1">@foreach(CrmTask::PRIORITIES as $k => $l)<option value="{{ $k }}" @selected($k === 'normal')>{{ $l }}</option>@endforeach</select></label>
                <label class="block text-sm text-gray-600">Due<input type="datetime-local" name="due_at" value="{{ old('due_at') }}" class="{{ $sel }} mt-1"></label>
                <label class="block text-sm text-gray-600">Assign to<select name="assigned_to" class="{{ $sel }} mt-1">@foreach($staff as $u)<option value="{{ $u->id }}" @selected($u->id === auth()->id())>{{ $u->name }}</option>@endforeach</select></label>
            </div>
            <label class="block text-sm text-gray-600">Details<textarea name="description" rows="2" class="{{ $sel }} mt-1">{{ old('description') }}</textarea></label>
            <div class="flex justify-end gap-2"><button type="button" @click="showAdd = false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">Cancel</button><button class="px-5 py-2 text-sm bg-orange-600 text-white rounded-lg font-medium hover:bg-orange-700">Add task</button></div>
        </form>
    </div>
    @endif
</div>
@endsection
