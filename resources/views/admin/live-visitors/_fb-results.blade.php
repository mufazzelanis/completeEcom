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

{{-- ── how well Meta can recognise these customers ── --}}
@if($match)
    @php [$gLabel, $gColor] = $match['grade']; $mk = \App\Models\FacebookConversionLog::MATCH_KEYS; @endphp
    <div class="fbx-mq">
        <div class="fbx-mq-score">
            <div class="fbx-ring" style="--m:{{ $gColor }}; --p:{{ $match['score'] * 10 }}"><b>{{ $match['score'] }}</b><small>/10</small></div>
            <div>
                <p class="fbx-mq-t">Customer match · <span style="color:{{ $gColor }}">{{ $gLabel }}</span></p>
                <p class="fbx-mq-s">How well Meta can recognise who did these {{ number_format($match['n']) }} events. Higher = Meta finds your buyers more often → better ad results.</p>
            </div>
        </div>
        <div class="fbx-mq-bars">
            @foreach(['fbp', 'external_id', 'fbc', 'contact', 'country'] as $k)
                @php
                    $pct = $k === 'contact' ? $match['contact'] : $match['cover'][$k];
                    [$kLabel, $kIcon] = $k === 'contact' ? ['Email / phone', '✉️'] : $mk[$k];
                    $hint = $k === 'contact' ? 'Sent once the customer logs in or orders — browsing guests don\'t have it yet.' : $mk[$k][3];
                    $barColor = $pct >= 80 ? '#16a34a' : ($pct >= 40 ? '#0ea5e9' : '#f59e0b');
                @endphp
                <div class="fbx-mq-bar" title="{{ $hint }}">
                    <span class="fbx-mq-l">{{ $kIcon }} {{ $kLabel }}</span>
                    <span class="fbx-mq-track"><span style="width:{{ max(2, $pct) }}%;background:{{ $barColor }}"></span></span>
                    <b>{{ $pct }}%</b>
                </div>
            @endforeach
        </div>
    </div>
@endif

