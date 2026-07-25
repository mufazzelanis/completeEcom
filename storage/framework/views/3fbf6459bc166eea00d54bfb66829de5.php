<?php $__env->startSection('title', $page?->meta_title ?? $page?->title ?? 'FAQ'); ?>

<?php $__env->startSection('content'); ?>
<div class="max-w-3xl mx-auto px-4 py-12">
    <div class="text-center mb-10">
        <h1 class="text-3xl font-bold text-gray-900"><?php echo e($page?->title ?? 'Frequently Asked Questions'); ?></h1>
        <?php if($page?->excerpt): ?><p class="text-gray-500 mt-2"><?php echo e($page->excerpt); ?></p><?php endif; ?>
    </div>

    <?php if($faqs->isEmpty()): ?>
    <p class="text-center text-gray-400">No FAQs available yet.</p>
    <?php else: ?>
    <div class="space-y-6">
        <?php $__currentLoopData = $faqs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoryName => $items): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php if($categoryName): ?>
        <h2 class="text-lg font-semibold text-gray-700 border-b border-gray-100 pb-2"><?php echo e($categoryName); ?></h2>
        <?php endif; ?>
        <div x-data="{ open: null }" class="space-y-2">
            <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $faq): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden">
                <button @click="open === <?php echo e($i); ?> ? open = null : open = <?php echo e($i); ?>"
                    class="w-full flex items-center justify-between px-6 py-4 text-left hover:bg-gray-50 transition">
                    <span class="font-medium text-gray-800 text-sm pr-4"><?php echo e($faq->question); ?></span>
                    <svg class="w-5 h-5 text-gray-400 flex-shrink-0 transition-transform" :class="open === <?php echo e($i); ?> ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open === <?php echo e($i); ?>" x-collapse class="px-6 pb-4">
                    <p class="text-sm text-gray-600 leading-relaxed"><?php echo e($faq->answer); ?></p>
                </div>
            </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/pages/faq.blade.php ENDPATH**/ ?>