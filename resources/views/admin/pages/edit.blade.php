@extends('layouts.admin')
@section('title', 'Edit Page')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('admin.pages.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Pages
    </a>

    <form action="{{ route('admin.pages.update', $page) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')
        <div class="grid grid-cols-3 gap-6">
            <div class="col-span-2 space-y-5">
                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" value="{{ old('title', $page->title) }}" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Slug</label>
                        <input type="text" value="{{ $page->slug }}" disabled class="w-full border border-gray-100 bg-gray-50 rounded-xl px-4 py-2.5 text-sm text-gray-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
                        <textarea name="excerpt" rows="2"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('excerpt', $page->excerpt) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                        <textarea name="content" rows="14"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">{{ old('content', $page->content) }}</textarea>
                        <p class="text-xs text-gray-400 mt-1">HTML is supported.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
                    <h3 class="font-medium text-gray-800">SEO & Open Graph</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                            <input type="text" name="meta_title" value="{{ old('meta_title', $page->meta_title) }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">OG Title</label>
                            <input type="text" name="og_title" value="{{ old('og_title', $page->og_title) }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                            <textarea name="meta_description" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('meta_description', $page->meta_description) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">OG Description</label>
                            <textarea name="og_description" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('og_description', $page->og_description) }}</textarea>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                            <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $page->meta_keywords) }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Canonical URL</label>
                            <input type="url" name="canonical_url" value="{{ old('canonical_url', $page->canonical_url) }}"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">OG Image</label>
                            @if($page->og_image)
                                <img src="{{ Storage::url($page->og_image) }}" class="w-32 h-20 object-cover rounded-xl mb-2">
                            @endif
                            <input type="file" name="og_image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                        <select name="type" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="static"  {{ old('type', $page->type) === 'static'  ? 'selected' : '' }}>Static</option>
                            <option value="landing" {{ old('type', $page->type) === 'landing' ? 'selected' : '' }}>Landing</option>
                            <option value="seo"     {{ old('type', $page->type) === 'seo'     ? 'selected' : '' }}>SEO</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Template</label>
                        <select name="template" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="default" {{ old('template', $page->template) === 'default' ? 'selected' : '' }}>Default</option>
                            <option value="faq"     {{ old('template', $page->template) === 'faq'     ? 'selected' : '' }}>FAQ</option>
                            <option value="contact" {{ old('template', $page->template) === 'contact' ? 'selected' : '' }}>Contact</option>
                            <option value="landing" {{ old('template', $page->template) === 'landing' ? 'selected' : '' }}>Landing</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                        <input type="number" name="sort_order" value="{{ old('sort_order', $page->sort_order) }}"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $page->is_active) ? 'checked' : '' }} class="rounded text-indigo-600">
                        <span class="text-sm font-medium text-gray-700">Active</span>
                    </label>
                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save Changes</button>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                    @if($page->image)
                        <img src="{{ Storage::url($page->image) }}" class="w-full h-24 object-cover rounded-xl mb-3">
                    @endif
                    <input type="file" name="image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
