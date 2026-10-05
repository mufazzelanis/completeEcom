@php $recaptcha = app(\App\Services\RecaptchaService::class); $ajax = $ajax ?? false; @endphp
@if($recaptcha->enabled())
    @if($recaptcha->version() === 'v3')
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        <script src="https://www.google.com/recaptcha/api.js?render={{ $recaptcha->siteKey() }}"></script>
        <script>
            (function () {
                var form = document.currentScript.closest('form');
                if (!form) return;
                {{-- Default: once the token is fetched, call the real form.submit() — a normal
                     page navigation, same as before. ajax=true (an AJAX-submitted form, e.g.
                     the footer newsletter form) instead dispatches a custom event and leaves
                     the actual submitting to that form's own JS, so this never forces a real
                     navigation on a form that intentionally never wants one. --}}
                var ajaxMode = {{ $ajax ? 'true' : 'false' }};
                form.addEventListener('submit', function (e) {
                    if (document.getElementById('recaptcha_token').value) return; // already fetched
                    e.preventDefault();
                    grecaptcha.ready(function () {
                        grecaptcha.execute('{{ $recaptcha->siteKey() }}', { action: 'submit' }).then(function (token) {
                            document.getElementById('recaptcha_token').value = token;
                            if (ajaxMode) {
                                form.dispatchEvent(new CustomEvent('recaptcha:ready'));
                            } else {
                                form.submit();
                            }
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
