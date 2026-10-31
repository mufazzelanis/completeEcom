{{-- Remarks + recorded changes for one order (App\Support\OrderTimeline). Re-rendered by
     OrderRemarkController after every add / pin / delete. Needs: $order, $pinned, $feed. --}}
@php
    $tz = setting('timezone', 'Asia/Dhaka');
    $me = auth()->user();
    $when = fn ($t) => $t ? $t->timezone($tz)->format('d M, g:i A') . ' · ' . $t->diffForHumans(null, true, true) . ' ago' : '';
    $initials = fn ($n) => collect(preg_split('/\s+/', trim((string) $n)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: '•';
@endphp

{{-- newest 10 entries open, the rest folded away --}}
@foreach([$pinned, $feed->take(10), $feed->slice(10)] as $section => $items)
    @if($section === 2 && $items->isNotEmpty())<details class="rm-more"><summary>Show {{ $items->count() }} older</summary>@endif
    @foreach($items as $item)
        @php $r = $section === 0 ? $item : ($item->kind === 'remark' ? $item->remark : null); @endphp
        @if($r)
            @php
                [$tLabel, $tIcon, $tColor] = $r->meta();
                $o = $r->outcome ? (\App\Models\OrderRemark::OUTCOMES[$r->outcome] ?? null) : null;
                $overdue = $r->follow_up_at && $r->follow_up_at->isPast();
                $canDelete = $me && ($r->user_id === $me->id || $me->isAdmin());
            @endphp
            <div class="rm-item {{ $r->is_pinned ? 'is-pinned' : '' }}">
                <span class="rm-av" style="background:{{ $tColor }}">{{ $initials($r->user?->name ?? 'Staff') }}</span>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <b class="rm-who">{{ $r->user?->name ?? 'Staff' }}</b>
                        @if($o)
                            <span class="rm-tag" style="background:{{ $o[2] }}1a;color:{{ $o[2] }}">{{ $o[1] }} {{ $o[0] }}</span>
                        @else
                            <span class="rm-tag" style="background:{{ $tColor }}1a;color:{{ $tColor }}">{{ $tIcon }} {{ $tLabel }}</span>
                        @endif
                        @if($r->is_pinned)<span class="rm-tag" style="background:#fef3c7;color:#b45309">📌 Pinned</span>@endif
                        <span class="rm-time" title="{{ $r->created_at?->timezone($tz)->format('d M Y, g:i:s A') }}">{{ $when($r->created_at) }}</span>
                    </div>
                    @if($r->body)<p class="rm-body">{{ $r->body }}</p>@endif
                    @if($r->follow_up_at)
                        <p class="rm-follow {{ $overdue ? 'is-late' : '' }}">⏰ Call back {{ $r->follow_up_at->timezone($tz)->format('D d M, g:i A') }}{{ $overdue ? ' — overdue' : ' · in ' . $r->follow_up_at->diffForHumans(null, true, true) }}</p>
                    @endif
                </div>
                <div class="rm-acts">
                    <button type="button" @click="pin(@js(route('admin.orders.remarks.pin', $r->id)))" title="{{ $r->is_pinned ? 'Unpin' : 'Pin to the top' }}" aria-label="{{ $r->is_pinned ? 'Unpin' : 'Pin' }}">📌</button>
                    @if($canDelete)<button type="button" @click="remove(@js(route('admin.orders.remarks.destroy', $r->id)))" title="Delete" aria-label="Delete remark">🗑</button>@endif
                </div>
            </div>
        @elseif($section >= 1)
            <div class="rm-event">
                <span class="rm-ev-ic">{{ $item->icon }}</span>
                <span class="min-w-0 flex-1"><b>{{ $item->text }}</b> <span class="rm-time">· {{ $item->who }} · {{ $when($item->at) }}</span></span>
            </div>
        @endif
    @endforeach
    @if($section === 0 && $pinned->isNotEmpty())<div class="rm-sep"></div>@endif
    @if($section === 2 && $items->isNotEmpty())</details>@endif
@endforeach
