{{-- Admin → Live Visitors: Facebook Conversions API events, refreshed with the rest of the feed
     every 5 s. Sits in the wide left column under the visitors: today's totals per event, then
     the latest sends — each with its product (photo, name, price → opens the product page),
     main site or the landing page it came from, and a Purchase linked to its order. --}}
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $fbMeta = [
        'PageView' => ['Page view', '#64748b', '📄'],
        'ViewContent' => ['Viewed product', '#0ea5e9', '👀'],
        'AddToCart' => ['Add to cart', '#f59e0b', '🛒'],
        'InitiateCheckout' => ['Checkout', '#8b5cf6', '💳'],
        'Purchase' => ['Purchase', '#16a34a', '✅'],
    ];
    $meta = fn ($name) => $fbMeta[$name] ?? [$name, '#6366f1', '⚡'];
    $since = function ($at) {
        $s = (int) max(0, now()->diffInSeconds($at, true));
        return $s < 60 ? $s . 's ago' : ($s < 3600 ? intdiv($s, 60) . 'm ago' : ($s < 86400 ? intdiv($s, 3600) . 'h ago' : intdiv($s, 86400) . 'd ago'));
    };
    // every event type always gets its card (0 until it happens), so the row never jumps around
    $cards = collect(['ViewContent', 'AddToCart', 'InitiateCheckout', 'Purchase'])
        ->merge(array_keys($fb['today']))->unique()->values();
