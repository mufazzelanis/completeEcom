{{--
    "Page not found" (404) — also 410 Gone (errors/410). Its own page, not the storefront layout:
    an address that matches no route never runs the web middleware (session, shared view data)
    the layout relies on, and a not-found page must never fail itself. Every database read is
    guarded — the worst case is a plainer page, never a 500.

    Built to turn a dead link into a click: live search with suggestions, "did you mean…" products
    from the words in the broken address, a mascot whose eyes follow the pointer with best sellers
    orbiting it (each one a link), "Surprise me", a live flash-sale countdown, category tiles and a
    trending-products strip. Status stays 404/410 and the page is noindex.
--}}
@php
    $gone = ($gone ?? false) || (isset($exception) && method_exists($exception, 'getStatusCode') && $exception->getStatusCode() === 410);
    $siteName = 'Mitavin';
    $primary = '#ea580c';
    $secondary = '#ec4899';
    $logo = null;
    $currency = '৳';
    $phone = '';
    $guess = collect();
    $popular = collect();
    $categories = collect();
    $flash = null;
    $favicon = null;
    $darkDefault = 'system';
    $fontHref = null;
    $fontStack = '"Inter"';

    try {
        $favicon = setting_file_url('favicon') ?: setting_file_url('site_logo');
        $darkDefault = (string) setting('dark_mode_default', 'system');
        // the storefront font (layouts/app does the same): a Google font unless it's a system stack
        $fontFamily = (string) setting('font_family', 'Inter, sans-serif');
        $fontName = trim(explode(',', $fontFamily)[0], " \t\"'");
        if ($fontName !== '' && preg_match('/^[A-Za-z0-9 ]+$/', $fontName)) {
            $fontStack = '"' . $fontName . '"';
            if (!str_contains($fontFamily, 'system-ui')) {
                $fontHref = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $fontName) . ':wght@400;600;700;800;900&display=swap';
            }
        }
    } catch (\Throwable $e) {
        // settings unavailable: keep the defaults
    }

    try {
        $siteName = setting('site_name', $siteName) ?: $siteName;
        $primary = preg_match('/^#[0-9a-f]{6}$/i', (string) setting('primary_color')) ? setting('primary_color') : $primary;
        $secondary = preg_match('/^#[0-9a-f]{6}$/i', (string) setting('secondary_color')) ? setting('secondary_color') : $secondary;
        $logo = setting_file_url('site_logo');
        $currency = setting('currency_symbol', '৳') ?: '৳';
        $phone = (string) (setting('contact_phone') ?: setting('company_phone') ?: setting('topbar_phone', ''));

        $cols = ['products.id', 'products.name', 'products.slug', 'products.price', 'products.sale_price', 'products.image'];

        // "did you mean": the shop's own search, fed with the words of the broken address
        $words = collect(preg_split('/[^a-z0-9]+/i', strtolower((string) basename(request()->path()))))
            ->filter(fn ($w) => strlen($w) >= 3 && !is_numeric($w) && !in_array($w, ['products', 'product', 'shop', 'category', 'brand', 'pages', 'html', 'php', 'www', 'the', 'and', 'with', 'for'], true))
            ->unique()->take(5)->implode(' ');
        if ($words !== '') {
            $guess = \App\Models\Product::active()->where('stock', '>', 0)->search($words, loose: true)->orderByRelevance($words)->limit(4)->get($cols);
            if ($guess->isEmpty()) {
                // a link full of noise words ("old", "link"…): any product sharing one of its words, best match first
                $list = explode(' ', $words);
                $guess = \App\Models\Product::active()->where('stock', '>', 0)
                    ->where(fn ($q) => collect($list)->each(fn ($w) => $q->orWhere('name', 'like', "%{$w}%")))
                    ->orderByDesc('views')->limit(30)->get($cols)
                    ->sortByDesc(fn ($p) => collect($list)->filter(fn ($w) => stripos($p->name, $w) !== false)->count())
                    ->take(4)->values();
            }
        }

        // Cached as plain arrays, never models: this site's cache refuses to unserialize objects
        // (config cache.serializable_classes), so a cached model would come back broken.
        $cache = fn (string $key, \Closure $fn) => collect(\Illuminate\Support\Facades\Cache::remember('notfound:v3:' . $key, 900, $fn))
            ->map(fn ($row) => (object) $row);
        $popular = $cache('popular', fn () => \App\Models\Product::active()->where('stock', '>', 0)->whereNotNull('image')
            ->orderByDesc('views')->limit(14)->get($cols)
            ->map(fn ($p) => $p->only(['id', 'name', 'slug', 'price', 'sale_price', 'image']))->all());
        $categories = $cache('categories', fn () => \App\Models\Category::active()->whereNull('parent_id')
            ->withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderByDesc('products_count')
            ->limit(8)->get(['id', 'name', 'slug', 'image'])->filter(fn ($c) => $c->products_count > 0)
            ->map(fn ($c) => $c->only(['id', 'name', 'slug', 'image', 'products_count']))->values()->all());
        $flash = $cache('flash', fn () => ($f = \App\Models\FlashSale::live()->orderBy('ends_at')->first())
            ? [['name' => $f->name, 'url' => $f->url, 'ends' => $f->ends_at->toIso8601String()]] : [])->first();
    } catch (\Throwable $e) {
        // a broken database never turns "page not found" into "server error"
    }

    $orbit = $popular->take(6)->values();
    $trending = $popular->reject(fn ($p) => $guess->contains('id', $p->id))->values();
    $finalPrice = fn ($p) => (float) ($p->sale_price && $p->sale_price < $p->price ? $p->sale_price : $p->price);
    $money = fn ($v) => $currency . number_format((float) $v);
    $off = fn ($p) => $p->sale_price && $p->sale_price < $p->price && $p->price > 0 ? (int) round(100 - $p->sale_price / $p->price * 100) : 0;
    $thumb = fn ($p, $w = 300) => $p->image ? (image_thumb($p->image, $w) ?? \Illuminate\Support\Facades\Storage::url($p->image)) : null;
    $home = url('/');
    $shop = url('/shop');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, follow">
    <title>{{ $gone ? 'This page is no longer available' : 'Page not found' }} | {{ $siteName }}</title>
    {{-- the same browser-tab icon, home-screen icon, app manifest and theme colour as every storefront page --}}
    @if($favicon)
        <link rel="icon" href="{{ $favicon }}">
        <link rel="apple-touch-icon" href="{{ $favicon }}">
    @endif
    <link rel="manifest" href="{{ url('/manifest.webmanifest') }}">
    <meta name="theme-color" content="{{ $primary }}">
    <meta name="application-name" content="{{ $siteName }}">
    {{-- the storefront's own font (Settings → Theme), loaded without blocking the first paint --}}
    @if($fontHref)
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link rel="stylesheet" href="{{ $fontHref }}" media="print" onload="this.media='all'; this.onload=null;">
    @endif
    <script>
        // Same light/dark choice as the rest of the store (the visitor's theme toggle, else the
        // admin's default, else the device setting) — applied before the first paint.
        (function () {
            var stored = null;
            try { stored = localStorage.getItem('site-theme'); } catch (e) {}
            var def = {{ Js::from($darkDefault) }};
            var dark = stored ? stored === 'dark' : (def === 'dark' ? true : def === 'light' ? false : window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
        })();
    </script>
    <style>
        :root {
            --p: {{ $primary }}; --s: {{ $secondary }};
            --bg: #f7f8fc; --bg2: #ffffff; --card: #ffffff; --ink: #0f172a; --muted: #64748b; --line: #e6e9f2;
            --glass: rgba(255, 255, 255, .7); --shadow: 0 24px 60px -28px rgba(15, 23, 42, .35);
            color-scheme: light;
        }
        html.dark { --bg: #070b14; --bg2: #0c1222; --card: #111a2e; --ink: #eef2ff; --muted: #94a3b8; --line: #1e293b; --glass: rgba(17, 26, 46, .72); --shadow: 0 24px 60px -24px rgba(0, 0, 0, .8); color-scheme: dark; }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; font-family: {!! $fontStack !!}, "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Hind Siliguri", sans-serif; background: var(--bg); color: var(--ink); overflow-x: hidden; -webkit-font-smoothing: antialiased; }
        a { color: inherit; text-decoration: none; }
        button { font: inherit; }
        .grad { background: linear-gradient(110deg, var(--p), var(--s), var(--p)); background-size: 200% auto; -webkit-background-clip: text; background-clip: text; color: transparent; animation: shine 5s linear infinite; }
        @keyframes shine { to { background-position: 200% center; } }

        /* background: brand aurora + faint grid + floating particles */
        .bg { position: fixed; inset: 0; z-index: 0; pointer-events: none; overflow: hidden; }
        .aurora { position: absolute; width: 60vmax; height: 60vmax; border-radius: 50%; filter: blur(90px); opacity: .22; animation: drift 22s ease-in-out infinite alternate; }
        .aurora.a { background: var(--p); top: -25vmax; left: -15vmax; }
        .aurora.b { background: var(--s); bottom: -30vmax; right: -20vmax; animation-delay: -8s; }
        @keyframes drift { to { transform: translate(8vmax, 6vmax) scale(1.15); } }
        .gridlines { position: absolute; inset: 0; background-image: linear-gradient(var(--line) 1px, transparent 1px), linear-gradient(90deg, var(--line) 1px, transparent 1px); background-size: 56px 56px; opacity: .35; mask-image: radial-gradient(ellipse at 50% 30%, #000 20%, transparent 70%); -webkit-mask-image: radial-gradient(ellipse at 50% 30%, #000 20%, transparent 70%); }
        .dot { position: absolute; width: 6px; height: 6px; border-radius: 50%; background: var(--p); opacity: .5; animation: rise 12s linear infinite; }
        @keyframes rise { from { transform: translateY(110vh); } to { transform: translateY(-10vh); } }

        .wrap { position: relative; z-index: 1; max-width: 1180px; margin: 0 auto; padding: max(16px, env(safe-area-inset-top)) 18px 40px; }

        /* top bar */
        .top { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 6px 0; }
        .brand { display: flex; align-items: center; font-weight: 800; font-size: 20px; }
        .brand img { height: 34px; width: auto; }
        .top nav { display: flex; gap: 6px; }
        .top nav a { padding: 8px 14px; border-radius: 999px; font-size: 14px; font-weight: 600; color: var(--muted); transition: .2s; }
        .top nav a:hover { color: var(--ink); background: var(--glass); }
        @media (max-width: 640px) { .top nav a:not(.keep) { display: none; } }

        /* hero */
        .hero { display: grid; grid-template-columns: 1.05fr .95fr; align-items: center; gap: 30px; padding: 26px 0 10px; }
        @media (max-width: 900px) { .hero { grid-template-columns: 1fr; gap: 6px; text-align: center; padding-top: 6px; } .hero .scene { order: -1; } }
        .badge { display: inline-flex; align-items: center; gap: 8px; padding: 7px 14px; border-radius: 999px; font-size: 13px; font-weight: 700; background: var(--glass); border: 1px solid var(--line); backdrop-filter: blur(8px); }
        .badge i { width: 8px; height: 8px; border-radius: 50%; background: var(--p); box-shadow: 0 0 0 0 var(--p); animation: ping 1.8s infinite; }
        @keyframes ping { 70% { box-shadow: 0 0 0 9px transparent; } }
        h1 { font-size: clamp(32px, 5.4vw, 58px); line-height: 1.05; letter-spacing: -.03em; margin: 16px 0 12px; font-weight: 900; }
        .lead { font-size: clamp(15px, 1.6vw, 18px); color: var(--muted); line-height: 1.6; max-width: 520px; margin: 0; }
        @media (max-width: 900px) { .lead { margin: 0 auto; } }

        /* search with live suggestions */
        .search { position: relative; margin-top: 24px; max-width: 540px; }
        @media (max-width: 900px) { .search { margin-left: auto; margin-right: auto; } }
        .search form { display: flex; align-items: center; gap: 6px; background: var(--card); border: 2px solid var(--line); border-radius: 18px; padding: 6px 6px 6px 16px; box-shadow: var(--shadow); transition: border-color .2s, box-shadow .2s; }
        .search form:focus-within { border-color: var(--p); box-shadow: 0 0 0 6px color-mix(in srgb, var(--p) 16%, transparent), var(--shadow); }
        .search svg { width: 20px; height: 20px; color: var(--muted); flex: none; }
        .search input { flex: 1; min-width: 0; border: 0; outline: 0; background: transparent; color: var(--ink); font: inherit; font-size: 16px; padding: 12px 4px; }
        .search button { border: 0; cursor: pointer; padding: 13px 22px; border-radius: 13px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--p), var(--s)); transition: transform .15s, filter .15s; }
        .search button:hover { filter: brightness(1.08); }
        .search button:active { transform: scale(.96); }
        .sugg { position: absolute; left: 0; right: 0; top: calc(100% + 8px); z-index: 20; background: var(--card); border: 1px solid var(--line); border-radius: 16px; box-shadow: var(--shadow); padding: 6px; display: none; text-align: left; max-height: 360px; overflow: auto; }
        .sugg.open { display: block; animation: fade .18s ease; }
        @keyframes fade { from { opacity: 0; transform: translateY(-6px); } }
        .sugg a { display: flex; align-items: center; gap: 12px; padding: 8px 10px; border-radius: 12px; font-size: 14px; }
        .sugg a:hover, .sugg a.on { background: color-mix(in srgb, var(--p) 10%, transparent); }
        .sugg img { width: 40px; height: 40px; object-fit: contain; border-radius: 8px; background: #fff; flex: none; }
        .sugg b { margin-left: auto; color: var(--p); white-space: nowrap; }
        .sugg .cat { color: var(--muted); }
        .hint { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 6px; font-size: 13px; color: var(--muted); align-items: center; }
        @media (max-width: 900px) { .hint { justify-content: center; } }
        .hint a { padding: 4px 10px; border-radius: 999px; border: 1px solid var(--line); background: var(--card); font-weight: 600; color: var(--ink); transition: .15s; }
        .hint a:hover { border-color: var(--p); color: var(--p); }

        /* buttons */
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 22px; }
        @media (max-width: 900px) { .actions { justify-content: center; } }
        .btn { position: relative; overflow: hidden; display: inline-flex; align-items: center; gap: 8px; padding: 13px 20px; border-radius: 14px; font-weight: 700; font-size: 15px; border: 1.5px solid var(--line); background: var(--card); cursor: pointer; color: var(--ink); transition: transform .18s, border-color .18s, box-shadow .18s; }
        .btn svg { width: 18px; height: 18px; }
        .btn:hover { transform: translateY(-3px); border-color: var(--p); box-shadow: 0 14px 26px -16px var(--p); }
        .btn:active { transform: scale(.96); }
        .btn.primary { color: #fff; border: 0; background: linear-gradient(135deg, var(--p), var(--s)); box-shadow: 0 16px 30px -14px var(--p); }
        .btn.primary::after { content: ""; position: absolute; inset: 0; background: linear-gradient(120deg, transparent 30%, rgba(255, 255, 255, .45), transparent 70%); transform: translateX(-120%); animation: sheen 3.2s ease-in-out infinite; }
        @keyframes sheen { 60%, 100% { transform: translateX(120%); } }
        @media (max-width: 480px) { .actions .btn { flex: 1 1 calc(50% - 10px); justify-content: center; padding: 13px 12px; } }

        .trust { display: flex; flex-wrap: wrap; gap: 8px 18px; margin-top: 22px; font-size: 13px; color: var(--muted); font-weight: 600; }
        @media (max-width: 900px) { .trust { justify-content: center; } }
        .trust span { display: inline-flex; align-items: center; gap: 6px; }
        .trust span::before { content: "✓"; display: grid; place-items: center; width: 18px; height: 18px; border-radius: 50%; font-size: 11px; color: #fff; background: var(--p); }

        /* the scene: big 404, mascot, orbiting best sellers */
        .scene { position: relative; aspect-ratio: 1; width: min(100%, 500px); margin: 0 auto; display: grid; place-items: center; }
        @media (max-width: 900px) { .scene { width: min(76vw, 330px); } }
        @media (max-width: 900px) and (max-height: 760px) { .scene { width: min(64vw, 270px); } }
        .big { position: absolute; font-size: clamp(110px, 21vw, 230px); font-weight: 900; letter-spacing: -.06em; line-height: 1; color: transparent; -webkit-text-stroke: 2px color-mix(in srgb, var(--p) 35%, transparent); user-select: none; animation: breathe 6s ease-in-out infinite; }
        @keyframes breathe { 50% { transform: scale(1.04); opacity: .7; } }
        .ring { position: absolute; inset: 4%; border-radius: 50%; border: 1.5px dashed color-mix(in srgb, var(--p) 30%, transparent); animation: spin 40s linear infinite; }
        .ring.paused, .scene:hover .ring { animation-play-state: paused; }
        .ring.paused .sat, .scene:hover .sat { animation-play-state: paused; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .sat { position: absolute; top: 50%; left: 50%; width: 22%; aspect-ratio: 1; margin: -11% 0 0 -11%; transform: rotate(var(--a)) translate(calc(var(--r) * 1px)) rotate(calc(-1 * var(--a))); }
        .sat a { display: grid; place-items: center; width: 100%; height: 100%; border-radius: 22px; background: #fff; box-shadow: 0 14px 30px -14px rgba(0, 0, 0, .45); padding: 10%; animation: unspin 40s linear infinite; transition: transform .2s; }
        .scene:hover .sat a { animation-play-state: paused; }
        .sat a:hover { transform: scale(1.18); }
        @keyframes unspin { to { transform: rotate(-360deg); } }
        .sat img { width: 100%; height: 100%; object-fit: contain; }
        .sat .tag { position: absolute; bottom: -10px; left: 50%; transform: translateX(-50%); white-space: nowrap; font-size: 11px; font-weight: 800; color: #fff; background: var(--p); padding: 3px 8px; border-radius: 999px; }

        .mascot { position: relative; width: 44%; cursor: pointer; animation: float 3.4s ease-in-out infinite; filter: drop-shadow(0 26px 30px color-mix(in srgb, var(--p) 45%, transparent)); -webkit-tap-highlight-color: transparent; }
        .mascot.jump { animation: jump .6s cubic-bezier(.3, 1.6, .5, 1); }
        @keyframes float { 50% { transform: translateY(-14px) rotate(-3deg); } }
        @keyframes jump { 40% { transform: translateY(-40px) scale(1.06) rotate(8deg); } }
        .mascot svg { width: 100%; height: auto; display: block; overflow: visible; }
        .pupil { transition: transform .08s linear; }
        .lid { transform-origin: center; transform-box: fill-box; animation: blink 4.5s infinite; }
        @keyframes blink { 0%, 93%, 100% { transform: scaleY(0); } 96% { transform: scaleY(1); } }
        .bubble { position: absolute; left: 50%; bottom: 100%; transform: translate(-50%, -6px) scale(.8); opacity: 0; white-space: nowrap; padding: 8px 14px; border-radius: 14px; font-size: 14px; font-weight: 700; background: var(--card); border: 1px solid var(--line); box-shadow: var(--shadow); transition: .25s cubic-bezier(.3, 1.5, .5, 1); pointer-events: none; }
        .bubble.show { opacity: 1; transform: translate(-50%, -14px) scale(1); }
        .floor { position: absolute; bottom: 12%; width: 30%; height: 14px; border-radius: 50%; background: color-mix(in srgb, var(--ink) 14%, transparent); filter: blur(2px); animation: shadow 3.4s ease-in-out infinite; }
        @keyframes shadow { 50% { transform: scaleX(.72); opacity: .5; } }

        /* flash sale strip */
        .flash { margin-top: 26px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 14px; padding: 16px 20px; border-radius: 20px; color: #fff; background: linear-gradient(120deg, var(--p), var(--s)); box-shadow: 0 22px 40px -24px var(--p); position: relative; overflow: hidden; }
        .flash::before { content: ""; position: absolute; inset: 0; background: repeating-linear-gradient(-45deg, rgba(255, 255, 255, .08) 0 14px, transparent 14px 28px); animation: slide 3s linear infinite; }
        @keyframes slide { to { background-position: 40px 0; } }
        .flash > * { position: relative; }
        .flash strong { font-size: 18px; }
        .clock { display: flex; gap: 6px; }
        .clock span { min-width: 52px; text-align: center; padding: 6px 8px; border-radius: 12px; background: rgba(0, 0, 0, .22); font-weight: 900; font-size: 20px; font-variant-numeric: tabular-nums; }
        .clock small { display: block; font-size: 10px; font-weight: 600; opacity: .8; }
        .flash .go { padding: 11px 18px; border-radius: 12px; background: #fff; color: #111; font-weight: 800; }

        /* sections */
        section { margin-top: 46px; }
        .head { display: flex; align-items: end; justify-content: space-between; gap: 12px; margin-bottom: 16px; }
        .head h2 { margin: 0; font-size: clamp(20px, 2.4vw, 26px); letter-spacing: -.02em; }
        .head p { margin: 4px 0 0; color: var(--muted); font-size: 14px; }
        .head a { color: var(--p); font-weight: 700; font-size: 14px; white-space: nowrap; }

        .cats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        @media (max-width: 900px) { .cats { grid-template-columns: repeat(2, 1fr); } }
        .cat { position: relative; display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 18px; background: var(--card); border: 1px solid var(--line); overflow: hidden; transition: transform .2s, border-color .2s, box-shadow .2s; }
        .cat:hover { transform: translateY(-4px); border-color: var(--p); box-shadow: 0 18px 30px -22px var(--p); }
        .cat .ic { width: 48px; height: 48px; flex: none; border-radius: 14px; display: grid; place-items: center; overflow: hidden; font-weight: 900; font-size: 20px; color: #fff; background: linear-gradient(135deg, var(--p), var(--s)); }
        .cat .ic img { width: 100%; height: 100%; object-fit: cover; background: #fff; }
        .cat b { display: block; font-size: 14px; line-height: 1.3; }
        .cat small { color: var(--muted); font-size: 12px; }
        .cat::after { content: "→"; position: absolute; right: 14px; color: var(--p); font-weight: 900; opacity: 0; transform: translateX(-6px); transition: .2s; }
        .cat:hover::after { opacity: 1; transform: none; }

        .grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        @media (max-width: 900px) { .grid { grid-template-columns: repeat(2, 1fr); gap: 10px; } }
        .card { position: relative; display: flex; flex-direction: column; background: var(--card); border: 1px solid var(--line); border-radius: 20px; overflow: hidden; transition: transform .25s, box-shadow .25s, border-color .25s; }
        .card:hover { transform: translateY(-6px); border-color: color-mix(in srgb, var(--p) 50%, var(--line)); box-shadow: 0 26px 40px -26px rgba(0, 0, 0, .5); }
        .card .img { position: relative; aspect-ratio: 1; background: #fff; display: grid; place-items: center; padding: 12px; overflow: hidden; }
        .card img { max-width: 100%; max-height: 100%; object-fit: contain; transition: transform .4s; }
        .card:hover img { transform: scale(1.08); }
        .card .off { position: absolute; top: 10px; left: 10px; padding: 4px 9px; border-radius: 999px; font-size: 12px; font-weight: 800; color: #fff; background: linear-gradient(135deg, var(--p), var(--s)); }
        .card .view { position: absolute; left: 12px; right: 12px; bottom: 12px; padding: 10px; border-radius: 12px; text-align: center; font-size: 13px; font-weight: 800; color: #fff; background: rgba(15, 23, 42, .88); opacity: 0; transform: translateY(10px); transition: .25s; }
        .card:hover .view { opacity: 1; transform: none; }
        .card .body { padding: 12px 14px 14px; display: flex; flex-direction: column; gap: 6px; flex: 1; }
        .card .name { font-size: 14px; line-height: 1.4; font-weight: 600; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card .price { margin-top: auto; display: flex; align-items: baseline; gap: 8px; }
        .card .price b { font-size: 17px; color: var(--p); }
        .card .price s { font-size: 13px; color: var(--muted); }
        @media (hover: none) { .card .view { display: none; } }

        /* trending: endless strip, pauses on hover, swipeable on phones */
        .strip { position: relative; overflow: hidden; mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); -webkit-mask-image: linear-gradient(90deg, transparent, #000 6%, #000 94%, transparent); }
        .track { display: flex; gap: 14px; width: max-content; animation: marquee 45s linear infinite; padding: 6px 0 14px; }
        .strip:hover .track { animation-play-state: paused; }
        @keyframes marquee { to { transform: translateX(-50%); } }
        .track .card { width: 210px; flex: none; }
        .dup { display: contents; }
        @media (max-width: 640px) { .strip { overflow-x: auto; scroll-snap-type: x mandatory; mask-image: none; -webkit-mask-image: none; scrollbar-width: none; } .strip::-webkit-scrollbar { display: none; } .track { animation: none; } .track .card { width: 46vw; scroll-snap-align: start; } .track .dup { display: none; } }

        .help { margin-top: 46px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; padding: 22px 24px; border-radius: 22px; background: var(--card); border: 1px solid var(--line); }
        .help h3 { margin: 0 0 4px; font-size: 18px; }
        .help p { margin: 0; color: var(--muted); font-size: 14px; }
        .help div:last-child { display: flex; gap: 10px; flex-wrap: wrap; }
        footer { margin-top: 34px; text-align: center; color: var(--muted); font-size: 13px; }

        .reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s ease, transform .7s cubic-bezier(.2, .9, .3, 1); }
        .reveal.in { opacity: 1; transform: none; }
        .fade-up { animation: up .8s cubic-bezier(.2, .9, .3, 1) both; }
        @keyframes up { from { opacity: 0; transform: translateY(26px); } }
        @media (prefers-reduced-motion: reduce) { *, *::before, *::after { animation: none !important; transition: none !important; } .reveal { opacity: 1; transform: none; } }
    </style>
</head>
<body>
<div class="bg" aria-hidden="true">
    <div class="aurora a"></div><div class="aurora b"></div><div class="gridlines"></div>
    @foreach([[8, 0, 14], [22, 4, 10], [41, 7, 16], [63, 2, 12], [78, 9, 15], [91, 5, 11]] as [$x, $d, $t])
        <i class="dot" style="left: {{ $x }}%; animation-delay: -{{ $d }}s; animation-duration: {{ $t }}s; {{ $loop->even ? 'background: ' . $secondary : '' }}"></i>
    @endforeach
</div>

<div class="wrap">
    <div class="top fade-up">
        <a href="{{ $home }}" class="brand" aria-label="{{ $siteName }} home">
            @if($logo)<img src="{{ $logo }}" alt="{{ $siteName }}">@else{{ $siteName }}@endif
        </a>
        <nav>
            <a href="{{ $shop }}">Shop</a>
            <a href="{{ url('/categories') }}">Categories</a>
            <a href="{{ url('/brands') }}">Brands</a>
            <a href="{{ url('/contact') }}" class="keep">Help</a>
        </nav>
    </div>

    <div class="hero">
        <div class="fade-up" style="animation-delay: .08s">
            <span class="badge"><i></i> {{ $gone ? 'Error 410 · Page removed' : 'Error 404 · Page not found' }}</span>
            <h1>{{ $gone ? 'This page has moved on.' : 'Oops! Page not found.' }}<br><span class="grad">Let's find what you need.</span></h1>
            <p class="lead">{{ $gone
                ? 'The product or page you opened has been removed. Search below, or explore our best sellers — something just as good is waiting.'
                : 'The link may be broken or the page has moved. Search below, or jump straight into our most-loved products.' }}</p>

            <div class="search" id="search">
                <form action="{{ $shop }}" method="GET" role="search" autocomplete="off">
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M20 20l-3.5-3.5"/></svg>
                    <input type="search" name="search" id="q" placeholder="Search products…" aria-label="Search products" aria-autocomplete="list" aria-controls="sugg">
                    <button type="submit">Search</button>
                </form>
                <div class="sugg" id="sugg" role="listbox"></div>
                @if($categories->isNotEmpty())
                    <div class="hint">Popular:
                        @foreach($categories->take(4) as $c)<a href="{{ route('shop.category', $c->slug) }}">{{ $c->name }}</a>@endforeach
                    </div>
                @endif
            </div>

            <div class="actions">
                <a href="{{ $home }}" class="btn primary">
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 11l9-7 9 7v9a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/></svg>
                    Back to Home
                </a>
                <a href="{{ $shop }}" class="btn">
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12l-1 13H7L6 7zm3 0a3 3 0 0 1 6 0"/></svg>
                    Shop All
                </a>
                @if($popular->isNotEmpty())
                <button type="button" class="btn" id="surprise" data-urls="{{ $popular->map(fn ($p) => route('products.show', $p->slug))->toJson() }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="4"/><circle cx="8.5" cy="8.5" r="1.3" fill="currentColor"/><circle cx="15.5" cy="15.5" r="1.3" fill="currentColor"/><circle cx="12" cy="12" r="1.3" fill="currentColor"/></svg>
                    Surprise Me
                </button>
                @endif
                <a href="{{ $home }}" class="btn" onclick="if (history.length > 1 && document.referrer.indexOf(location.host) !== -1) { history.back(); return false; }">
                    <svg fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Go Back
                </a>
            </div>

            <div class="trust"><span>Original products</span><span>Cash on delivery</span><span>Delivery all over Bangladesh</span><span>Easy returns</span></div>
        </div>

        <div class="scene fade-up" style="animation-delay: .2s" id="scene">
            <div class="big" aria-hidden="true">{{ $gone ? '410' : '404' }}</div>
            @if($orbit->isNotEmpty())
            <div class="ring" aria-label="Best sellers">
                @foreach($orbit as $i => $p)
                    <div class="sat" style="--a: {{ $i * (360 / $orbit->count()) }}deg; --r: 205">
                        <a href="{{ route('products.show', $p->slug) }}" title="{{ $p->name }} — {{ $money($finalPrice($p)) }}">
                            <img src="{{ $thumb($p, 200) }}" alt="{{ $p->name }}" width="120" height="120" loading="lazy">
                            @if($off($p))<span class="tag">-{{ $off($p) }}%</span>@endif
                        </a>
                    </div>
                @endforeach
            </div>
            @endif
            <div class="floor"></div>
            <div class="mascot" id="mascot" role="img" aria-label="A shopping bag looking for the page">
                <span class="bubble" id="bubble">Psst… try the search! 🔍</span>
                <svg viewBox="0 0 160 170" fill="none">
                    <defs>
                        <linearGradient id="bagG" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="{{ $primary }}"/><stop offset="1" stop-color="{{ $secondary }}"/></linearGradient>
                        <linearGradient id="shine" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity=".35"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
                    </defs>
                    <path d="M52 50c0-18 12-31 28-31s28 13 28 31" stroke="url(#bagG)" stroke-width="10" stroke-linecap="round"/>
                    <path d="M24 50h112l-9 98a12 12 0 0 1-12 11H45a12 12 0 0 1-12-11L24 50z" fill="url(#bagG)"/>
                    <path d="M30 56h40l-6 92H40z" fill="url(#shine)"/>
                    <g>
                        <circle cx="60" cy="92" r="15" fill="#fff"/><circle cx="100" cy="92" r="15" fill="#fff"/>
                        <circle class="pupil" cx="60" cy="92" r="7" fill="#0f172a"/><circle class="pupil" cx="100" cy="92" r="7" fill="#0f172a"/>
                        <rect class="lid" x="44" y="76" width="32" height="32" rx="16" fill="url(#bagG)"/>
                        <rect class="lid" x="84" y="76" width="32" height="32" rx="16" fill="url(#bagG)"/>
                    </g>
                    <ellipse cx="44" cy="118" rx="8" ry="5" fill="#fff" fill-opacity=".25"/><ellipse cx="116" cy="118" rx="8" ry="5" fill="#fff" fill-opacity=".25"/>
                    <path id="mouth" d="M68 128c8-7 16-7 24 0" stroke="#fff" stroke-width="5" stroke-linecap="round"/>
                </svg>
            </div>
        </div>
    </div>

    @if($flash)
        <a href="{{ $flash->url }}" class="flash reveal" data-ends="{{ $flash->ends }}">
            <div><strong>⚡ {{ $flash->name }}</strong><div style="font-size: 14px; opacity: .9">Flash sale is live — ends in</div></div>
            <div class="clock"><span data-u="d">00<small>days</small></span><span data-u="h">00<small>hrs</small></span><span data-u="m">00<small>min</small></span><span data-u="s">00<small>sec</small></span></div>
            <span class="go">Shop the sale →</span>
        </a>
    @endif

    @if($guess->isNotEmpty())
    <section class="reveal">
        <div class="head"><div><h2>🔍 Were you looking for one of these?</h2><p>Matched from the link you opened.</p></div></div>
        <div class="grid">
            @foreach($guess as $p)@include('errors.partials.card', ['p' => $p])@endforeach
        </div>
    </section>
    @endif

    @if($categories->isNotEmpty())
    <section class="reveal">
        <div class="head"><div><h2>Shop by category</h2><p>Jump straight to what you love.</p></div><a href="{{ url('/categories') }}">All categories →</a></div>
        <div class="cats">
            @foreach($categories as $c)
                <a class="cat" href="{{ route('shop.category', $c->slug) }}">
                    <span class="ic">@if($c->image)<img src="{{ image_thumb($c->image, 120) ?? \Illuminate\Support\Facades\Storage::url($c->image) }}" alt="" loading="lazy">@else{{ mb_substr($c->name, 0, 1) }}@endif</span>
                    <span><b>{{ $c->name }}</b><small>{{ $c->products_count }} products</small></span>
                </a>
            @endforeach
        </div>
    </section>
    @endif

    @if($trending->isNotEmpty())
    <section class="reveal">
        <div class="head"><div><h2>🔥 Trending right now</h2><p>What other shoppers are buying.</p></div><a href="{{ $shop }}">See all →</a></div>
        <div class="strip">
            <div class="track">
                @foreach($trending as $p)@include('errors.partials.card', ['p' => $p])@endforeach
                {{-- a second copy makes the scroll seamless; hidden from screen readers and phones --}}
                @foreach($trending as $p)<div class="dup" aria-hidden="true">@include('errors.partials.card', ['p' => $p, 'dup' => true])</div>@endforeach
            </div>
        </div>
    </section>
    @endif

    <div class="help reveal">
        <div><h3>Still can't find it?</h3><p>Tell us what you need — we'll help you find it or let you know when it's back.</p></div>
        <div>
            @if($phone)<a class="btn primary" href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">📞 Call {{ $phone }}</a>@endif
            <a class="btn" href="{{ url('/contact') }}">✉️ Contact us</a>
        </div>
    </div>

    <footer>© {{ date('Y') }} {{ $siteName }} · Original products · Cash on delivery · Nationwide delivery</footer>
</div>

<script>
(function () {
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // eyes follow the pointer (or a finger)
    var pupils = document.querySelectorAll('.pupil'), mascot = document.getElementById('mascot');
    function look(x, y) {
        pupils.forEach(function (p) {
            var r = p.getBoundingClientRect(), cx = r.left + r.width / 2, cy = r.top + r.height / 2;
            var a = Math.atan2(y - cy, x - cx), d = Math.min(6, Math.hypot(x - cx, y - cy) / 30);
            p.style.transform = 'translate(' + (Math.cos(a) * d).toFixed(2) + 'px,' + (Math.sin(a) * d).toFixed(2) + 'px)';
        });
    }
    if (!reduce) {
        addEventListener('pointermove', function (e) { look(e.clientX, e.clientY); }, { passive: true });
        // nobody pointing (phones): it looks around by itself
        var t = 0; setInterval(function () { if (matchMedia('(hover: none)').matches) { t += 1; var r = mascot.getBoundingClientRect(); look(r.left + r.width / 2 + Math.cos(t) * 200, r.top + Math.sin(t * 1.3) * 120 + 100); } }, 1400);
    }

    // tap the bag: it jumps and says something useful
    var lines = ['Psst… try the search! 🔍', 'Our best sellers are spinning around me ✨', 'Tap a product, it\'s right there! 👉', 'Feeling lucky? Hit "Surprise Me" 🎲', 'Cash on delivery, all over Bangladesh 🚚'];
    var bubble = document.getElementById('bubble'), n = 0, hideT;
    function say(text) { bubble.textContent = text; bubble.classList.add('show'); clearTimeout(hideT); hideT = setTimeout(function () { bubble.classList.remove('show'); }, 2600); }
    mascot.addEventListener('click', function () {
        mascot.classList.remove('jump'); void mascot.offsetWidth; mascot.classList.add('jump');
        say(lines[n++ % lines.length]);
    });
    mascot.addEventListener('animationend', function (e) { if (e.animationName === 'jump') mascot.classList.remove('jump'); });
    setTimeout(function () { say(lines[0]); }, 1200);

    // Surprise Me → a random best seller
    var s = document.getElementById('surprise');
    if (s) s.addEventListener('click', function () { var u = JSON.parse(s.dataset.urls); location.href = u[Math.floor(Math.random() * u.length)]; });

    // orbit radius follows the scene size
    var scene = document.getElementById('scene');
    function size() { var w = scene.getBoundingClientRect().width; document.querySelectorAll('.sat').forEach(function (el) { el.style.setProperty('--r', Math.round(w * .41)); }); }
    size(); addEventListener('resize', size);
    // on touch screens the orbit pauses while a finger is on it, so a product can be tapped
    var ring = document.querySelector('.ring');
    if (ring) { ring.addEventListener('touchstart', function () { ring.classList.add('paused'); }, { passive: true }); ring.addEventListener('touchend', function () { setTimeout(function () { ring.classList.remove('paused'); }, 1500); }); }

    // live search suggestions (the shop's own /search/suggest)
    var q = document.getElementById('q'), box = document.getElementById('sugg'), timer, ctl, sel = -1;
    var esc = function (s) { return String(s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
    q.addEventListener('input', function () {
        clearTimeout(timer); sel = -1;
        var v = q.value.trim();
        if (v.length < 2) { box.classList.remove('open'); return; }
        timer = setTimeout(function () {
            if (ctl) ctl.abort(); ctl = new AbortController();
            fetch('{{ url('/search/suggest') }}?q=' + encodeURIComponent(v), { headers: { Accept: 'application/json' }, signal: ctl.signal })
                .then(function (r) { return r.json(); }).then(function (d) {
                    var html = (d.products || []).map(function (p) { return '<a href="' + esc(p.url) + '" role="option">' + (p.image ? '<img src="' + esc(p.image) + '" alt="">' : '') + '<span>' + esc(p.name) + '</span><b>' + esc(p.price) + '</b></a>'; }).join('')
                        + (d.categories || []).map(function (c) { return '<a href="' + esc(c.url) + '" class="cat" role="option">📂 <span>' + esc(c.name) + '</span></a>'; }).join('');
                    box.innerHTML = html || '<a href="{{ $shop }}?search=' + encodeURIComponent(v) + '">Search all products for “' + esc(v) + '” →</a>';
                    box.classList.add('open');
                }).catch(function () {});
        }, 180);
    });
    q.addEventListener('keydown', function (e) {
        var items = box.querySelectorAll('a'); if (!box.classList.contains('open') || !items.length) return;
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); sel = (sel + (e.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length; items.forEach(function (a, i) { a.classList.toggle('on', i === sel); }); }
        else if (e.key === 'Enter' && sel >= 0) { e.preventDefault(); location.href = items[sel].href; }
        else if (e.key === 'Escape') box.classList.remove('open');
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('#search')) box.classList.remove('open'); });

    // typing placeholder with real product names
    var names = {!! json_encode($popular->take(6)->map(fn ($p) => \Illuminate\Support\Str::limit($p->name, 32, ''))->values()) !!};
    if (names.length && !reduce) {
        var i = 0, j = 0, back = false;
        (function type() {
            if (document.activeElement === q || q.value) { setTimeout(type, 1200); return; }
            var w = names[i % names.length];
            q.placeholder = 'Try "' + w.slice(0, j) + '"';
            if (!back && j < w.length) j++; else if (!back) { back = true; return setTimeout(type, 1600); }
            else if (j > 0) j--; else { back = false; i++; }
            setTimeout(type, back ? 30 : 70);
        })();
    }

    // sections fade in as they scroll into view
    var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (es) { es.forEach(function (e) { if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); } }); }, { rootMargin: '0px 0px -8% 0px' }) : null;
    document.querySelectorAll('.reveal').forEach(function (el) { io ? io.observe(el) : el.classList.add('in'); });

    // flash sale countdown
    var f = document.querySelector('.flash[data-ends]');
    if (f) {
        var end = new Date(f.dataset.ends).getTime();
        var tick = function () {
            var s = Math.max(0, Math.floor((end - Date.now()) / 1000));
            var v = { d: Math.floor(s / 86400), h: Math.floor(s % 86400 / 3600), m: Math.floor(s % 3600 / 60), s: s % 60 };
            f.querySelectorAll('[data-u]').forEach(function (el) { el.firstChild.nodeValue = String(v[el.dataset.u]).padStart(2, '0'); });
        };
        tick(); setInterval(tick, 1000);
    }
})();
</script>
</body>
</html>
