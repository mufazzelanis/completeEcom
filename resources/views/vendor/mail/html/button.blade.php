@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php
    // Laravel's built-in notifications (password reset, email verification) never
    // pass anything but the default 'primary' color, so this is the one that needs
    // to track the admin's brand color; 'success'/'error' stay the fixed green/red
    // from the theme CSS since those signal a specific state, not the brand.
    $primary = setting('primary_color', '#ea580c');
    $buttonStyle = $color === 'primary'
        ? "background-color:{$primary};border-color:{$primary};"
        : '';
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button button-{{ $color }}" style="{{ $buttonStyle }}" target="_blank" rel="noopener">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
