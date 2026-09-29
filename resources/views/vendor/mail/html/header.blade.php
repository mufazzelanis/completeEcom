@props(['url'])
@php
    $siteName = setting('site_name', config('app.name'));
    $logoUrl  = setting_file_url('email_logo', setting_file_url('site_logo'));
    $primary  = setting('primary_color', '#ea580c');
@endphp
<tr>
<td class="header" style="background:linear-gradient(135deg, {{ $primary }}, #111827);">
<a href="{{ $url }}" style="display: inline-block;">
@if ($logoUrl)
<img src="{{ $logoUrl }}" class="logo" alt="{{ $siteName }}">
@else
<span style="color:#ffffff; font-size:19px; font-weight:700;">{{ $siteName }}</span>
@endif
</a>
</td>
</tr>
