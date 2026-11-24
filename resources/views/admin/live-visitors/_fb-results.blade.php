{{-- Meta events stream — the part that refreshes (LiveFacebookEventsController::stream): the
     per-event cards (they double as the event filter) and the matching events. --}}
@php
    $fbxMeta = fn ($n) => \App\Http\Controllers\Admin\LiveFacebookEventsController::meta($n);
    $tz = setting('timezone', 'Asia/Dhaka');
    $today = now($tz)->toDateString();
    $ago = function ($at) {
        $s = (int) max(0, now()->diffInSeconds($at, true));
        return $s < 60 ? $s . 's ago' : ($s < 3600 ? intdiv($s, 60) . 'm ago' : ($s < 86400 ? intdiv($s, 3600) . 'h ago' : intdiv($s, 86400) . 'd ago'));
    };
@endphp

{{-- ── per event (click = filter) ── --}}
<div class="fbx-cards">
    <button type="button" @click="setEvent('')" class="fbx-card {{ $f['event'] === '' ? 'is-on' : '' }}" style="--c:#1877f2">
        <span class="fbx-card-l">⚡ All events</span>
        <span class="fbx-card-n">{{ number_format($all['sent'] + $all['failed']) }}</span>
        <span class="fbx-card-s">
            <span style="color:#16a34a">{{ number_format($all['sent']) }} sent</span>
            @if($all['failed'])· <span style="color:#dc2626">{{ number_format($all['failed']) }} failed</span>@endif
        </span>
    </button>
    @foreach($names as $name)
        @php [$label, $color, $icon] = $fbxMeta($name); $c = $byEvent[$name] ?? ['sent' => 0, 'failed' => 0, 'value' => 0]; @endphp
        <button type="button" @click="setEvent(@js($name))" class="fbx-card {{ $f['event'] === $name ? 'is-on' : '' }}" style="--c:{{ $color }}">
            <span class="fbx-card-l">{{ $icon }} {{ $label }}</span>
            <span class="fbx-card-n">{{ number_format($c['sent'] + $c['failed']) }}</span>
            <span class="fbx-card-s">
                @if($c['failed'])<span style="color:#dc2626">{{ $c['failed'] }} failed</span>
                @elseif($name === 'Purchase' && $c['value'])<span>৳{{ number_format($c['value']) }}</span>
                @else<span>{{ $c['sent'] ? 'all sent' : '—' }}</span>@endif
            </span>
        </button>
    @endforeach
</div>

