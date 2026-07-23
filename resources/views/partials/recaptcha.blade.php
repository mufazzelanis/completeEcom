@php $recaptcha = app(\App\Services\RecaptchaService::class); @endphp
@if($recaptcha->enabled())
    @if($recaptcha->version() === 'v3')
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha->siteKey() }}"></script>
        <script>
            (function () {
                var form = document.currentScript.closest('form');
                if (!form) return;
                form.addEventListener('submit', function (e) {
                    if (document.getElementById('recaptcha_token').value) return; // already fetched
                    e.preventDefault();
                    grecaptcha.ready(function () {
                        grecaptcha.execute('{{ $recaptcha->siteKey() }}', { action: 'submit' }).then(function (token) {
                            document.getElementById('recaptcha_token').value = token;
                            form.submit();
                        });
                    });
                });
            })();
        </script>
    @else
        <div class="g-recaptcha" data-sitekey="{{ $recaptcha->siteKey() }}"></div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    @endif
@endif
