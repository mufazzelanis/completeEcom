{{-- Reusable Summernote rich-text editor for long-form description/content
     fields (product description, blog post content, page content). Include with:
     @include('partials.rich-editor', ['name' => 'description', 'value' => old('description', $product->description ?? ''), 'id' => 'description']) --}}
@php
    $editorId = $id ?? $name ?? 'editor';
    $fieldName = $name ?? 'content';
@endphp

@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
        <style>
            .rich-editor .note-editor.note-frame { border-color: rgb(229 231 235); border-radius: 0.75rem; }
            .rich-editor .note-toolbar { border-radius: 0.75rem 0.75rem 0 0; background: rgb(249 250 251); }
            .rich-editor .note-editing-area { min-height: 220px; }
            .rich-editor .note-statusbar { border-radius: 0 0 0.75rem 0.75rem; }

            /* The "Style" toolbar dropdown (Normal / Header 1-6 / Quote / Code) previews each
               option in its own actual tag — but Tailwind's preflight resets h1-h6 to
               font-size:inherit/font-weight:inherit site-wide, so without this it renders as
               a flat, identically-sized list (no visual size cue for which header is which)
               instead of the size-graded picker Summernote intends. Scoped to .rich-editor so
               nothing outside the editor is affected. */
            .rich-editor .note-dropdown-menu { border-color: rgb(229 231 235); border-radius: 0.5rem; box-shadow: 0 4px 12px rgba(0,0,0,.08); padding: 0.25rem; }
            .rich-editor .note-dropdown-item { display: block; padding: 0.4rem 0.65rem; border-radius: 0.375rem; font-size: 0.8125rem; color: rgb(55 65 81); text-decoration: none; }
            .rich-editor .note-dropdown-item:hover { background: rgb(238 242 255); color: rgb(67 56 202); }
            .rich-editor .note-dropdown-item h1, .rich-editor .note-dropdown-item h2, .rich-editor .note-dropdown-item h3,
            .rich-editor .note-dropdown-item h4, .rich-editor .note-dropdown-item h5, .rich-editor .note-dropdown-item h6,
            .rich-editor .note-dropdown-item blockquote, .rich-editor .note-dropdown-item pre {
                margin: 0; font-weight: 700; line-height: 1.2; color: inherit;
            }
            .rich-editor .note-dropdown-item h1 { font-size: 1.375rem; }
            .rich-editor .note-dropdown-item h2 { font-size: 1.2rem; }
            .rich-editor .note-dropdown-item h3 { font-size: 1.075rem; }
            .rich-editor .note-dropdown-item h4 { font-size: 0.95rem; }
            .rich-editor .note-dropdown-item h5 { font-size: 0.85rem; }
            .rich-editor .note-dropdown-item h6 { font-size: 0.75rem; font-weight: 600; color: rgb(107 114 128); }
            .rich-editor .note-dropdown-item blockquote, .rich-editor .note-dropdown-item pre { font-size: 0.8125rem; font-weight: 400; font-style: italic; color: rgb(107 114 128); }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    @endpush
@endonce

