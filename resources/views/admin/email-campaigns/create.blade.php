@extends('layouts.admin')
@section('title', 'New Email Campaign')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.email-campaigns.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-xl font-bold text-gray-800">New Email Campaign</h1>
</div>

<form action="{{ route('admin.email-campaigns.store') }}" method="POST" class="grid grid-cols-3 gap-6">
    @csrf

    {{-- Main Content --}}
    <div class="col-span-2 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h2 class="font-semibold text-gray-800">Email Content</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Campaign Name <span class="text-red-500">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required placeholder="Internal name (not visible to recipients)"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subject Line <span class="text-red-500">*</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" required placeholder="What your recipients will see in their inbox"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                @error('subject')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Preheader Text</label>
                <input type="text" name="preheader" value="{{ old('preheader') }}" placeholder="Preview text shown after subject in inbox (optional)"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Body (HTML) <span class="text-red-500">*</span></label>
                <div x-data="{ tab: 'edit' }">
                    <div class="flex gap-1 mb-2">
                        <button type="button" @click="tab = 'edit'" :class="tab==='edit' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition">Edit</button>
                        <button type="button" @click="tab = 'preview'" :class="tab==='preview' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-600'" class="px-3 py-1.5 rounded-lg text-xs font-medium transition">Preview</button>
                    </div>
                    <div x-show="tab === 'edit'">
                        <textarea name="content" id="content" rows="18" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('content') }}</textarea>
                    </div>
                    <div x-show="tab === 'preview'" x-cloak class="border border-gray-200 rounded-xl overflow-hidden bg-white">
                        <iframe id="preview-frame" class="w-full h-96" frameborder="0"></iframe>
                    </div>
                    <script>
                    document.addEventListener('alpine:init', () => {
                        document.querySelector('[x-data]').__x.$watch('tab', val => {
                            if (val === 'preview') {
                                const doc = document.getElementById('preview-frame').contentDocument;
                                doc.open(); doc.write(document.getElementById('content').value); doc.close();
                            }
                        });
                    });
                    </script>
                </div>
                @error('content')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-xs text-gray-400 mt-1">Write HTML email content. Use <code>{{ '{{$name}}' }}</code> for recipient name.</p>
            </div>
        </div>
    </div>

    {{-- Sidebar Settings --}}
    <div class="col-span-1 space-y-5">
        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
            <h2 class="font-semibold text-gray-800">Settings</h2>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Recipients <span class="text-red-500">*</span></label>
                <select name="recipient_type" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="all">All Users</option>
                    <option value="customers">Customers (placed order)</option>
                    <option value="new_30d">New Users (last 30 days)</option>
                    <option value="never_ordered">Never Ordered</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Name</label>
                <input type="text" name="from_name" value="{{ old('from_name', config('mail.from.name')) }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From Email</label>
                <input type="email" name="from_email" value="{{ old('from_email', config('mail.from.address')) }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Schedule (optional)</label>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"
                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <p class="text-xs text-gray-400 mt-1">Leave blank to save as draft</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm p-6 space-y-3">
            <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                Save Campaign
            </button>
            <a href="{{ route('admin.email-campaigns.index') }}" class="block w-full text-center bg-gray-100 text-gray-600 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
        </div>
    </div>
</form>
@endsection
