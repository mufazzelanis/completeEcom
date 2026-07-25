
<?php
    $editorId = $id ?? 'description';
    $fieldName = $name ?? 'description';
?>

<?php if (! $__env->hasRenderedOnce('c2fed4ab-f4c8-4db6-8193-98d7c7012807')): $__env->markAsRenderedOnce('c2fed4ab-f4c8-4db6-8193-98d7c7012807'); ?>
    <?php $__env->startPush('styles'); ?>
        <link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
        <style>
            .rich-editor .ql-toolbar { border-radius: 0.75rem 0.75rem 0 0; border-color: rgb(229 231 235); background: rgb(249 250 251); }
            .rich-editor .ql-container { border-radius: 0 0 0.75rem 0.75rem; border-color: rgb(229 231 235); font-size: 0.875rem; min-height: 200px; }
            .rich-editor .ql-editor { min-height: 200px; }
        </style>
    <?php $__env->stopPush(); ?>
    <?php $__env->startPush('scripts'); ?>
        <script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
    <?php $__env->stopPush(); ?>
<?php endif; ?>

<div class="rich-editor" x-data x-init="
    const hidden = $refs.<?php echo e($editorId); ?>Input;
    const quill = new Quill($refs.<?php echo e($editorId); ?>Editor, {
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
    <div x-ref="<?php echo e($editorId); ?>Editor"></div>
    <textarea name="<?php echo e($fieldName); ?>" x-ref="<?php echo e($editorId); ?>Input" class="hidden"><?php echo e($value); ?></textarea>
</div>
<?php /**PATH C:\xampp\htdocs\azad-ecom\resources\views/admin/products/_description_editor.blade.php ENDPATH**/ ?>