{{-- Footer on every page, then the first page's header. Expects $title and $number. The band's
     colour and the logo's white plate are painted by InvoicePdf; this fills them. --}}
<htmlpagefooter name="invoiceFooter">
    <table class="foot">
        <tr>
            <td style="width:75%;">{{ collect([$store['name'], $store['phone'], $store['email']])->filter()->implode('  ·  ') }}</td>
            <td style="text-align:right;">Page {PAGENO} of {nbpg}</td>
        </tr>
    </table>
</htmlpagefooter>
<sethtmlpagefooter name="invoiceFooter" value="on" show-this-page="1" />

<table class="band">
    <tr>
        <td style="width:55%; height:28mm;">
            @if ($store['logo'])
                <img src="{{ $store['logo'] }}" style="height:{{ \App\Support\InvoicePdf::LOGO_HEIGHT_MM }}mm; max-width:{{ \App\Support\InvoicePdf::LOGO_MAX_MM }}mm; margin-left:5mm;" alt="">
            @else
                <div class="brand-name">{{ $store['name'] }}</div>
            @endif
        </td>
        <td style="text-align:right;">
            <div class="doc-title">{{ $title }}</div>
            <div class="doc-number">{{ $number }}</div>
        </td>
    </tr>
</table>
