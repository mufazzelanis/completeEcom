@extends('layouts.admin')
@section('title', 'CRM · Lead')

@php
    use App\Support\CrmUi as Ui;
    use App\Models\Crm\CrmLead;
    $canManage = auth()->user()->hasPermission('crm.manage');
    $sel = 'w-full border border-gray-200 rounded-lg px-2.5 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-400';
    $st = CrmLead::STAGES[$lead->stage];
@endphp

@section('content')
@include('admin.crm._tabs')

<div class="mb-4"><a href="{{ route('admin.crm.leads.index') }}" class="text-sm text-gray-500 hover:text-orange-600">← Pipeline</a></div>

<div class="grid lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-800">{{ $lead->name }}</h1>
                    <div class="text-sm text-gray-500 mt-1 flex flex-wrap gap-x-4">
                        @if($lead->company)<span>🏢 {{ $lead->company }}</span>@endif
                        @if($lead->phone)<span>📞 {{ $lead->phone }}</span>@endif
                        @if($lead->email)<span>✉️ {{ $lead->email }}</span>@endif
                        <span>via {{ CrmLead::SOURCES[$lead->source] ?? $lead->source }} · {{ $lead->created_at->diffForHumans() }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ Ui::badge($st['color']) }}">{{ $st['label'] }}</span>
                    @if($lead->value > 0)<span class="text-sm font-bold text-gray-800">{{ Ui::money($lead->value) }}</span>@endif
                </div>
            </div>
            @if($lead->interest)<p class="mt-4 text-sm text-gray-700 whitespace-pre-line bg-gray-50 rounded-xl p-4">{{ $lead->interest }}</p>@endif
            @if($lead->lost_reason)<p class="mt-3 text-sm text-red-600">Lost reason: {{ $lead->lost_reason }}</p>@endif

            @if($canManage)
            <div class="mt-4 flex flex-wrap items-center gap-2">
                @if($lead->contact_id)
                    <a href="{{ route('admin.crm.contacts.show', $lead->contact_id) }}" class="text-sm border border-gray-200 hover:bg-gray-50 px-3.5 py-2 rounded-xl text-gray-700">Open customer profile →</a>
                @endif
                @if($lead->stage !== 'won')
                    <form method="POST" action="{{ route('admin.crm.leads.convert', $lead) }}">@csrf<button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-medium">✓ Won — save as customer</button></form>
                @endif
                <form method="POST" action="{{ route('admin.crm.leads.destroy', $lead) }}" onsubmit="return confirm('Delete this lead?')" class="ml-auto">@csrf @method('DELETE')<button class="text-xs text-red-500 hover:underline">Delete lead</button></form>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 mb-3">Activity</h3>
            @if($canManage)
            <form method="POST" action="{{ route('admin.crm.leads.activities.store', $lead) }}" class="bg-gray-50 rounded-xl p-3 mb-4 flex flex-wrap gap-2 items-start">
                @csrf
                <select name="type" class="border border-gray-200 rounded-lg px-2.5 py-2 text-sm">@foreach(['note' => 'Note', 'call' => 'Call', 'whatsapp' => 'WhatsApp', 'email' => 'Email', 'meeting' => 'Meeting', 'sms' => 'SMS'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select>
                <textarea name="body" required rows="1" placeholder="What happened?" class="flex-1 min-w-[200px] border border-gray-200 rounded-lg px-2.5 py-2 text-sm"></textarea>
                <button class="bg-orange-600 text-white px-4 py-2 rounded-lg text-sm font-medium">Log</button>
            </form>
            @endif
            <div class="space-y-3">
                @forelse($lead->activities as $a)
                    <div class="flex gap-3 text-sm">
                        <span class="text-[10px] h-fit font-semibold uppercase px-2 py-0.5 rounded-full {{ ($a->meta['system'] ?? false) ? 'bg-gray-100 text-gray-500' : 'bg-indigo-100 text-indigo-700' }}">{{ ($a->meta['system'] ?? false) ? 'system' : $a->type }}</span>
                        <div class="flex-1"><div class="text-gray-700">{{ $a->subject }}</div>@if($a->body)<div class="text-gray-600 whitespace-pre-line">{{ $a->body }}</div>@endif<div class="text-[11px] text-gray-400 mt-0.5">{{ $a->occurred_at->format('d M, h:i A') }}@if($a->user) · {{ $a->user->name }}@endif</div></div>
                    </div>
                @empty<p class="text-sm text-gray-400">No activity yet.</p>@endforelse
            </div>
        </div>
    </div>

    <div class="space-y-5">
        @if($canManage)
        <form method="POST" action="{{ route('admin.crm.leads.update', $lead) }}" class="bg-white rounded-2xl shadow-sm p-5 space-y-3">
            @csrf @method('PUT')
            <h3 class="font-semibold text-gray-800 text-sm">Edit details</h3>
            @include('admin.crm.leads._fields', ['lead' => $lead])
            <button class="w-full bg-gray-800 text-white rounded-lg py-2 text-sm font-medium hover:bg-gray-700">Save changes</button>
        </form>
        @endif

        <div class="bg-white rounded-2xl shadow-sm p-5">
            <h3 class="font-semibold text-gray-800 text-sm mb-3">Tasks</h3>
            <div class="space-y-2 mb-3">
                @forelse($lead->tasks as $t)
                    <div class="flex items-center gap-2 text-sm {{ $t->status === 'done' ? 'opacity-50 line-through' : '' }}">
                        @if($canManage && $t->status === 'open')<form method="POST" action="{{ route('admin.crm.tasks.complete', $t) }}">@csrf<button class="w-4 h-4 rounded-full border-2 border-gray-300 hover:border-green-500"></button></form>@else<span class="w-4 h-4 rounded-full bg-green-500"></span>@endif
                        <span class="flex-1 truncate">{{ $t->title }}</span><span class="text-xs {{ $t->isOverdue() ? 'text-red-500' : 'text-gray-400' }}">{{ $t->due_at?->format('d M') }}</span>
                    </div>
                @empty<p class="text-sm text-gray-400">None.</p>@endforelse
            </div>
            @if($canManage)
            <form method="POST" action="{{ route('admin.crm.tasks.store') }}" class="space-y-2 border-t border-gray-100 pt-3">
                @csrf <input type="hidden" name="lead_id" value="{{ $lead->id }}"><input type="hidden" name="type" value="follow_up"><input type="hidden" name="priority" value="normal"><input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                <input name="title" required placeholder="Follow up…" class="{{ $sel }}"><input type="datetime-local" name="due_at" class="{{ $sel }}">
                <button class="w-full border border-gray-200 hover:bg-gray-50 rounded-lg py-1.5 text-sm text-gray-700">+ Add task</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
