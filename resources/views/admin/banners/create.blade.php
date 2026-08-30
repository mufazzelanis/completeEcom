@extends('layouts.admin')
@section('title', 'Add Banner')

@section('content')
<div class="max-w-2xl">
    <a href="{{ route('admin.banners.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Banners
    </a>

    <div class="bg-white rounded-2xl shadow-sm p-6">
        <form action="{{ route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5"
              x-data="{ position: '{{ old('position', 'hero') }}' }">
            @csrf

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" value="{{ old('title') }}" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subtitle</label>
                    <input type="text" name="subtitle" value="{{ old('subtitle') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" rows="2"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Button Text</label>
                    <input type="text" name="button_text" value="{{ old('button_text') }}" placeholder="Shop Now"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Button Link</label>
                    <input type="text" name="button_link" value="{{ old('button_link') }}" placeholder="/shop"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Background Color</label>
                    <div class="flex gap-2">
                        <input type="color" name="bg_color" value="{{ old('bg_color', '#1e1b4b') }}" class="w-10 h-10 rounded border border-gray-200 cursor-pointer">
                        <input type="text" value="{{ old('bg_color', '#1e1b4b') }}" placeholder="#1e1b4b" class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Text Color</label>
                    <div class="flex gap-2">
                        <input type="color" name="text_color" value="{{ old('text_color', '#ffffff') }}" class="w-10 h-10 rounded border border-gray-200 cursor-pointer">
                        <input type="text" value="{{ old('text_color', '#ffffff') }}" placeholder="#ffffff" class="flex-1 border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                    <select name="position" x-model="position" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach(['hero','top','middle','bottom','sidebar','popup'] as $pos)
                            <option value="{{ $pos }}" {{ old('position') === $pos ? 'selected' : '' }}>{{ ucfirst($pos) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                    <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div class="col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image</label>
                    <input type="file" name="image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                    {{-- The exact ratio here MUST match the display container's aspect-[...]
                         class (home.blade.php's hero slider and promo-tile grid) — mismatched
                         ratios are what causes object-cover to crop the image on the live site
                         (e.g. an image with a taller ratio than "hero" getting its top/bottom
                         cut off there). Upload at exactly this ratio, ideally 2x for retina
                         screens, and nothing will ever be cropped regardless of device. --}}
                    <p class="text-xs text-gray-500 mt-1" x-show="position === 'hero'" x-cloak>
                        Recommended: <strong>1920×600px</strong> (16:5 ratio) — this fills the full-width slider at the top of the homepage on every screen size, phone included.
                    </p>
                    <p class="text-xs text-gray-500 mt-1" x-show="position === 'top'" x-cloak>
                        Recommended: <strong>1200×600px</strong> (2:1 ratio) — this is one of the small promo tiles below Categories on the homepage.
                    </p>
                    <p class="text-xs text-gray-500 mt-1" x-show="!['hero','top'].includes(position)" x-cloak>
                        A wide, landscape-orientation image works best here.
                    </p>
                    <p class="text-xs text-gray-400 mt-1">Upload at that exact ratio (or a larger multiple of it, e.g. double for a sharper look on big screens) — a different ratio will get cropped to fit.</p>
                </div>
                <div class="col-span-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', 1) ? 'checked' : '' }} class="rounded text-indigo-600">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Create Banner</button>
                <a href="{{ route('admin.banners.index') }}" class="px-6 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-600 hover:bg-gray-50 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
