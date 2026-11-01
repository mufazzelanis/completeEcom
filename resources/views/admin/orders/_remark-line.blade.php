{{-- Last remark on an order, under the customer — orders list + dashboard recent orders.
     Tap it to open the quick view at the remarks box. Re-drawn live by the drawer's
     "order-remark" event. Needs: $order with latestRemark(.user) and remarks_count. --}}
@php $lr = $order->latestRemark; $ll = $lr?->listLine(); @endphp
<div class="rl" x-data="{ n: {{ (int) $order->remarks_count }}, line: @js($ll) }"
     @order-remark.window="if ($event.detail.id === {{ $order->id }}) { n = $event.detail.count; line = $event.detail.line }">
    <button type="button" class="rl-box" x-show="line" @if(! $lr) style="display:none" @endif
            @click.stop="$dispatch('oq-open', { id: {{ $order->id }}, focus: 'remarks' })"
            :title="line ? line.tag + (line.body ? ' — ' + line.body : '') + '  ·  ' + line.who + ', ' + line.at : ''">
        <span class="rl-top">
            <span class="rl-tag" :style="line && ('background:' + line.color + '1a;color:' + line.color)" x-text="line?.tag">{{ $ll["tag"] ?? "" }}</span>
            <span class="rl-body" x-show="line?.body" x-text="line?.body">{{ $ll["body"] ?? "" }}</span>
        </span>
        <span class="rl-meta">
            <span x-text="line ? line.who + ' · ' + line.ago : ''"></span>
            <span class="rl-n" x-show="n > 1" x-text="'💬 ' + n"></span>
            <span class="rl-fu" x-show="line?.fu" :class="line?.late && 'is-late'" x-text="'⏰ ' + (line?.fu || '') + (line?.late ? ' — overdue' : '')"></span>
        </span>
    </button>
    @if($order->status === 'pending')
        <button type="button" class="rl-add" x-show="!line" @if($lr) style="display:none" @endif
                @click.stop="$dispatch('oq-open', { id: {{ $order->id }}, focus: 'remarks' })">＋ Add call note</button>
    @endif
</div>
