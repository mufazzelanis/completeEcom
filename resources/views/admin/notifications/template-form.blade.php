@extends('layouts.admin')
@section('title', isset($template) ? 'Edit Template' : 'New Template')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ isset($template) ? 'Edit Template' : 'New Template' }}</h1>
        <a href="{{ route('admin.notifications.templates') }}" class="text-sm text-indigo-600 hover:underline">&larr; Back</a>
    </div>

    <form method="POST"
          action="{{ isset($template) ? route('admin.notifications.templates.update', $template) : route('admin.notifications.templates.store') }}"
          class="bg-white rounded-xl shadow-sm border p-6 space-y-5">
        @csrf
        @if(isset($template)) @method('PUT') @endif

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            @if(!isset($template))
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Event Type <span class="text-red-500">*</span></label>
                <select name="event_type" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">Select event</option>
                    @foreach($eventTypes as $ev)
                    <option value="{{ $ev }}" @selected(old('event_type') === $ev)>{{ str_replace('_', ' ', $ev) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Channel <span class="text-red-500">*</span></label>
                <select name="channel" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">Select channel</option>
                    @foreach($channels as $ch)
                    <option value="{{ $ch }}" @selected(old('channel') === $ch)>{{ strtoupper($ch) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipient <span class="text-red-500">*</span></label>
                <select name="recipient" class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500" required>
                    <option value="">Select</option>
                    <option value="customer" @selected(old('recipient') === 'customer')>Customer</option>
                    <option value="admin" @selected(old('recipient') === 'admin')>Admin</option>
                </select>
            </div>
            @else
            <div class="col-span-3 flex gap-4 text-sm text-gray-700">
                <div><span class="font-medium">Event:</span> {{ $template->event_type }}</div>
                <div><span class="font-medium">Channel:</span> {{ strtoupper($template->channel) }}</div>
                <div><span class="font-medium">Recipient:</span> {{ ucfirst($template->recipient) }}</div>
            </div>
            @endif
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Subject <span class="text-gray-400 text-xs">(email only)</span></label>
            <input type="text" name="subject" value="{{ old('subject', $template->subject ?? '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="e.g. Your order @{{order_number}} has been placed">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Push Title <span class="text-gray-400 text-xs">(push only)</span></label>
            <input type="text" name="push_title" value="{{ old('push_title', $template->push_title ?? '') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
                   placeholder="e.g. Order Update">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Body <span class="text-red-500">*</span></label>
            <textarea name="body" rows="6" required
                      class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 font-mono"
                      placeholder="Use @{{variable}} placeholders...">{{ old('body', $template->body ?? '') }}</textarea>
            <p class="text-xs text-gray-400 mt-1">Available variables depend on the event type. Common: @{{order_number}}, @{{customer}}, @{{total}}, @{{status}}, @{{url}}, @{{ticket_number}}, @{{subject}}</p>
        </div>

        <div class="flex items-center gap-2">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded"
                   @checked(old('is_active', $template->is_active ?? true))>
            <label for="is_active" class="text-sm text-gray-700">Active (will be sent when event fires)</label>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('admin.notifications.templates') }}" class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">Cancel</a>
            <button type="submit" class="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                {{ isset($template) ? 'Save Changes' : 'Create Template' }}
            </button>
        </div>
    </form>
</div>
@endsection
