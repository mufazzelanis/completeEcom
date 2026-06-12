@extends('layouts.admin')
@section('title', 'New Flash Sale')

@section('content')
<div class="flex items-center gap-4 mb-6">
    <a href="{{ route('admin.flash-sales.index') }}" class="text-indigo-600 hover:text-indigo-700 text-sm flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg> Back
    </a>
    <h1 class="text-xl font-bold text-gray-800">New Flash Sale</h1>
</div>

<div class="max-w-2xl">
    <form action="{{ route('admin.flash-sales.store') }}" method="POST" class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
        @csrf

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sale Name <span class="text-red-500">*</span></label>
            <input type="text" name="name" value="{{ old('name') }}" required
                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea name="description" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Banner Text</label>
                <input type="text" name="banner_text" value="{{ old('banner_text') }}" placeholder="e.g. ⚡ 4-Hour Flash Sale!"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Banner Color</label>
                <div class="flex items-center gap-3">
                    <input type="color" name="banner_color" value="{{ old('banner_color','#ef4444') }}" class="h-10 w-14 border border-gray-200 rounded-lg cursor-pointer">
                    <span class="text-xs text-gray-400">Background color for the sale banner</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Starts At <span class="text-red-500">*</span></label>
                <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ends At <span class="text-red-500">*</span></label>
                <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}" required
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="flex items-center gap-3">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="w-4 h-4 text-indigo-600 rounded">
            <label for="is_active" class="text-sm text-gray-700">Active (show sale when scheduled time arrives)</label>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                Create & Add Products
            </button>
            <a href="{{ route('admin.flash-sales.index') }}" class="bg-gray-100 text-gray-600 px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-gray-200 transition">Cancel</a>
        </div>
    </form>
</div>
@endsection