@endphp
<div class="bg-white dark:bg-gray-900 rounded-2xl p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="min-w-0">
            <h3 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <span class="inline-flex w-6 h-6 rounded-full items-center justify-center text-white text-xs font-black" style="background:#1877f2">f</span>
                Facebook events
                <span class="hidden sm:inline text-xs font-normal text-gray-500 dark:text-gray-400">· sent to Meta from the server today</span>
            </h3>
        </div>
        <div class="flex items-center gap-3 text-xs">
            @if($fb['on'] || $fb['recent']->isNotEmpty())
                @if($fb['failedToday'])
                    <span class="rounded-full px-2 py-0.5 font-bold" style="background:#fef2f2;color:#dc2626">{{ $fb['failedToday'] }} failed today</span>
                @else
                    <span class="rounded-full px-2 py-0.5 font-bold" style="background:#22c55e1f;color:#16a34a">● all OK</span>
                @endif
            @endif
            <a href="{{ route('admin.facebook-conversion-logs.index') }}" class="font-semibold hover:underline" style="color:#1877f2">All logs →</a>
        </div>
    </div>

    @if(!$fb['on'] && $fb['recent']->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">Conversions API is off. Turn it on in
            <a href="{{ route('admin.settings.show', 'facebook_pixel') }}" class="underline" style="color:#1877f2">Settings → Facebook Pixel</a>.</p>
    @else
        {{-- today, per event --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-4 mb-4">
            @foreach($cards as $name)
                @php [$label, $color, $icon] = $meta($name); $c = $fb['today'][$name] ?? []; @endphp
                <div class="rounded-xl px-3 py-2.5" style="background: {{ $color }}14; box-shadow: inset 0 0 0 1px {{ $color }}33;">
                    <p class="lv-xs font-semibold truncate" style="color: {{ $color }};">{{ $icon }} {{ $label }}</p>
                    <p class="text-xl font-extrabold tabular-nums text-gray-900 dark:text-gray-100 leading-tight mt-0.5">
                        {{ number_format($c['sent'] ?? 0) }}
                        @if(!empty($c['failed']))<span class="lv-xs font-bold" style="color:#dc2626;">+{{ $c['failed'] }} failed</span>@endif
                    </p>
                </div>
            @endforeach
        </div>

        {{-- latest sends --}}
        <p class="lv-xs font-bold uppercase tracking-wide text-gray-400 mb-1">Latest events</p>
        <div>
            @forelse($fb['recent'] as $log)
                @php
                    [$label, $color, $icon] = $meta($log->event_name);
                    $ok = $log->status === 'sent';
                    $orderId = $log->event_name === 'Purchase' && preg_match('/^purchase_(\d+)$/', (string) $log->event_id, $m) ? (int) $m[1] : null;
                    $error = $ok ? null : (json_decode((string) $log->response, true)['error']['message'] ?? \Illuminate\Support\Str::limit((string) $log->response, 120));
                    $p = $log->product;
                    // where a click goes: the product, else the landing page, else the page the event happened on
                    $href = $p ? route('products.show', $p) : ($log->landingPage ? url('/' . $log->landingPage->slug) : $log->source_url);
                    $title = $p?->name ?? $log->landingPage?->title;
                    $more = $log->items > 1 ? $log->items - 1 : 0;
                @endphp
                <div class="flex items-center gap-3 py-2 border-t border-gray-100 dark:border-gray-800 first:border-0" @if($error) title="{{ $error }}" @endif>
                    {{-- product photo (or the event's icon) --}}
                    <a @if($href) href="{{ $href }}" target="_blank" rel="noopener" @endif
                       class="relative flex-shrink-0 w-10 h-10 rounded-lg overflow-hidden flex items-center justify-center"
                       style="background: {{ $color }}14; box-shadow: inset 0 0 0 1px {{ $color }}33;">
                        @if($p && $p->image)
                            <img src="{{ image_thumb($p->image, 96) }}" alt="" class="w-full h-full object-contain p-0.5" loading="lazy">
                        @else
                            <span>{{ $icon }}</span>
                        @endif
                        <span class="absolute bottom-0.5 right-0.5 w-2 h-2 rounded-full" style="background: {{ $ok ? '#22c55e' : '#ef4444' }}; box-shadow: 0 0 0 2px #fff;" aria-label="{{ $ok ? 'sent' : 'failed' }}"></span>
                    </a>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="font-bold flex-shrink-0" style="color: {{ $color }};">{{ $icon }} {{ $label }}</span>
                            @unless($ok)<span class="font-bold" style="color:#dc2626;">failed</span>@endunless
                            @if($orderId)
                                <a href="{{ route('admin.orders.show', $orderId) }}" class="rounded px-1.5 font-semibold hover:underline" style="background:#16a34a1a;color:#16a34a">Order #{{ $orderId }}</a>
                            @endif
                        </div>
                        @if($title)
                            <a href="{{ $href }}" target="_blank" rel="noopener" class="block truncate text-sm font-medium text-gray-800 dark:text-gray-200 hover:underline" title="{{ $title }}">{{ $title }}@if($more)<span class="text-gray-400 font-normal"> +{{ $more }} more</span>@endif</a>
                        @elseif($href)
                            <a href="{{ $href }}" target="_blank" rel="noopener" class="block truncate text-sm text-gray-600 dark:text-gray-300 hover:underline">{{ urldecode(parse_url($href, PHP_URL_PATH) ?: '/') }}</a>
                        @else
                            <span class="block text-sm text-gray-400">—</span>
                        @endif
                    </div>

                    <div class="flex-shrink-0 text-right text-xs">
                        @if($log->value)<p class="font-bold text-gray-800 dark:text-gray-200 tabular-nums">৳{{ number_format((float) $log->value) }}</p>@endif
                        <p class="text-gray-400 whitespace-nowrap">
                            <span class="hidden sm:inline">{{ $log->landingPage ? '🚀 ' . \Illuminate\Support\Str::limit($log->landingPage->title, 18) : '🏬 Main site' }} ·</span>
                            <span class="tabular-nums" title="{{ $log->sent_at?->copy()->setTimezone($tz)->format('d M, h:i:s A') }}">{{ $log->sent_at ? $since($log->sent_at) : '' }}</span>
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-400 py-2 text-center">No events sent yet.</p>
            @endforelse
        </div>
    @endif
</div>
