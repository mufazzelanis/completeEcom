{{-- Post language + the same article in the other language (Bangla ⇄ English). The second
     version is published at /blog/{slug}/{bn|en}, with a language switch on the post and
     hreflang tags so Google shows each searcher their language. Leave it empty for a
     single-language post. --}}
@php
    $post = $blogPost ?? null;
    $lang = old('lang', $post?->lang ?? setting('ai_blog_default_lang', 'bn'));
@endphp
<div class="bg-white rounded-2xl shadow-sm p-6 space-y-5"
     x-data="{ lang: {{ Js::from($lang) }}, open: {{ Js::from((bool) old('alt_title', $post?->alt_title)) }} }">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h3 class="font-medium text-gray-800">Language</h3>
            <p class="text-xs text-gray-400 mt-0.5">The language the title and content above are written in.</p>
        </div>
        <select name="lang" x-model="lang" class="border border-gray-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @foreach(\App\Models\BlogPost::LANGS as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="border-t border-gray-100 pt-4">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between text-left">
            <span>
                <span class="font-medium text-gray-800" x-text="lang === 'bn' ? 'English version (optional)' : 'বাংলা version (optional)'"></span>
                <span class="block text-xs text-gray-400 mt-0.5">Readers get a বাংলা | English switch on the post. Leave empty for one language only.</span>
            </span>
            <svg class="w-4 h-4 text-gray-400 transition" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>

        <div x-show="open" x-cloak class="mt-4 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" name="alt_title" value="{{ old('alt_title', $post?->alt_title) }}" maxlength="255"
                    class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Excerpt</label>
                <textarea name="alt_excerpt" rows="2" class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">{{ old('alt_excerpt', $post?->alt_excerpt) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Content</label>
                @include('partials.rich-editor', ['name' => 'alt_content', 'value' => old('alt_content', $post?->alt_content), 'id' => 'alt_content', 'placeholder' => 'The same article in the other language…', 'productBlocks' => true])
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                    <input type="text" name="alt_meta_title" value="{{ old('alt_meta_title', $post?->alt_meta_title) }}" maxlength="255"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                    <input type="text" name="alt_meta_description" value="{{ old('alt_meta_description', $post?->alt_meta_description) }}" maxlength="500"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
            </div>
            @if($post?->hasAlt())
                <a href="{{ route('blog.show.lang', [$post, $post->altLang()]) }}" target="_blank" class="inline-block text-xs text-indigo-600 hover:text-indigo-800 font-medium">View this version →</a>
            @endif
        </div>
    </div>
</div>
