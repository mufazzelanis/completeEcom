{{-- The one wrapper every system email goes through (wrap_branded_email): order and status
     emails, review requests, support replies, campaigns. Table layout + inline styles, because
     that's the only thing Gmail, Outlook and phone mail apps all render the same way; the small
     <style> block only adds the phone-width tweaks (clients that drop it still get a usable
     600px-max layout). --}}
@php
    $siteName = setting('site_name', 'ShopVista');
    $logoUrl = setting_file_url('email_logo', setting_file_url('site_logo'));
    $accent = $accent ?? email_accent_color();
    $phone = setting('company_phone');
    $email = setting('support_email') ?: setting('company_email') ?: config('mail.from.address');
    $address = setting('company_address');
    $social = collect([
        'Facebook' => setting('facebook_url'),
        'Instagram' => setting('instagram_url'),
        'YouTube' => setting('youtube_url'),
    ])->filter(fn ($url, $name) => $url && filter_var($url, FILTER_VALIDATE_URL) && str_contains(strtolower(parse_url($url, PHP_URL_HOST) ?? ''), strtolower($name === 'YouTube' ? 'youtube' : $name)));
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $siteName }}</title>
    <style>
        body, table, td { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        img { border: 0; line-height: 100%; outline: none; text-decoration: none; }
        .em-body p { margin: 0 0 14px; }
        .em-body a { color: {{ $accent }}; }
        @media only screen and (max-width: 620px) {
            .em-container { width: 100% !important; }
            .em-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .em-stack, .em-stack td { display: block !important; width: 100% !important; box-sizing: border-box; }
            .em-stack td { padding-left: 0 !important; padding-right: 0 !important; }
            .em-stack td + td { padding-top: 12px !important; }
            .em-hide-sm { display: none !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f1f3f7; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    @if(!empty($preheader))
        {{-- inbox preview line (the grey text after the subject) — hidden in the email itself --}}
        <div style="display:none; max-height:0; max-width:0; overflow:hidden; opacity:0; font-size:1px; line-height:1px; color:#f1f3f7;">{{ $preheader }}{!! str_repeat('&#847;&zwnj;&nbsp;', 60) !!}</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f1f3f7;">
        <tr>
            <td align="center" style="padding:28px 12px 32px;">
                <table role="presentation" class="em-container" width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px; max-width:600px;">
                    {{-- brand --}}
                    <tr>
                        <td align="center" style="padding:4px 0 22px;">
                            <a href="{{ url('/') }}" style="text-decoration:none;">
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="{{ $siteName }}" height="44" style="height:44px; max-height:44px; width:auto; display:inline-block;">
                                @else
                                    <span style="font-size:24px; font-weight:800; color:#111827; letter-spacing:-.3px;">{{ $siteName }}</span>
                                @endif
                            </a>
                        </td>
                    </tr>

                    {{-- card --}}
                    <tr>
                        <td style="background-color:#ffffff; border-radius:18px; overflow:hidden; box-shadow:0 6px 24px rgba(17,24,39,0.06);">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr><td style="height:5px; line-height:5px; font-size:0; background-color:{{ $accent }}; border-radius:18px 18px 0 0;">&nbsp;</td></tr>
                                <tr>
                                    <td class="em-pad em-body" style="padding:34px 40px 36px; font-size:15px; line-height:1.65; color:#374151;">
                                        {!! $body !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- help strip --}}
                    @if($phone || $email)
                        <tr>
                            <td style="padding:18px 8px 0;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:14px;">
                                    <tr>
                                        <td class="em-pad" style="padding:16px 24px; font-size:13px; line-height:1.6; color:#4b5563; text-align:center;">
                                            <strong style="color:#111827;">Need help?</strong>
                                            We're here for you —
                                            @if($phone)<a href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}" style="color:{{ $accent }}; text-decoration:none; font-weight:600;">{{ $phone }}</a>@endif
                                            @if($phone && $email) · @endif
                                            @if($email)<a href="mailto:{{ $email }}" style="color:{{ $accent }}; text-decoration:none; font-weight:600;">{{ $email }}</a>@endif
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    {{-- footer --}}
                    <tr>
                        <td align="center" style="padding:22px 16px 0; font-size:12px; line-height:1.7; color:#9ca3af;">
                            @if($social->isNotEmpty())
                                <p style="margin:0 0 8px;">
                                    @foreach($social as $name => $url)
                                        <a href="{{ $url }}" style="color:#6b7280; text-decoration:none; font-weight:600; margin:0 6px;">{{ $name }}</a>
                                    @endforeach
                                </p>
                            @endif
                            <p style="margin:0;">
                                <a href="{{ url('/') }}" style="color:#6b7280; text-decoration:none; font-weight:600;">{{ $siteName }}</a>
                                @if($address) · {{ $address }}@endif
                            </p>
                            <p style="margin:4px 0 0;">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                            @if(!empty($footerExtra))
                                <p style="margin:8px 0 0;">{!! $footerExtra !!}</p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