{{-- ── events ── --}}
<div class="fbx-table" role="table" aria-label="Meta events">
    <div class="fbx-row fbx-head" role="row">
        <span>Time</span><span>Event</span><span>Product / page</span><span>Source</span><span class="fbx-r">Value</span><span>Event ID</span><span>Status</span>
    </div>

    @forelse($rows as $log)
        @php
            [$label, $color, $icon] = $fbxMeta($log->event_name);
            $ok = $log->status === 'sent';
            $at = $log->sent_at?->copy()->setTimezone($tz);
            $orderId = $log->event_name === 'Purchase' && preg_match('/^purchase_(\d+)$/', (string) $log->event_id, $m) ? (int) $m[1] : null;
            $body = json_decode((string) $log->response, true);
            $error = $ok ? null : ($body['error']['error_user_msg'] ?? $body['error']['message'] ?? \Illuminate\Support\Str::limit((string) $log->response, 160));
            $p = $log->product;
            $href = $p ? route('products.show', $p) : ($log->landingPage ? url('/' . $log->landingPage->slug) : $log->source_url);
            $title = $p?->name ?? $log->landingPage?->title;
            $more = $log->items > 1 ? $log->items - 1 : 0;
            $isNew = $f['after'] && $log->id > $f['after'];
        @endphp
        <div class="fbx-row {{ $ok ? '' : 'is-failed' }} {{ $isNew ? 'fbx-new' : '' }}" style="--c:{{ $color }}" role="row">
            {{-- time --}}
            <span class="fbx-time" title="{{ $at?->format('d M Y, h:i:s A') }}">
                <b>{{ $at?->format('h:i:s A') }}</b>
                <small>{{ $at && $at->toDateString() !== $today ? $at->format('d M') : ($log->sent_at ? $ago($log->sent_at) : '') }}</small>
            </span>

            {{-- event --}}
            <span><span class="fbx-badge">{{ $icon }} {{ $label }}</span></span>

            {{-- product / page --}}
            <span class="fbx-what">
                <a @if($href) href="{{ $href }}" target="_blank" rel="noopener" @endif class="fbx-thumb">
                    @if($p && $p->image)<img src="{{ image_thumb($p->image, 96) }}" alt="" loading="lazy">@else<span>{{ $icon }}</span>@endif
                </a>
                <span class="min-w-0">
                    @if($title)
                        <a href="{{ $href }}" target="_blank" rel="noopener" class="fbx-name" title="{{ $title }}">{{ $title }}</a>
                    @elseif($orderId)
                        <a href="{{ route('admin.orders.show', $orderId) }}" class="fbx-name">Order #{{ $orderId }}</a>
                    @elseif($href)
                        <a href="{{ $href }}" target="_blank" rel="noopener" class="fbx-name">{{ urldecode(parse_url($href, PHP_URL_PATH) ?: '/') }}</a>
                    @else
                        <span class="fbx-name fbx-muted">—</span>
                    @endif
                    <small>
                        @if($more)+{{ $more }} more product{{ $more > 1 ? 's' : '' }}@endif
                        @if($orderId && $title)<a href="{{ route('admin.orders.show', $orderId) }}" class="fbx-order">Order #{{ $orderId }}</a>@endif
                    </small>
                </span>
            </span>

            {{-- source --}}
            <span class="fbx-src">
                @if($log->landingPage)
                    <span class="fbx-tag" style="--t:#db2777" title="{{ $log->landingPage->title }}">🚀 {{ \Illuminate\Support\Str::limit($log->landingPage->title, 16) }}</span>
                @else
                    <span class="fbx-tag" style="--t:#4f46e5">🏬 Main site</span>
                @endif
            </span>

            {{-- value --}}
            <span class="fbx-r fbx-val">{{ $log->value ? '৳' . number_format((float) $log->value) : '—' }}</span>

            {{-- event id + copy --}}
            <span class="fbx-id">
                <code title="{{ $log->event_id }}" @click="copy(@js($log->event_id))">{{ \Illuminate\Support\Str::limit($log->event_id, 22, '…') }}</code>
                <button type="button" class="fbx-copy" @click="copy(@js($log->event_id))" :class="copied === @js($log->event_id) && 'is-done'"
                        title="Copy event ID" aria-label="Copy event ID {{ $log->event_id }}">
                    <svg x-show="copied !== @js($log->event_id)" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h10"/></svg>
                    <svg x-show="copied === @js($log->event_id)" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12l5 5L20 7"/></svg>
                </button>
            </span>

            {{-- status --}}
            <span>
                @if($ok)
                    <span class="fbx-status is-ok" title="Meta received it{{ isset($body['fbtrace_id']) ? ' · fbtrace ' . $body['fbtrace_id'] : '' }}">✓ Sent <i>{{ $log->http_status }}</i></span>
                @else
                    <span class="fbx-status is-bad" title="{{ $error }}">✕ Failed <i>{{ $log->http_status ?: '—' }}</i></span>
                @endif
            </span>

            @if($error)<p class="fbx-err">⚠ {{ $error }}</p>@endif
        </div>
    @empty
        <div class="fbx-empty">
            <div class="text-3xl mb-1">🔍</div>
            <p class="font-semibold">No events match these filters</p>
            <button type="button" class="fbx-link" @click="reset()">Clear filters</button>
        </div>
    @endforelse
</div>

@php $max = \App\Http\Controllers\Admin\LiveFacebookEventsController::MAX; $shown = $rows->count(); @endphp
<div class="fbx-foot">
    <span>Latest {{ number_format($shown) }} of {{ number_format($total) }}</span>
    <span class="flex flex-wrap items-center justify-end gap-2">
        @if($shown < $total && $f['limit'] < $max)
            <button type="button" class="fbx-more" @click="more()">Show {{ min(10, $total - $shown) }} more ↓</button>
        @endif
        @if($f['limit'] > \App\Http\Controllers\Admin\LiveFacebookEventsController::SHOW)
            <button type="button" class="fbx-more" style="color:#64748b;background:#64748b14" @click="less()">Show less ↑</button>
        @endif
        @if($shown < $total)
            {{-- the full history (event/status filters carried over) lives on the log page --}}
            <a href="{{ route('admin.facebook-conversion-logs.index', array_filter(['event_name' => $f['event'], 'status' => $f['status']])) }}" class="fbx-link">All {{ number_format($total) }} in logs →</a>
        @endif
    </span>
</div>
