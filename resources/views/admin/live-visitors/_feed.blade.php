{{-- Admin → Live Visitors: everything that changes — re-rendered every few seconds by
     LiveVisitorController::feed and swapped in without reloading the page. Accent colours are
     inline hex (LiveVisitor::PAGE_TYPES) so they work on the light and dark theme alike. --}}
@php
    $money = fn ($v) => '৳' . number_format((float) $v);
    $ago = function ($from) {
        $s = max(0, now()->diffInSeconds($from, true));
        return $s < 60 ? ((int) $s) . 's' : ($s < 3600 ? intdiv((int) $s, 60) . 'm ' . ((int) $s % 60) . 's' : intdiv((int) $s, 3600) . 'h ' . intdiv((int) $s % 3600, 60) . 'm');
    };
    $maxChart = max(1, max($chart));
    $atCheckout = $live->where('page_type', 'checkout')->count();
    $inCart = $live->where('cart_count', '>', 0)->count();
@endphp

{{-- ── Hero ─────────────────────────────────────────────────────────────── --}}
<div class="relative overflow-hidden rounded-2xl p-6 sm:p-7 mb-6 text-white shadow-lg"
     style="background: linear-gradient(135deg, #4338ca 0%, #7c3aed 45%, #db2777 100%);">
    <div class="pointer-events-none absolute -top-16 -right-10 w-64 h-64 rounded-full" style="background: rgba(255,255,255,.12); filter: blur(10px);"></div>
    <div class="pointer-events-none absolute -bottom-20 left-1/3 w-72 h-72 rounded-full" style="background: rgba(255,255,255,.08); filter: blur(14px);"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-6">
        <div>
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-widest" style="color: rgba(255,255,255,.85);">
                <span class="relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full" style="background:#4ade80; opacity:.75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3" style="background:#22c55e"></span>
                </span>
                Live now
            </div>
            <div class="flex items-baseline gap-3 mt-1">
                <span class="lv-big font-extrabold leading-none tabular-nums">{{ $live->count() }}</span>
                <span class="text-lg" style="color: rgba(255,255,255,.85);">{{ \Illuminate\Support\Str::plural('visitor', $live->count()) }} on the site</span>
            </div>
            @if($teamLive && !$includeTeam)
                <p class="text-xs mt-2" style="color: rgba(255,255,255,.7);">+ {{ $teamLive }} of your team (not counted)</p>
            @endif
        </div>
        <div class="lv-stats text-center">
            @foreach([['Visits today', number_format($visitorsToday), '📈'], ['In carts', $money($cartValue), '🛒'], ['At checkout', $atCheckout, '💳']] as [$label, $value, $icon])
                <div class="rounded-xl px-4 py-3" style="background: rgba(255,255,255,.14); backdrop-filter: blur(6px);">
                    <p class="text-xl font-bold tabular-nums">{{ $value }}</p>
                    <p class="lv-xs uppercase tracking-wide" style="color: rgba(255,255,255,.8);">{{ $icon }} {{ $label }}</p>
                </div>
            @endforeach
        </div>
    </div>

    {{-- last 30 minutes, one bar per minute --}}
    <div class="relative mt-6">
        <div class="lv-bars">
            @foreach($chart as $i => $n)
                <div class="lv-bar" title="{{ 29 - $i }} min ago: {{ $n }}"
                     style="height: {{ max(4, round($n / $maxChart * 100)) }}%; background: {{ $i === 29 ? '#fef08a' : 'rgba(255,255,255,' . ($n ? '.55' : '.18') . ')' }};"></div>
            @endforeach
        </div>
        <div class="flex justify-between lv-xxs mt-1" style="color: rgba(255,255,255,.7);"><span>30 min ago</span><span>now</span></div>
    </div>
</div>

{{-- ── Where they are ───────────────────────────────────────────────────── --}}
@if($byType)
<div class="flex flex-wrap gap-2 mb-6">
    @foreach($byType as $type => $n)
        @php [$label, $color, $icon] = \App\Models\LiveVisitor::PAGE_TYPES[$type] ?? \App\Models\LiveVisitor::PAGE_TYPES['other']; @endphp
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold"
              style="background: {{ $color }}1f; color: {{ $color }}; box-shadow: inset 0 0 0 1px {{ $color }}55;">
            {{ $icon }} {{ $label }} <span class="ml-0.5 rounded-full px-1.5 text-xs text-white" style="background: {{ $color }};">{{ $n }}</span>
        </span>
    @endforeach
    @if($inCart)
        <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold" style="background:#f59e0b1f; color:#d97706; box-shadow: inset 0 0 0 1px #f59e0b55;">🛍️ {{ $inCart }} with items in cart</span>
    @endif
</div>
@endif

