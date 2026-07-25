{{-- Reusable rich-text editor for the product Full Description field. Include with:
     @include('admin.products._description_editor', ['name' => 'description', 'value' => old('description', $product->description ?? ''), 'id' => 'description']) --}}
@php
    $editorId = $id ?? 'description';
    $fieldName = $name ?? 'description';
@endphp

@once
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
        <style>
            .rich-editor .ql-toolbar { border-radius: 0.75rem 0.75rem 0 0; border-color: rgb(229 231 235); background: rgb(249 250 251); }
            .rich-editor .ql-container { border-radius: 0 0 0.75rem 0.75rem; border-color: rgb(229 231 235); font-size: 0.875rem; min-height: 200px; }
            .rich-editor .ql-editor { min-height: 200px; }
        </style>
    @endpush
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    @endpush
@endonce

<div class="rich-editor" x-data x-init="
    const hidden = $refs.{{ $editorId }}Input;
    const quill = new Quill($refs.{{ $editorId }}Editor, {
        theme: 'snow',
        placeholder: 'Describe the product in detail — features, materials, sizing, care instructions…',
        modules: {
            toolbar: [
                [{ header: [2, 3, false] }],
                ['bold', 'italic', 'underline', 'strike'],
                [{ color: [] }, { background: [] }],
                [{ list: 'ordered' }, { list: 'bullet' }],
                [{ align: [] }],
                ['blockquote', 'code-block', 'link', 'image'],
                ['clean'],
            ],
        },
    });
    quill.root.innerHTML = hidden.value;
    quill.on('text-change', () => { hidden.value = quill.root.innerHTML; });
    $el.closest('form')?.addEventListener('submit', () => { hidden.value = quill.root.innerHTML; });
">
    <div x-ref="{{ $editorId }}Editor"></div>
    <textarea name="{{ $fieldName }}" x-ref="{{ $editorId }}Input" class="hidden">{{ $value }}</textarea>
</div>
