@extends('layouts.admin')
@section('title', 'Notification Templates')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Notification Templates</h1>
        <div class="flex gap-2">
            <a href="{{ route('admin.notifications.templates.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                + New Template
            </a>
            <a href="{{ route('admin.notifications.index') }}" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-lg text-sm hover:bg-gray-200">&larr; Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Event</th>
                    <th class="px-4 py-3 text-left">Channel</th>
                    <th class="px-4 py-3 text-left">Recipient</th>
                    <th class="px-4 py-3 text-left">Subject / Title</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($templates as $template)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-0.5 rounded">{{ $template->event_type }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @php $cc = ['email'=>'blue','sms'=>'green','push'=>'purple','whatsapp'=>'emerald'][$template->channel] ?? 'gray'; @endphp
                        <span class="inline-block bg-{{ $cc }}-100 text-{{ $cc }}-700 text-xs px-2 py-0.5 rounded-full font-medium">{{ strtoupper($template->channel) }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 capitalize">{{ $template->recipient }}</td>
                    <td class="px-4 py-3 text-gray-700 max-w-xs truncate">
                        {{ $template->subject ?? $template->push_title ?? '(body only)' }}
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-block text-xs px-2 py-0.5 rounded-full {{ $template->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                            {{ $template->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.notifications.templates.edit', $template) }}" class="text-indigo-600 hover:underline text-xs mr-3">Edit</a>
                        <form method="POST" action="{{ route('admin.notifications.templates.destroy', $template) }}" class="inline"
                              onsubmit="return confirm('Delete this template?')">
                            @csrf @method('DELETE')
                            <button class="text-red-500 hover:underline text-xs">Delete</button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                        No templates yet.
                        <a href="{{ route('admin.notifications.templates.create') }}" class="text-indigo-600 hover:underline ml-1">Create one</a>
                        or
                        <a href="{{ route('admin.notifications.seed') }}" class="text-green-600 hover:underline ml-1"
                           onclick="return confirm('Seed all default templates?')">seed defaults</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($templates->isEmpty())
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-5">
        <p class="text-sm text-amber-700 font-medium mb-2">Quick tip: Seed default templates</p>
        <p class="text-sm text-amber-600 mb-3">Default templates cover all event types across all channels. You can edit them afterwards.</p>
        <a href="{{ route('admin.notifications.seed') }}" class="inline-block px-4 py-2 bg-amber-500 text-white rounded-lg text-sm font-medium hover:bg-amber-600"
           onclick="return confirm('This will create default templates for all event/channel combinations. Continue?')">
            Seed Default Templates
        </a>
    </div>
    @endif
</div>
@endsection
