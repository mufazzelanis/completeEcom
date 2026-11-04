{{-- Countdown to $target. Corrects for the visitor's clock using the server's time at
     render (skew), shows days only when there are some, and dispatches a bubbling
     `flash-ended` event when it reaches zero. $size: sm | lg.
     The first values are rendered server-side, so nothing jumps when Alpine takes over. --}}
@php
    $size = $size ?? 'sm';
    $left = max(0, $target->getTimestamp() - now()->getTimestamp());
    [$cd, $ch, $cm, $cs] = [intdiv($left, 86400), intdiv($left % 86400, 3600), intdiv($left % 3600, 60), $left % 60];
    $pad = fn ($n) => str_pad((string) $n, 2, '0', STR_PAD_LEFT);
@endphp
<div class="fs-cd fs-cd-{{ $size }}" role="timer" aria-live="off"
     x-data="{
        end: {{ $target->getTimestamp() * 1000 }},
        skew: {{ (int) round(microtime(true) * 1000) }} - Date.now(),
        d: {{ $cd }}, h: {{ $ch }}, m: {{ $cm }}, s: {{ $cs }}, t: null,
        init() { this.tick(); this.t = setInterval(() => this.tick(), 1000); },
        destroy() { clearInterval(this.t); },
        tick() {
            const left = Math.max(0, Math.floor((this.end - Date.now() - this.skew) / 1000));
            this.d = Math.floor(left / 86400);
            this.h = Math.floor(left % 86400 / 3600);
            this.m = Math.floor(left % 3600 / 60);
            this.s = left % 60;
            if (!left) { clearInterval(this.t); this.$dispatch('flash-ended'); }
        },
        pad(n) { return String(n).padStart(2, '0'); }
     }">
    <div class="fs-cd-unit" x-show="d > 0" @if(! $cd) style="display: none" @endif><span class="fs-cd-num" x-text="d">{{ $cd }}</span><span class="fs-cd-lbl">{{ $size === 'lg' ? 'Days' : 'Day' }}</span></div>
    <span class="fs-cd-sep" x-show="d > 0" @if(! $cd) style="display: none" @endif>:</span>
    <div class="fs-cd-unit"><span class="fs-cd-num" x-text="pad(h)">{{ $pad($ch) }}</span><span class="fs-cd-lbl">Hrs</span></div>
    <span class="fs-cd-sep">:</span>
    <div class="fs-cd-unit"><span class="fs-cd-num" x-text="pad(m)">{{ $pad($cm) }}</span><span class="fs-cd-lbl">Min</span></div>
    <span class="fs-cd-sep">:</span>
    <div class="fs-cd-unit"><span class="fs-cd-num" x-text="pad(s)">{{ $pad($cs) }}</span><span class="fs-cd-lbl">Sec</span></div>
</div>
