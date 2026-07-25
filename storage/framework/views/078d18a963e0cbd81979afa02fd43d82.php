<?php $recaptcha = app(\App\Services\RecaptchaService::class); ?>
<?php if($recaptcha->enabled()): ?>
    <?php if($recaptcha->version() === 'v3'): ?>
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        <script src="https://www.google.com/recaptcha/api.js?render=<?php echo e($recaptcha->siteKey()); ?>"></script>
        <script>
            (function () {
                var form = document.currentScript.closest('form');
                if (!form) return;
                form.addEventListener('submit', function (e) {
                    if (document.getElementById('recaptcha_token').value) return; // already fetched
                    e.preventDefault();
                    grecaptcha.ready(function () {
                        grecaptcha.execute('<?php echo e($recaptcha->siteKey()); ?>', { action: 'submit' }).then(function (token) {
                            document.getElementById('recaptcha_token').value = token;
                            form.submit();
                        });
                    });
                });
            })();
        </script>
    <?php else: ?>
        <div class="g-recaptcha" data-sitekey="<?php echo e($recaptcha->siteKey()); ?>"></div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\completeEcom\resources\views/partials/recaptcha.blade.php ENDPATH**/ ?>