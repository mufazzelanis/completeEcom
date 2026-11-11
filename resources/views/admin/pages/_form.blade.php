{{-- Add / edit page — one form for both. $page is null on create. --}}
@php
    $page ??= null;
    $locked = $page && in_array($page->slug, \App\Http\Controllers\Admin\PageController::SYSTEM_SLUGS, true);
    $input = 'w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500';
@endphp
<form action="{{ $page ? route('admin.pages.update', $page) : route('admin.pages.store') }}" method="POST" enctype="multipart/form-data"
    x-data="{
        title: {{ Js::from(old('title', $page?->title ?? '')) }},
        slug: {{ Js::from(old('slug', $page?->slug ?? '')) }},
        slugTouched: {{ Js::from((bool) ($page || old('slug'))) }},
        excerpt: {{ Js::from(old('excerpt', $page?->excerpt ?? '')) }},
        metaTitle: {{ Js::from(old('meta_title', $page?->meta_title ?? '')) }},
        metaDesc: {{ Js::from(old('meta_description', $page?->meta_description ?? '')) }},
        base: {{ Js::from(rtrim(url('/pages'), '/') . '/') }},
        slugify(s) {
            return (s || '').toString().toLowerCase().trim()
                .replace(/[^a-z0-9\s-]/g, '').replace(/[\s_-]+/g, '-').replace(/^-+|-+$/g, '');
        },
        syncSlug() { if (!this.slugTouched) this.slug = this.slugify(this.title); },
        get serpTitle() { return this.metaTitle || this.title || 'Page title'; },
        get serpDesc() { return this.metaDesc || this.excerpt || 'Add a meta description so Google shows a useful summary instead of guessing one from the page.'; },
        counter(len, ideal, max) { return len === 0 ? 'text-gray-400' : (len > max ? 'text-red-500' : (len > ideal ? 'text-amber-500' : 'text-green-600')); },
    }">
    @csrf
    @if($page) @method('PUT') @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
        {{-- Main --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                    <input type="text" name="title" x-model="title" @input="syncSlug()" required maxlength="255" placeholder="e.g. About Us"
                        class="{{ $input }} text-base font-medium">
                    @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">URL Slug</label>
                    <div class="flex items-stretch rounded-xl border {{ $errors->has('slug') ? 'border-red-400' : 'border-gray-200' }} focus-within:ring-2 focus-within:ring-indigo-500 overflow-hidden">
                        <span class="hidden sm:flex items-center bg-gray-50 dark:bg-gray-800 px-3 text-xs text-gray-400 border-r border-gray-200 whitespace-nowrap" x-text="base"></span>
                        <input type="text" name="slug" x-model="slug" @input="slugTouched = true" maxlength="120" placeholder="about-us"
                            @if($locked) readonly @endif
                            class="flex-1 min-w-0 border-0 px-3 py-2.5 text-sm font-mono focus:outline-none focus:ring-0 bg-transparent {{ $locked ? 'text-gray-400' : '' }}">
                        @unless($locked)
                            <button type="button" @click="slug = slugify(title); slugTouched = false" class="px-3 text-xs font-medium text-indigo-600 hover:text-indigo-800 border-l border-gray-200">Generate</button>
                        @endunless
                    </div>
                    @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    @if($locked)
                        <p class="text-xs text-gray-400 mt-1">This page is linked from the site's menus, so its address can't change.</p>
                    @elseif($page)
                        <p class="text-xs text-amber-600 mt-1" x-show="slug !== {{ Js::from($page->slug) }}" x-cloak>The old address will automatically redirect (301) to the new one, so links and Google rankings are kept.</p>
                    @else
                        <p class="text-xs text-gray-400 mt-1">Short English words, e.g. <code>about-us</code>. Filled in from the title — Bangla titles need an English slug.</p>
                    @endif
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Excerpt <span class="text-gray-400 font-normal">(short summary)</span></label>
                        <span class="text-xs" :class="counter(excerpt.length, 160, 220)" x-text="excerpt.length + ' chars'"></span>
                    </div>
                    <textarea name="excerpt" x-model="excerpt" rows="2" maxlength="500" class="{{ $input }}"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                    @include('partials.rich-editor', ['name' => 'content', 'value' => old('content', $page?->content ?? ''), 'id' => 'content', 'placeholder' => 'Write the page content…'])
                </div>
            </div>

            {{-- SEO --}}
            <div class="bg-white rounded-2xl shadow-sm p-6 space-y-5">
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    <h3 class="font-medium text-gray-800">Search engine preview</h3>
                </div>
                <div class="rounded-xl border border-gray-100 bg-gray-50 dark:bg-gray-800/50 p-4">
                    <p class="text-xs text-gray-500 truncate" x-text="base + (slug || 'your-page')"></p>
                    <p class="text-[#1a0dab] dark:text-[#8ab4f8] text-lg leading-snug truncate" style="font-family: arial, sans-serif" x-text="serpTitle"></p>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1 line-clamp-2" x-text="serpDesc"></p>
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                        <span class="text-xs" :class="counter(metaTitle.length, 60, 70)" x-text="metaTitle.length + ' / 60'"></span>
                    </div>
                    <input type="text" name="meta_title" x-model="metaTitle" maxlength="255" placeholder="Leave blank to use the page title" class="{{ $input }}">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                        <span class="text-xs" :class="counter(metaDesc.length, 160, 175)" x-text="metaDesc.length + ' / 160'"></span>
                    </div>
                    <textarea name="meta_description" x-model="metaDesc" rows="2" maxlength="500" placeholder="One or two sentences shown under the title in Google" class="{{ $input }}"></textarea>
                </div>

                <details class="group" @if(old('og_title', $page?->og_title) || old('canonical_url', $page?->canonical_url) || $page?->og_image) open @endif>
                    <summary class="cursor-pointer text-sm font-medium text-gray-600 select-none">Advanced — social sharing, keywords, canonical</summary>
                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Social title (OG)</label>
                            <input type="text" name="og_title" value="{{ old('og_title', $page?->og_title) }}" maxlength="255" placeholder="Defaults to the meta title" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Meta Keywords</label>
                            <input type="text" name="meta_keywords" value="{{ old('meta_keywords', $page?->meta_keywords) }}" maxlength="255" placeholder="keyword1, keyword2" class="{{ $input }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Social description (OG)</label>
                            <textarea name="og_description" rows="2" maxlength="500" placeholder="Defaults to the meta description" class="{{ $input }}">{{ old('og_description', $page?->og_description) }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Canonical URL</label>
                            <input type="url" name="canonical_url" value="{{ old('canonical_url', $page?->canonical_url) }}" placeholder="Leave blank — only for a page copied from another address" class="{{ $input }}">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Social share image</label>
                            @if($page?->og_image)
                                <img src="{{ Storage::url($page->og_image) }}" alt="" class="w-40 h-[84px] object-cover rounded-xl mb-2">
                            @endif
                            <input type="file" name="og_image" accept="image/*" class="{{ $input }}">
                            <p class="text-xs text-gray-400 mt-1">1200×630px — the preview on Facebook, WhatsApp, Messenger. Defaults to the featured image.</p>
                        </div>
                    </div>
                </details>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6 lg:sticky lg:top-24">
            <div class="bg-white rounded-2xl shadow-sm p-6 space-y-4">
                <label class="flex items-center justify-between gap-3 cursor-pointer">
                    <span>
                        <span class="block text-sm font-medium text-gray-800">Published</span>
                        <span class="block text-xs text-gray-400">Off = hidden from visitors and Google.</span>
                    </span>
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $page?->is_active ?? true)) class="w-5 h-5 rounded text-indigo-600">
                </label>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Page type</label>
                    <select name="type" class="{{ $input }}">
                        @foreach(['static' => 'Static (About, Policy, Terms…)', 'seo' => 'SEO content page', 'landing' => 'Landing'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('type', $page?->type ?? 'static') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Layout</label>
                    <select name="template" class="{{ $input }}">
                        @foreach(['default' => 'Standard page', 'faq' => 'FAQ', 'contact' => 'Contact', 'landing' => 'Landing'] as $val => $label)
                            <option value="{{ $val }}" @selected(old('template', $page?->template ?? 'default') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sort order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $page?->sort_order ?? 0) }}" class="{{ $input }}">
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-indigo-700 transition">{{ $page ? 'Save changes' : 'Create page' }}</button>
                @if($page)
                    <a href="{{ route('pages.show', $page) }}" target="_blank" class="block text-center text-xs font-medium text-indigo-600 hover:text-indigo-800">View page →</a>
                    <p class="text-center text-xs text-gray-400">Updated {{ $page->updated_at->diffForHumans() }}</p>
                @endif
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">Featured image <span class="text-gray-400 font-normal">(optional)</span></label>
                @if($page?->image)
                    <img src="{{ Storage::url($page->image) }}" alt="" class="w-full h-28 object-cover rounded-xl mb-3">
                @endif
                <input type="file" name="image" accept="image/*" class="{{ $input }}">
                <p class="text-xs text-gray-400 mt-1">1600×640px — a wide banner across the top of the page.</p>
            </div>
        </div>
    </div>
</form>
