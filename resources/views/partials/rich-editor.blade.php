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

<div class="rich-editor" x-data x-init="
    const hidden = $refs.{{ $editorId }}Input;
    const editor = $($refs.{{ $editorId }}Editor);
    editor.summernote({
        height: 220,
        placeholder: {{ Js::from($placeholder ?? 'Write here…') }},
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'hr']],
            ['view', ['fullscreen', 'codeview']],
        ],
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
</div>
