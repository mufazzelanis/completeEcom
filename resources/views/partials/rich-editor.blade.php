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
