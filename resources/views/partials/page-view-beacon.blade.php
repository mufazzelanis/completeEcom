{{-- This render was a Turbo hover-prefetch: the view is recorded now that the page is shown
     (App\Support\PageView). Removes itself so a Back-button snapshot doesn't carry it. --}}
@if(!empty($pageViewToken))
<script>
    (function (s) {
        fetch(@js(route('page-view')), { method: 'POST', keepalive: true, credentials: 'same-origin', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ t: @js($pageViewToken) }) }).catch(function () {});
        s && s.remove();
    })(document.currentScript);
</script>
@endif
