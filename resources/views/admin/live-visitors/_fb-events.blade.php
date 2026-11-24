{{-- Admin → Live Visitors: Facebook Conversions API events, refreshed with the rest of the feed
     every 5 s. Today's totals per event, then the latest sends — each tagged main site or the
     landing page it came from, a Purchase linked to its order. --}}
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
@endphp
<div class="bg-white dark:bg-gray-900 rounded-2xl p-5 shadow-sm">
    <div class="flex items-center justify-between gap-2">
        <h3 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <span class="inline-flex w-6 h-6 rounded-full items-center justify-center text-white text-xs font-black" style="background:#1877f2">f</span>
            Facebook events
        </h3>
        <a href="{{ route('admin.facebook-conversion-logs.index') }}" class="text-xs font-semibold hover:underline" style="color:#1877f2">All logs →</a>
    </div>

    @if(!$fb['on'] && $fb['recent']->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-3">Conversions API is off. Turn it on in
            <a href="{{ route('admin.settings.show', 'facebook_pixel') }}" class="underline" style="color:#1877f2">Settings → Facebook Pixel</a>.</p>
    @else
        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
            Sent to Meta from the server today
            @if($fb['failedToday'])
                · <b style="color:#dc2626">{{ $fb['failedToday'] }} failed</b>
            @else
                · <span style="color:#16a34a">all OK</span>
            @endif
        </p>

        {{-- today, per event --}}
        @if($fb['today'])
            <div class="grid grid-cols-2 gap-2 mb-4">
                @foreach($fb['today'] as $name => $c)
                    @php [$label, $color, $icon] = $meta($name); @endphp
                    <div class="rounded-xl px-3 py-2" style="background: {{ $color }}14; box-shadow: inset 0 0 0 1px {{ $color }}33;">
                        <p class="lv-xs font-semibold truncate" style="color: {{ $color }};">{{ $icon }} {{ $label }}</p>
                        <p class="text-lg font-extrabold tabular-nums text-gray-900 dark:text-gray-100 leading-tight">
                            {{ number_format($c['sent'] ?? 0) }}
                            @if(!empty($c['failed']))<span class="lv-xs font-bold" style="color:#dc2626;">+{{ $c['failed'] }} failed</span>@endif
                        </p>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-sm text-gray-400 mb-3">No events yet today.</p>
        @endif

        {{-- latest sends --}}
        <p class="lv-xs font-bold uppercase tracking-wide text-gray-400 mb-1">Latest</p>
        @forelse($fb['recent'] as $log)
            @php
                [$label, $color, $icon] = $meta($log->event_name);
                $ok = $log->status === 'sent';
                $orderId = $log->event_name === 'Purchase' && preg_match('/^purchase_(\d+)$/', (string) $log->event_id, $m) ? (int) $m[1] : null;
                $error = $ok ? null : (json_decode((string) $log->response, true)['error']['message'] ?? \Illuminate\Support\Str::limit((string) $log->response, 120));
            @endphp
            <div class="flex items-center gap-2 py-1.5 text-xs border-t border-gray-100 dark:border-gray-800 first:border-0" @if($error) title="{{ $error }}" @endif>
                <span class="w-2 h-2 rounded-full flex-shrink-0" style="background: {{ $ok ? '#22c55e' : '#ef4444' }};" aria-label="{{ $ok ? 'sent' : 'failed' }}"></span>
                <span class="font-semibold flex-shrink-0" style="color: {{ $color }};">{{ $icon }} {{ $label }}</span>
                <span class="truncate flex-1 text-gray-500 dark:text-gray-400">
                    @if($orderId)
                        <a href="{{ route('admin.orders.show', $orderId) }}" class="hover:underline">Order #{{ $orderId }}</a> ·
                    @endif
                    {{ $log->landingPage ? '🚀 ' . $log->landingPage->title : '🏬 Main site' }}
                    @unless($ok)<b style="color:#dc2626;"> · failed</b>@endunless
                </span>
                <span class="text-gray-400 flex-shrink-0 tabular-nums" title="{{ $log->sent_at?->copy()->setTimezone($tz)->format('d M, h:i:s A') }}">{{ $log->sent_at ? $since($log->sent_at) : '' }}</span>
            </div>
        @empty
            <p class="text-sm text-gray-400 py-2 text-center">No events sent yet.</p>
        @endforelse
    @endif
</div>
