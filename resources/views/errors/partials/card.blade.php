{{-- Product card on the not-found page. $p; $dup = the marquee's hidden second copy (not focusable). --}}
<a class="card" href="{{ route('products.show', $p->slug) }}" @if(!empty($dup)) tabindex="-1" @endif>
    <div class="img">
        @if($thumb($p))<img src="{{ $thumb($p) }}" alt="{{ empty($dup) ? $p->name : '' }}" loading="lazy" width="300" height="300">@endif
        @if($off($p))<span class="off">-{{ $off($p) }}%</span>@endif
        <span class="view">View product</span>
    </div>
    <div class="body">
        <span class="name">{{ $p->name }}</span>
        <span class="price"><b>{{ $money($finalPrice($p)) }}</b>@if($off($p))<s>{{ $money($p->price) }}</s>@endif</span>
    </div>
</a>
