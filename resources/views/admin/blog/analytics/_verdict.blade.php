{{-- Who a visit really was — $v: App\Support\VisitorIntel::verdict() --}}
@php
    $style = match (true) {
        $v['type'] === 'human'   => 'background:#ecfdf5;color:#047857',
        $v['kind'] === 'fake'    => 'background:#fef2f2;color:#b91c1c',
        $v['type'] === 'cloud'   => 'background:#eff6ff;color:#1d4ed8',
        $v['type'] === 'unknown' => 'background:#f3f4f6;color:#4b5563',
        default                  => 'background:#fff7ed;color:#c2410c',
    };
    $kind = $v['kind'] ? (\App\Support\VisitorIntel::KINDS[$v['kind']] ?? null) : null;
@endphp
<span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold whitespace-nowrap" style="{{ $style }}"
      title="{{ collect([$kind, $v['note']])->filter()->implode(' — ') }}">
    <span aria-hidden="true">{{ \App\Support\VisitorIntel::icon($v) }}</span>{{ $v['name'] }}
    @if($v['verified'] === true)<span title="Its IP really belongs to this company">✓</span>@endif
</span>
@if(!empty($showKind) && $kind && $v['type'] !== 'cloud')
    <span class="block text-[10px] text-gray-400 mt-0.5">{{ $kind }}</span>
@endif
@if(!empty($showNote) && $v['note'])
    <span class="block text-[10px] mt-0.5 {{ $v['kind'] === 'fake' ? 'text-red-600' : 'text-amber-600' }}">{{ $v['note'] }}</span>
@endif
