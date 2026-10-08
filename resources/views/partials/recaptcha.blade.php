@php $recaptcha = app(\App\Services\RecaptchaService::class); $ajax = $ajax ?? false; @endphp
@if($recaptcha->enabled())
    {{-- Google's reCAPTCHA script is ~500 KB of JS, and this partial sits in the footer
         newsletter form on every page — so it's loaded lazily: only once the form nears the
         viewport or gets focus/hover (v2), or at submit time at the latest (v3). Shared
         window.__loadRecaptcha so several forms on one page only ever inject it once. --}}
    <script>
        window.__loadRecaptcha = window.__loadRecaptcha || function (src) {
            return window.__recaptchaPromise = window.__recaptchaPromise || new Promise(function (resolve) {
                var s = document.createElement('script');
                s.src = src; s.async = true; s.onload = resolve;
                document.head.appendChild(s);
            });
        };
    </script>
    @if($recaptcha->version() === 'v3')
        <input type="hidden" name="recaptcha_token" id="recaptcha_token">
        <script>
            (function () {
                var form = document.currentScript.closest('form');
                if (!form) return;
                var src = 'https://www.google.com/recaptcha/api.js?render={{ $recaptcha->siteKey() }}';
                form.addEventListener('focusin', function () { window.__loadRecaptcha(src); }, { once: true });
                {{-- Default: once the token is fetched, call the real form.submit() — a normal
                     page navigation, same as before. ajax=true (an AJAX-submitted form, e.g.
                     the footer newsletter form) instead dispatches a custom event and leaves
                     the actual submitting to that form's own JS, so this never forces a real
                     navigation on a form that intentionally never wants one. --}}
                var ajaxMode = {{ $ajax ? 'true' : 'false' }};
                form.addEventListener('submit', function (e) {
                    if (document.getElementById('recaptcha_token').value) return; // already fetched
                    e.preventDefault();
                    window.__loadRecaptcha(src).then(function () {
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
                });
            })();
        </script>
    @else
        <div class="g-recaptcha" data-sitekey="{{ $recaptcha->siteKey() }}"></div>
        <script>
            (function () {
                var form = document.currentScript.closest('form');
                var load = function () { window.__loadRecaptcha('https://www.google.com/recaptcha/api.js'); };
                if (!form || !('IntersectionObserver' in window)) return load();
                ['focusin', 'pointerenter', 'touchstart'].forEach(function (ev) {
                    form.addEventListener(ev, load, { once: true, passive: true });
                });
                new IntersectionObserver(function (entries, observer) {
                    if (entries.some(function (e) { return e.isIntersecting; })) { load(); observer.disconnect(); }
                }, { rootMargin: '300px' }).observe(form);
            })();
        </script>
    @endif
@endif
