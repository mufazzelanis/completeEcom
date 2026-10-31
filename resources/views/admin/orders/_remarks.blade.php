{{-- Order remarks card — quick view drawer and the full order page. Needs $order. The JS/CSS it
     uses live in _remarks-js (loaded once by the page, since the drawer's HTML arrives via x-html). --}}
@php $rt = \App\Support\OrderTimeline::for($order); @endphp
<div class="rm-card" x-data="orderRemarks(@js([
        'orderId' => $order->id,
        'count' => $rt['count'],
        'storeUrl' => route('admin.orders.remarks.store', $order->id),
    ]))">
    <div class="flex items-center justify-between gap-2 mb-2">
        <span class="rm-h">💬 Remarks &amp; history <span class="rm-count" x-text="count">{{ $rt['count'] }}</span></span>
        <span class="text-[11px] text-gray-400">Staff only · not shown to the customer</span>
    </div>

    {{-- composer --}}
    <div class="rm-compose">
        <div class="rm-types">
            @foreach(\App\Models\OrderRemark::TYPES as $key => [$label, $icon, $color])
                <button type="button" @click="setType('{{ $key }}')" :class="type === '{{ $key }}' && 'is-on'" style="--c: {{ $color }}">{{ $icon }} {{ $label }}</button>
            @endforeach
        </div>

        <textarea x-ref="body" x-model="body" rows="2" class="rm-input" :placeholder="placeholder"
                  @keydown.ctrl.enter.prevent="add()" @keydown.meta.enter.prevent="add()" maxlength="1000"></textarea>

        <div class="rm-quick" x-show="(templates[type] || []).length">
            <template x-for="t in (templates[type] || [])" :key="t">
                <button type="button" @click="body = body ? body + ' ' + t : t; $refs.body.focus()" x-text="t"></button>
            </template>
        </div>

        {{-- call: tap the result — it's saved straight away (with the text above, if any) --}}
        <div x-show="type === 'call'" x-cloak class="rm-outcomes">
            @foreach(\App\Models\OrderRemark::OUTCOMES as $key => [$label, $icon, $color])
                <button type="button" @click="add('{{ $key }}')" :disabled="busy" style="--c: {{ $color }}" :class="outcome === '{{ $key }}' && 'is-on'">{{ $icon }} {{ $label }}</button>
            @endforeach
        </div>
        <div x-show="outcome === 'call_later'" x-cloak class="rm-later">
            <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Call back at:</span>
            <button type="button" @click="later(1)">+1 hour</button>
            <button type="button" @click="later('19')">Today 7 PM</button>
            <button type="button" @click="later('t11')">Tomorrow 11 AM</button>
            <input type="datetime-local" x-model="followUp" class="rm-input" style="width:auto;padding:.3rem .5rem">
            <button type="button" class="rm-save" @click="add('call_later')" :disabled="busy || !followUp">Save</button>
        </div>

        <div class="flex items-center justify-between gap-2 mt-2" x-show="type !== 'call'">
            <span class="text-[11px] text-gray-400 hidden sm:inline">Ctrl + Enter to add</span>
            <button type="button" class="rm-save ml-auto" @click="add()" :disabled="busy || !body.trim()" x-text="busy ? 'Adding…' : 'Add remark'">Add remark</button>
        </div>
    </div>

    <div x-ref="list" class="rm-list">
        @include('admin.orders._remarks-list', ['order' => $order] + $rt)
    </div>
</div>