{{-- ── events ── --}}
<div class="fbx-table" role="table" aria-label="Meta events">
    <div class="fbx-row fbx-head" role="row">
        <span>Time</span><span>Event</span><span>Product / page</span><span>Visitor</span><span class="fbx-r">Value</span><span>Event ID</span><span>Status</span>
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
                    <small class="fbx-sub">
                        {{-- where it happened: the main site or which landing page --}}
                        @if($log->landingPage)
                            <span class="fbx-tag" style="--t:#db2777" title="{{ $log->landingPage->title }}">🚀 {{ \Illuminate\Support\Str::limit($log->landingPage->title, 18) }}</span>
                        @else
                            <span class="fbx-tag" style="--t:#4f46e5">🏬 Main site</span>
                        @endif
                        @if($more)<span>+{{ $more }} more product{{ $more > 1 ? 's' : '' }}</span>@endif
                        @if($orderId && $title)<a href="{{ route('admin.orders.show', $orderId) }}" class="fbx-order">Order #{{ $orderId }}</a>@endif
                    </small>
                </span>
            </span>

            {{-- visitor: IP (click = only this visitor's events), how active, new / returning, network, place --}}
            @php
                $ip = $log->ip_address;
                $v = $ip ? ($visitors[$ip] ?? null) : null;
                $bot = $log->user_agent ? \App\Support\VisitorIntel::botFromUserAgent($log->user_agent) : null;
            @endphp
            <span class="fbx-vis">
                @if($ip)
                    <button type="button" class="fbx-ip" @click="q = @js($ip); apply()" title="Show only this visitor's events">{{ $ip }}</button>
                    <span class="fbx-vis-tags">
                        @if($v && $v['count'] > 1)
                            <span class="fbx-pill" style="{{ $v['count'] >= 20 ? 'background:#fff7ed;color:#c2410c' : 'background:#eef2ff;color:#4338ca' }}"
                                  title="{{ $v['count'] }} Meta events from this IP in this time range">{{ $v['count'] }}×</span>
                        @endif
                        @if($v)
                            <span class="fbx-pill" style="{{ $v['returning'] ? 'background:#f1f5f9;color:#475569' : 'background:#ecfdf5;color:#047857' }}"
                                  title="{{ $v['returning'] ? 'Visited the site before this time range' : 'First seen on the site in this time range' }}">{{ $v['returning'] ? 'Returning' : 'New' }}</span>
                        @endif
                        @if($bot)<span class="fbx-pill" style="background:#fef3c7;color:#b45309" title="{{ $log->user_agent }}">🤖 {{ \Illuminate\Support\Str::limit($bot['name'], 14) }}</span>@endif
                    </span>
                    @if($v && $v['network'])
                        <span class="fbx-net" style="{{ $v['hosting'] ? 'color:#2563eb' : '' }}" title="{{ $v['hosting'] ? 'Data centre / cloud server — not a home or mobile connection' : 'Internet provider' }}">{{ $v['hosting'] ? '☁️ ' : '' }}{{ $v['network'] }}</span>
                    @endif
                    <span class="fbx-loc">{{ $v && $v['location'] ? $v['flag'] . ' ' . $v['location'] : 'Locating…' }}</span>
                @else
                    <span class="fbx-muted text-xs">—</span>
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

            {{-- status + how well Meta can recognise this customer; the button opens the details --}}
            @php
                $keys = $log->matchKeyList();
                $mScore = $keys !== null ? \App\Models\FacebookConversionLog::scoreFor($keys) : null;
                [$mLabel, $mColor, $mBars] = $mScore !== null ? \App\Models\FacebookConversionLog::grade($mScore) : ['—', '#94a3b8', 0];
            @endphp
            <span class="fbx-stat">
                @if($ok)
                    <span class="fbx-status is-ok" title="Meta received it{{ isset($body['fbtrace_id']) ? ' · fbtrace ' . $body['fbtrace_id'] : '' }}">✓ Sent <i>{{ $log->http_status }}</i></span>
                @else
                    <span class="fbx-status is-bad" title="{{ $error }}">✕ Failed <i>{{ $log->http_status ?: '—' }}</i></span>
                @endif
                <button type="button" class="fbx-match" style="--m:{{ $mColor }}" @click="toggle({{ $log->id }})" :aria-expanded="!!opened[{{ $log->id }}]"
                        title="{{ $mScore !== null ? 'Customer match ' . $mScore . '/10 (' . $mLabel . ') — click for what Meta received' : 'Details' }}">
                    @if($mScore !== null)
                        <span class="fbx-sig" aria-hidden="true">@for($b = 1; $b <= 4; $b++)<i class="{{ $b <= $mBars ? 'on' : '' }}" style="height:{{ 3 + $b * 2.5 }}px"></i>@endfor</span>
                        <b>{{ $mLabel }}</b>
                    @else
                        <span>Details</span>
                    @endif
                    <svg :class="opened[{{ $log->id }}] && 'rot'" viewBox="0 0 20 20" fill="currentColor"><path d="M5.3 7.3a1 1 0 0 1 1.4 0L10 10.6l3.3-3.3a1 1 0 1 1 1.4 1.4l-4 4a1 1 0 0 1-1.4 0l-4-4a1 1 0 0 1 0-1.4z"/></svg>
                </button>
            </span>

            @if($error)<p class="fbx-err">⚠ {{ $error }}</p>@endif

            {{-- details: what Meta received to recognise the customer, in plain words --}}
            <div class="fbx-detail" x-show="opened[{{ $log->id }}]" x-cloak x-transition.opacity>
                @if($keys !== null)
                    <div class="fbx-d-head">
                        <div class="fbx-ring" style="--m:{{ $mColor }}; --p:{{ $mScore * 10 }}">
                            <b>{{ $mScore }}</b><small>/10</small>
                        </div>
                        <div class="min-w-0">
                            <p class="fbx-d-title">Customer match: <span style="color:{{ $mColor }}">{{ $mLabel }}</span></p>
                            <p class="fbx-d-text">
                                @if(array_intersect(['em', 'ph'], $keys))
                                    Meta got this customer's contact details — it can tie this {{ strtolower($label) }} to a real Facebook account and credit your ads.
                                @elseif(in_array('fbc', $keys))
                                    This visitor came from a Facebook ad — Meta can credit that ad. Email / phone are added once they order or log in.
                                @elseif(in_array('fbp', $keys))
                                    A guest browsing — Meta recognises them by the pixel's browser ID and this shop's visitor ID. Email / phone are added once they order or log in.
                                @else
                                    Only basic details went with this event (no pixel cookie — likely an ad blocker or a bot), so Meta has little to match it with.
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="fbx-keys">
                        @foreach(\App\Models\FacebookConversionLog::MATCH_KEYS as $k => [$kLabel, $kIcon, $kWeight, $kHelp])
                            @php $on = in_array($k, $keys, true); @endphp
                            <span class="fbx-key {{ $on ? 'on' : '' }}" title="{{ $kHelp }}">
                                <span class="fbx-key-i">{{ $on ? '✓' : '–' }}</span>{{ $kIcon }} {{ $kLabel }}
                            </span>
                        @endforeach
                    </div>
                    <p class="fbx-d-note">Hover any item to see what it is. Only <i>which</i> details were sent is kept here — the values themselves go to Meta encrypted (hashed) and are never stored.</p>
                @else
                    <p class="fbx-d-note">Sent before this shop recorded which customer details go with each event — newer events show them here.</p>
                @endif
                <dl class="fbx-d-tech">
                    <div><dt>Event ID</dt><dd><code>{{ $log->event_id }}</code></dd></div>
                    @if(isset($body['fbtrace_id']))<div><dt>Meta trace ID</dt><dd><code>{{ $body['fbtrace_id'] }}</code></dd></div>@endif
                    <div><dt>Pixel</dt><dd><code>{{ $log->pixel_id }}</code></dd></div>
                    @if($log->source_url)<div><dt>Page</dt><dd><a href="{{ $log->source_url }}" target="_blank" rel="noopener" class="fbx-link">{{ \Illuminate\Support\Str::limit(urldecode($log->source_url), 90) }}</a></dd></div>@endif
                    @if($log->user_agent)<div><dt>Browser</dt><dd>{{ \Illuminate\Support\Str::limit($log->user_agent, 120) }}</dd></div>@endif
                </dl>
            </div>
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
