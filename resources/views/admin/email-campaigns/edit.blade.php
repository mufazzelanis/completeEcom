@extends('layouts.admin')
@section('title', 'Edit Campaign')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.email-campaigns.show', $emailCampaign) }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-xl font-bold text-gray-800">Edit: {{ $emailCampaign->name }}</h1>
</div>

<form action="{{ route('admin.email-campaigns.update', $emailCampaign) }}" method="POST" class="grid grid-cols-3 gap-6">
    @csrf @method('PUT')

    <div class="col-span-2 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name</label>
                <input type="text" name="name" value="{{ old('name', $emailCampaign->name) }}" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject Line</label>
                <input type="text" name="subject" value="{{ old('subject', $emailCampaign->subject) }}" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Preheader</label>
                <input type="text" name="preheader" value="{{ old('preheader', $emailCampaign->preheader) }}"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Body (HTML)</label>
                <textarea name="content" rows="18" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('content', $emailCampaign->content) }}</textarea>
            </div>
        </div>
    </div>

    <div class="col-span-1 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipients</label>
                <select name="recipient_type" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    @foreach(['all'=>'All Users','customers'=>'Customers (placed order)','new_30d'=>'New (last 30 days)','never_ordered'=>'Never Ordered'] as $val => $label)
                    <option value="{{ $val }}" {{ old('recipient_type', $emailCampaign->recipient_type) === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                <input type="text" name="from_name" value="{{ old('from_name', $emailCampaign->from_name) }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Email</label>
                <input type="email" name="from_email" value="{{ old('from_email', $emailCampaign->from_email) }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Schedule</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $emailCampaign->scheduled_at?->format('Y-m-d\TH:i')) }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>
        <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save Changes</button>
    </div>
</form>
@endsection
