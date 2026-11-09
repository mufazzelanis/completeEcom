{{-- Last block of every document, held at the foot of the page by $push (see InvoicePdf::render).
     Expects $boxes ([title, body] pairs, nulls allowed), $thanks and $qr. --}}
@php
    $boxes = array_values(array_filter($boxes));
    $contact = array_filter([$store['phone'], $store['email']]);
@endphp

{{-- compact: nothing but the boxes may follow the totals, or this spacer alone can start a blank page --}}
@unless ($compact)
    <table><tr><td style="height:{{ max(3, $push) }}mm;"></td></tr></table>
@endunless

@if ($boxes)
    <table class="boxes" style="margin-top:{{ $compact ? 3 : 0 }}mm;">
        <tr>
            @foreach ($boxes as $i => [$boxTitle, $boxBody])
                @if ($i > 0)<td class="gap"></td>@endif
                <td class="box" style="width:{{ (int) (100 / count($boxes)) - 1 }}%;">
                    <div class="label">{{ $boxTitle }}</div>
                    <div class="note">{!! nl2br(e($boxBody)) !!}</div>
                </td>
            @endforeach
        </tr>
    </table>
@endif

@unless ($compact)
<table class="closing">
    <tr>
        <td>
            <div class="thanks">{{ $thanks }}</div>
            <div class="small">{{ $store['footer'] }}</div>
            @if ($contact)
                <div class="small" style="margin-top:1.5mm;">Need help? <b>{{ implode('  ·  ', $contact) }}</b></div>
            @endif
        </td>
        @if ($qr)
            <td style="width:48mm; text-align:right; padding-right:2mm;"><div class="label">Track Your Order</div><div class="small">Scan to see live delivery status</div></td>
            <td style="width:24mm; padding-left:0;"><img src="{{ $qr }}" style="width:20mm; height:20mm;" alt=""></td>
        @endif
        @if ($store['signature'])
            <td class="signature" style="width:42mm;">
                <img src="{{ $store['signature'] }}" style="height:12mm; max-width:40mm;" alt=""><br>
                Authorized Signature
            </td>
        @endif
    </tr>
</table>
@endunless
