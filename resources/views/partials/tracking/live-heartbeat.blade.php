{{-- Live-visitor heartbeat (Admin → Live Visitors): tells the server which page this browser is
     on — on every page change (Turbo or full load), every 20 s while the tab is visible, and
     right away when the visitor comes back to the tab. Nothing is sent from a hidden tab, so
     "live" means someone actually has the site open. The visitor id is a random value kept in
     this browser only. Tiny and fire-and-forget: a failed ping never affects the page. --}}
<script>
(function (w, d) {
    if (w.__mvLive) return;
    w.__mvLive = true;
    var key;
    try {
        key = localStorage.getItem('mv_vk');
        if (!key) { key = (w.crypto && crypto.randomUUID ? crypto.randomUUID() : (Date.now().toString(36) + Math.random().toString(36).slice(2, 12) + 'xxxxxxxx')).slice(0, 36); localStorage.setItem('mv_vk', key); }
    } catch (e) { key = 'nostore-' + Math.random().toString(36).slice(2, 18); }
    var firstRef = d.referrer || '';
    var ping = function () {
        if (d.visibilityState !== 'visible') return;
        var meta = d.querySelector('meta[name="csrf-token"]');
        try {
            fetch('{{ route('live.ping') }}', {
                method: 'POST', keepalive: true, credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': meta ? meta.content : @js(csrf_token()) },
                body: JSON.stringify({ vk: key, url: location.href, title: d.title, ref: firstRef })
            }).catch(function () {});
        } catch (e) {}
    };
    @if($turbo ?? false)
    d.addEventListener('turbo:load', ping); // fires on the first load too
    @else
    if (d.readyState === 'loading') d.addEventListener('DOMContentLoaded', ping); else ping();
    @endif
    d.addEventListener('visibilitychange', ping);
    setInterval(ping, 20000);
})(window, document);
</script>
