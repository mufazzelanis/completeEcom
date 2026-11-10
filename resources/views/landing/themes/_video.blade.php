{{-- One video for the themed landing pages. YouTube / Facebook players weigh close to a
     megabyte of script, so the page shows a picture and a play button and loads the player
     only when it is tapped (then it starts playing right away). $url, $tall, $label. --}}
@php
    $embed = embed_video_url($url);
    $ytId = ($embed && preg_match('#youtube\.com/embed/([A-Za-z0-9_-]+)#', $embed['src'], $m)) ? $m[1] : null;
    $src = $embed && $embed['type'] === 'iframe' ? $embed['src'] . (str_contains($embed['src'], '?') ? '&' : '?') . 'autoplay=1&rel=0' : null;
@endphp
<div class="lt-video {{ $tall ? 'lt-video--tall' : '' }}" x-data="{ on: false }">
    @if($embed && $embed['type'] === 'video')
        <video src="{{ $embed['src'] }}" controls preload="none" playsinline></video>
    @elseif($src)
        <template x-if="on">
            <iframe :src="@js($src)" title="{{ $label ?: 'ভিডিও' }}" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
        </template>
        <button type="button" class="lt-play" x-show="!on" @click="on = true" aria-label="{{ $label ? $label . ' — ' : '' }}ভিডিও চালু করুন">
            @if($ytId)<img src="https://i.ytimg.com/vi/{{ $ytId }}/hqdefault.jpg" alt="" loading="lazy" decoding="async" width="480" height="360">@endif
            <span><svg fill="currentColor" viewBox="0 0 20 20"><path d="M6 4l10 6-10 6V4z"/></svg></span>
            @if($label)<em>{{ $label }}</em>@endif
        </button>
    @else
        <a href="{{ $url }}" target="_blank" rel="noopener" class="lt-play" aria-label="{{ $label ? $label . ' — ' : '' }}ভিডিও দেখুন">
            <span><svg fill="currentColor" viewBox="0 0 20 20"><path d="M6 4l10 6-10 6V4z"/></svg></span>
            @if($label)<em>{{ $label }}</em>@endif
        </a>
    @endif
</div>
