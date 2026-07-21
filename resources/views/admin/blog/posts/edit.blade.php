@extends('layouts.admin')
@section('title', 'Edit Blog Post')

@section('content')
<div class="max-w-4xl"
    x-data="{
        title: {{ Js::from(old('title', $blogPost->title)) }},
        slug: {{ Js::from(old('slug', $blogPost->slug)) }},
        slugTouched: true,
        excerpt: {{ Js::from(old('excerpt', $blogPost->excerpt ?? '')) }},
        metaTitle: {{ Js::from(old('meta_title', $blogPost->meta_title ?? '')) }},
        metaDesc: {{ Js::from(old('meta_description', $blogPost->meta_description ?? '')) }},
        blogUrl: '{{ rtrim(url('/blog'), '/') }}/',
        slugify(str) {
            return (str || '').toString().toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/[\s_-]+/g, '-')
                .replace(/^-+|-+$/g, '');
        },
        get serpTitle() { return this.metaTitle || this.title || 'Your post title'; },
        get serpDesc() { return this.metaDesc || this.excerpt || 'Add a meta description or excerpt so Google shows something useful here instead of guessing from your content.'; },
        get titleLen() { return this.metaTitle.length; },
        get descLen() { return this.metaDesc.length; },
        counterClass(len, ideal, max) {
            if (len === 0) return 'text-gray-400';
            if (len > max) return 'text-red-500';
            if (len > ideal) return 'text-amber-500';
            return 'text-green-600';
        }
    }">
    <a href="{{ route('admin.blog.posts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to Posts
    </a>

    <form action="{{ route('admin.blog.posts.update', $blogPost) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf @method('PUT')
        <div class="grid grid-cols-3 gap-6">
            {{-- Main Content --}}
            <div class="col-span-2 space-y-5">
                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" x-model="title" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-400 flex-shrink-0" x-text="blogUrl"></span>
                            <input type="text" name="slug" x-model="slug" @input="slugTouched = true"
                                class="flex-1 min-w-0 border border-gray-200 rounded-xl px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('slug') border-red-400 @enderror">
                            <button type="button" @click="slug = slugify(title)"
                                class="flex-shrink-0 text-xs text-indigo-600 hover:text-indigo-800 font-medium">Regenerate</button>
                        </div>
                        @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        <p class="text-xs text-amber-600 mt-1">Changing the slug changes this post's public URL — old links will stop working.</p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Excerpt</label>
                            <span class="text-xs" :class="counterClass(excerpt.length, 140, 200)" x-text="excerpt.length + ' chars'"></span>
                        </div>
                        <textarea name="excerpt" x-model="excerpt" rows="2"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Content <span class="text-red-500">*</span></label>
                        <textarea name="content" rows="16" required
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono">{{ old('content', $blogPost->content) }}</textarea>
                    </div>
                </div>

                {{-- SEO --}}
                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        <h3 class="font-medium text-gray-800">Search Engine Preview</h3>
                    </div>

                    {{-- Google SERP preview --}}
                    <div class="border border-gray-100 rounded-xl p-4 bg-gray-50">
                        <p class="text-xs text-gray-400 mb-2" x-text="blogUrl + slug"></p>
                        <p class="text-[#1a0dab] text-lg leading-snug truncate" x-text="serpTitle" style="font-family: arial, sans-serif;"></p>
                        <p class="text-sm text-gray-600 mt-1 line-clamp-2" x-text="serpDesc"></p>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                            <span class="text-xs" :class="counterClass(titleLen, 60, 70)" x-text="titleLen + ' / 60'"></span>
                        </div>
                        <input type="text" name="meta_title" x-model="metaTitle" maxlength="255"
                            placeholder="Leave blank to use the post title"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                            <span class="text-xs" :class="counterClass(descLen, 160, 175)" x-text="descLen + ' / 160'"></span>
                        </div>
                        <textarea name="meta_description" x-model="metaDesc" rows="2" maxlength="500"
                            placeholder="Leave blank to use the excerpt"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                        <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $blogPost->meta_keywords) }}"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-5">
                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="draft"      {{ old('status', $blogPost->status) === 'draft'      ? 'selected' : '' }}>Draft</option>
                            <option value="published"  {{ old('status', $blogPost->status) === 'published'  ? 'selected' : '' }}>Published</option>
                            <option value="scheduled"  {{ old('status', $blogPost->status) === 'scheduled'  ? 'selected' : '' }}>Scheduled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Publish Date</label>
                        <input type="datetime-local" name="published_at"
                            value="{{ old('published_at', $blogPost->published_at?->clone()->setTimezone(setting('timezone', 'Asia/Dhaka'))->format('Y-m-d\TH:i')) }}"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Time is in {{ setting('timezone', 'Asia/Dhaka') }}.</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $blogPost->is_featured) ? 'checked' : '' }} class="rounded text-indigo-600">
                        <span class="text-sm font-medium text-gray-700">Featured Post</span>
                    </label>
                    <div class="text-xs text-gray-400">
                        {{ number_format($blogPost->views) }} views · Created {{ $blogPost->created_at->diffForHumans() }}
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium hover:bg-indigo-700 transition">Save Changes</button>
                    <a href="{{ route('blog.show', $blogPost) }}" target="_blank" class="block text-center text-xs text-indigo-600 hover:text-indigo-800 font-medium">View live post →</a>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="blog_category_id" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">No Category</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('blog_category_id', $blogPost->blog_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                        <input type="text" name="tags" value="{{ old('tags', $blogPost->tags->pluck('name')->join(', ')) }}"
                            placeholder="tag1, tag2, tag3"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <p class="text-xs text-gray-400 mt-1">Comma-separated. New tags are created automatically.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm p-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Featured Image</label>
                    @if($blogPost->image)
                        <img src="{{ Storage::url($blogPost->image) }}" class="w-full h-32 object-cover rounded-xl mb-3">
                    @endif
                    <input type="file" name="image" accept="image/*" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm">
                    <p class="text-xs text-gray-400 mt-2">Also used as the article's social share (Open Graph) image.</p>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