<div class="lv-grid">
    {{-- ── Visitors ─────────────────────────────────────────────────────── --}}
    <div class="lv-main space-y-3">
        @forelse($live as $v)
            @php
                [$label, $color, $icon] = $v->pageMeta();
                $geo = \App\Models\LiveVisitor::geo($v->ip_address);
                $p = $v->product;
                $price = $p ? (float) ($p->sale_price ?? $p->price) : null;
                $isCheckout = $v->page_type === 'checkout';
                $steps = collect($v->path ?? []);
            @endphp
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-4 transition"
                 style="box-shadow: {{ $isCheckout ? '0 0 0 2px #10b981, 0 8px 24px -8px #10b98166' : '0 1px 3px rgba(0,0,0,.08)' }}; border-left: 4px solid {{ $color }};"
                 x-data="{ open: !!(window.__lvOpen || {})[{{ $v->id }}] }">
                <div class="flex gap-4">
                    {{-- picture / page icon --}}
                    <a href="{{ $p ? route('products.show', $p) : $v->url }}" target="_blank" rel="noopener"
                       class="flex-shrink-0 w-16 h-16 rounded-xl overflow-hidden flex items-center justify-center"
                       style="background: {{ $color }}14; box-shadow: inset 0 0 0 1px {{ $color }}33;">
                        @if($p && $p->image)
                            <img src="{{ image_thumb($p->image, 200) }}" alt="" class="w-full h-full object-contain p-1" loading="lazy">
                        @else
                            <span class="text-2xl">{{ $icon }}</span>
                        @endif
                    </a>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 lv-xs font-bold uppercase tracking-wide" style="background: {{ $color }}; color: #fff;">{{ $icon }} {{ $label }}</span>
                            @if($isCheckout)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 lv-xs font-bold" style="background:#10b9811f; color:#059669;">
                                    <span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full" style="background:#34d399"></span><span class="relative inline-flex rounded-full h-2 w-2" style="background:#10b981"></span></span>
                                    about to order
                                </span>
                            @endif
                            <span class="text-xs text-gray-500 dark:text-gray-400">on this page {{ $ago($v->page_started_at ?? $v->last_seen_at) }}</span>
                        </div>
                        <a href="{{ $v->url }}" target="_blank" rel="noopener" class="block mt-1 font-semibold text-gray-900 dark:text-gray-100 hover:underline truncate" title="{{ $p?->name ?? $v->title }}">{{ $p?->name ?? $v->title }}</a>
                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1 mt-1 text-xs text-gray-500 dark:text-gray-400">
                            @if($price !== null)
                                <span class="font-bold text-sm" style="color: {{ $color }};">{{ $money($price) }}</span>
                                @if($p->sale_price && $p->sale_price < $p->price)<span class="line-through">{{ $money($p->price) }}</span>@endif
                            @endif
                            @if($v->cart_count > 0)
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 font-semibold" style="background:#f59e0b1f; color:#d97706;">🛒 {{ $v->cart_count }} · {{ $money($v->cart_total) }}</span>
                            @endif
                            @if($steps->count() > 1)
                                <button type="button" @click="open = !open; (window.__lvOpen = window.__lvOpen || {})[{{ $v->id }}] = open" class="hover:underline" style="color: {{ $color }};">
                                    <span x-text="open ? 'Hide path' : '{{ $steps->count() }} pages this visit'"></span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- who / where --}}
                    <div class="lv-who flex-shrink-0 text-right text-xs space-y-1">
                        <p class="font-semibold text-gray-800 dark:text-gray-200">
                            @if($v->user){{ $v->user->name }}@if($v->user->canAccessAdmin()) <span class="lv-xxs px-1 rounded" style="background:#64748b; color:#fff;">team</span>@endif
                            @else<span class="italic text-gray-500 dark:text-gray-400 font-normal">Guest</span>@endif
                        </p>
                        @if($v->user?->phone)<p class="font-mono text-gray-500 dark:text-gray-400">{{ $v->user->phone }}</p>@endif
                        <p class="text-gray-600 dark:text-gray-300">{{ \App\Models\LiveVisitor::flag($geo['cc'] ?? null) }} {{ collect([$geo['city'] ?? null, $geo['country'] ?? null])->filter()->implode(', ') ?: 'Locating…' }}</p>
                        <a href="{{ route('admin.activity-logs.index', ['ip' => $v->ip_address]) }}" class="font-mono text-gray-400 hover:text-indigo-500 hover:underline" title="This IP in the Activity Log">{{ $v->ip_address }}</a>
                        <p class="text-gray-500 dark:text-gray-400">{{ $v->device === 'Mobile' ? '📱' : ($v->device === 'Tablet' ? '📲' : '💻') }} {{ $v->device }}{{ $v->browser ? ' · ' . $v->browser : '' }}</p>
                        <p><span class="rounded-full px-2 py-0.5 font-semibold" style="background:#6366f11a; color:#6366f1;">{{ $v->source ?: 'Direct' }}</span> <span class="text-gray-400">· {{ $ago($v->first_seen_at ?? $v->last_seen_at) }} on site</span></p>
                    </div>
                </div>

                {{-- mobile: who / where in one line --}}
                <p class="lv-who-m mt-2 text-xs text-gray-500 dark:text-gray-400">
                    {{ $v->user?->name ?? 'Guest' }} · {{ \App\Models\LiveVisitor::flag($geo['cc'] ?? null) }} {{ $geo['city'] ?? '' }} · <span class="font-mono">{{ $v->ip_address }}</span> · {{ $v->device }} · {{ $v->source ?: 'Direct' }}
                </p>

                {{-- this visit's path --}}
                <ol x-show="open" x-cloak class="mt-3 pt-3 border-t border-gray-100 dark:border-gray-800 space-y-1.5">
                    @foreach($steps as $i => $step)
                        @php [$sLabel, $sColor, $sIcon] = \App\Models\LiveVisitor::PAGE_TYPES[$step['t'] ?? 'other'] ?? \App\Models\LiveVisitor::PAGE_TYPES['other']; @endphp
                        <li class="flex items-center gap-2 text-xs">
                            <span class="w-5 h-5 flex-shrink-0 rounded-full flex items-center justify-center lv-xxs font-bold text-white" style="background: {{ $sColor }};">{{ $i + 1 }}</span>
                            <span class="text-gray-400 tabular-nums w-12">{{ \Illuminate\Support\Carbon::parse($step['at'])->setTimezone(setting('timezone', 'Asia/Dhaka'))->format('H:i:s') }}</span>
                            <a href="{{ $step['url'] }}" target="_blank" rel="noopener" class="truncate text-gray-700 dark:text-gray-300 hover:underline">{{ $sIcon }} {{ $step['title'] }}</a>
                        </li>
                    @endforeach
                </ol>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-900 rounded-2xl p-12 text-center shadow-sm">
                <div class="text-5xl mb-3">🌙</div>
                <p class="font-semibold text-gray-800 dark:text-gray-200">Nobody on the site right now</p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Visitors appear here within seconds of opening any page.</p>
            </div>
        @endforelse
    </div>

    {{-- ── Side panels ──────────────────────────────────────────────────── --}}
    <div class="space-y-6">
        <div class="bg-white dark:bg-gray-900 rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">🔥 Hot right now</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-4">Products being looked at this minute</p>
            @forelse(array_slice($topProducts, 0, 8) as $tp)
                <a href="{{ $tp['url'] }}" target="_blank" rel="noopener" class="flex items-center gap-3 py-2 group">
                    <div class="w-11 h-11 flex-shrink-0 rounded-lg overflow-hidden bg-gray-50 dark:bg-gray-800">
                        @if($tp['image'])<img src="{{ $tp['image'] }}" alt="" class="w-full h-full object-contain" loading="lazy">@endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200 truncate group-hover:underline">{{ $tp['name'] }}</p>
                        <p class="text-xs font-bold" style="color:#6366f1;">{{ $money($tp['price']) }}</p>
                    </div>
                    <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-bold text-white" style="background: linear-gradient(135deg,#f97316,#db2777);">👀 {{ $tp['viewers'] }}</span>
                </a>
            @empty
                <p class="text-sm text-gray-400 py-4 text-center">No product pages open right now.</p>
            @endforelse
        </div>

        <div class="bg-white dark:bg-gray-900 rounded-2xl p-5 shadow-sm">
            <h3 class="font-bold text-gray-900 dark:text-gray-100">👋 Left in the last 10 minutes</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">Where they were when they left</p>
            @forelse($left->take(12) as $v)
                @php [$label, $color, $icon] = $v->pageMeta(); $geo = \App\Models\LiveVisitor::geo($v->ip_address); @endphp
                <div class="flex items-center gap-2 py-1.5 text-xs opacity-80">
                    <span class="w-2 h-2 rounded-full flex-shrink-0" style="background: {{ $color }};"></span>
                    <span class="truncate flex-1 text-gray-700 dark:text-gray-300" title="{{ $v->product?->name ?? $v->title }}">{{ $icon }} {{ $v->product?->name ?? $v->title }}</span>
                    @if($v->cart_count > 0)<span title="Left with items in cart" style="color:#d97706;">🛒{{ $money($v->cart_total) }}</span>@endif
                    <span class="text-gray-400 flex-shrink-0">{{ \App\Models\LiveVisitor::flag($geo['cc'] ?? null) }} {{ $ago($v->last_seen_at) }} ago</span>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-2 text-center">Nobody yet.</p>
            @endforelse
        </div>
    </div>
</div>