<div class="rich-editor" x-data="{
        promoOpen: false,
        promoUrl: '',
        promoAlt: '',
        promoButtonText: '',
        promoFile: null,
        promoUploading: false,
        promoError: '',
        _promoCtx: null,
        openPromo(ctx) {
            this._promoCtx = ctx;
            this.promoUrl = ''; this.promoAlt = ''; this.promoButtonText = ''; this.promoFile = null; this.promoError = '';
            this.promoOpen = true;
        },
        async insertPromo() {
            if (!this.promoFile || !this.promoUrl) { this.promoError = 'Please choose an image and enter a link URL.'; return; }
            this.promoUploading = true;
            this.promoError = '';
            try {
                const fd = new FormData();
                fd.append('image', this.promoFile);
                const res = await fetch('{{ route('rich-editor.upload-image') }}', {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    body: fd,
                });
                const data = await res.json();
                if (!data.url) throw new Error('upload failed');
                // Built via \x26 (not a literal &) — the browser decodes anything that LOOKS
                // like an HTML entity (e.g. a literal '&amp;' typed here) while parsing this
                // double-quoted x-data attribute, before Alpine/JS ever sees it, which silently
                // turns these replacement strings back into raw &/'/</> and breaks the script.
                const esc = (s) => String(s).replace(/&/g, '\x26amp;').replace(/'/g, '\x26#39;').replace(/</g, '\x26lt;').replace(/>/g, '\x26gt;');
                const label = this.promoButtonText.trim() || 'Shop Now →';
                const html = '<a href=\'' + esc(this.promoUrl) + '\' target=\'_blank\' rel=\'noopener\' class=\'blog-promo-link\'>'
                    + '<span class=\'blog-promo-frame\'>'
                    + '<img src=\'' + esc(data.url) + '\' alt=\'' + esc(this.promoAlt) + '\' class=\'blog-promo-img\'>'
                    + '<span class=\'blog-promo-badge\'>' + esc(label) + '</span>'
                    + '</span></a>';
                this._promoCtx.invoke('editor.restoreRange');
                this._promoCtx.invoke('editor.focus');
                this._promoCtx.invoke('editor.pasteHTML', html);
                this.promoOpen = false;
            } catch (e) {
                this.promoError = 'Image upload failed. Please try again.';
            } finally {
                this.promoUploading = false;
            }
        },
    }" x-init="
    const hidden = $refs.{{ $editorId }}Input;
    const editor = $($refs.{{ $editorId }}Editor);
    {{-- `this` inside x-init isn't reliably bound to the component's reactive data (it can
         resolve to `window` once the init body has enough statements) — `$data` is Alpine's
         own magic property for the current component's scope, so use that instead. --}}
    const alpineData = $data;
    editor.summernote({
        height: 220,
        placeholder: {{ Js::from($placeholder ?? 'Write here…') }},
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'promoImage', 'hr']],
            ['view', ['fullscreen', 'codeview']],
        ],
        buttons: {
            // A plain inserted picture has no way to link anywhere without switching to
            // codeview and hand-writing an <a> tag — this button uploads + wraps an image
            // in a link in one step, specifically so a non-technical admin can drop a
            // clickable promo banner (to a product, sale, landing page) straight into a post.
            promoImage: function (context) {
                const ui = $.summernote.ui;
                const button = ui.button({
                    contents: '<i class=\'note-icon-picture\'></i><sup style=\'font-size:9px;margin-left:1px;\'>🔗</sup>',
                    tooltip: 'Clickable Promo Image (links to a product/page)',
                    click: function () {
                        context.invoke('editor.saveRange');
                        alpineData.openPromo(context);
                    },
                });
                return button.render();
            },
        },
        callbacks: {
            onChange: function (contents) { hidden.value = contents; },
            onImageUpload: function (files) {
                for (let i = 0; i < files.length; i++) {
                    const fd = new FormData();
                    fd.append('image', files[i]);
                    fetch('{{ route('rich-editor.upload-image') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: fd,
                    })
                    .then(r => r.json())
                    .then(data => { editor.summernote('insertImage', data.url); })
                    .catch(() => alert('Image upload failed.'));
                }
            },
        },
    });
    editor.summernote('code', hidden.value);
    $el.closest('form')?.addEventListener('submit', () => { hidden.value = editor.summernote('code'); });
">
    <div x-ref="{{ $editorId }}Editor"></div>
    <textarea name="{{ $fieldName }}" x-ref="{{ $editorId }}Input" class="hidden">{{ $value }}</textarea>

    {{-- Promo Image modal — x-show sets style="display:none" directly (not the [hidden]
         attribute), so it's immune to the earlier hidden+flex Tailwind specificity bug. --}}
    {{-- @submit.prevent (belt-and-suspenders with @keydown.enter.prevent below) stops Enter
         in any of these text inputs from bubbling up to submit/publish the OUTER admin form
         these modal fields live inside — they have no name= attributes so they'd never be
         posted, but a native Enter-triggered submit would still fire it prematurely. --}}
    <div x-show="promoOpen" x-cloak @keydown.escape.window="promoOpen = false"
         @keydown.enter.prevent="insertPromo()"
         class="fixed inset-0 z-[9999] bg-gray-900/40 backdrop-blur-sm">
        <div class="w-full h-full flex items-center justify-center px-4">
            <div @click.outside="promoOpen = false" @submit.prevent class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
                <h3 class="text-base font-bold text-gray-900 mb-1">Insert Clickable Promo Image</h3>
                <p class="text-xs text-gray-500 mb-4">Drop a banner into the post that links straight to a product, sale, or landing page.</p>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Image</label>
                        <input type="file" accept="image/*" @change="promoFile = $event.target.files[0]"
                               class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Link URL (where clicking the image goes)</label>
                        <input type="url" x-model="promoUrl" placeholder="https://mitavin.com/products/…"
                               class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Button text (optional)</label>
                        <input type="text" x-model="promoButtonText" placeholder="Shop Now →"
                               class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Alt text (optional)</label>
                        <input type="text" x-model="promoAlt" placeholder="Describe the image"
                               class="w-full text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400">
                    </div>
                    <p x-show="promoError" x-cloak x-text="promoError" class="text-xs text-red-600"></p>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" @click="promoOpen = false" class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">Cancel</button>
                    <button type="button" @click="insertPromo()" :disabled="promoUploading"
                            class="px-4 py-2 text-sm rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-700 transition disabled:opacity-50">
                        <span x-show="!promoUploading">Insert</span>
                        <span x-show="promoUploading" x-cloak>Uploading…</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
