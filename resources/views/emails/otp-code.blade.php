@php
    $siteName = setting('site_name', 'ShopVista');
    $logoUrl  = setting_file_url('email_logo', setting_file_url('site_logo'));
    $primary  = setting('primary_color', '#ea580c');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subject }}</title>
</head>
{{-- Table-based layout with inline styles throughout — email clients (Outlook, Gmail
     app, etc.) strip <style> blocks and don't reliably support flexbox/grid, so this
     is the one layout approach that renders consistently everywhere. --}}
<body style="margin:0; padding:0; background-color:#f4f4f6; font-family:'Segoe UI', Helvetica, Arial, sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f6; padding:32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px; background-color:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 16px rgba(17,24,39,0.08);">

                <tr>
                    <td style="background:linear-gradient(135deg, {{ $primary }}, #111827); padding:28px 32px; text-align:center;">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $siteName }}" style="max-height:40px; display:inline-block;">
                        @else
                            <span style="color:#ffffff; font-size:20px; font-weight:700; letter-spacing:.3px;">{{ $siteName }}</span>
                        @endif
                    </td>
                </tr>

                <tr>
                    <td style="padding:36px 32px 8px; text-align:center;">
                        <div style="width:56px; height:56px; background-color:{{ $primary }}1a; border-radius:16px; display:inline-block; line-height:56px; margin-bottom:16px; font-size:26px;">&#128274;</div>
                        <h1 style="margin:0 0 8px; font-size:19px; color:#111827; font-weight:700;">{{ $subject }}</h1>
                        <p style="margin:0; font-size:14px; color:#6b7280; line-height:1.6;">{{ $intro }}</p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:24px 32px 8px;">
                        <div style="background-color:#f9fafb; border:1.5px dashed {{ $primary }}; border-radius:14px; padding:22px; text-align:center;">
                            <span style="font-family:'Courier New', monospace; font-size:34px; font-weight:800; letter-spacing:10px; color:#111827;">{{ $code }}</span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:16px 32px 28px; text-align:center;">
                        <p style="margin:0; font-size:13px; color:#9ca3af; line-height:1.6;">
                            This code expires in <strong style="color:#374151;">5 minutes</strong>.<br>
                            Never share it with anyone — {{ $siteName }} staff will never ask for it.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding:18px 32px; border-top:1px solid #f0f0f1; text-align:center;">
                        <p style="margin:0; font-size:12px; color:#b0b4ba; line-height:1.6;">
                            If you didn't request this code, you can safely ignore this email.<br>
                            &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
