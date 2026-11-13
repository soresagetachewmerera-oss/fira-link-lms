<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>{{ $subject ?? (config('app.name') . ' Newsletter') }}</title>
</head>

<body
    style="margin:0;padding:0;background-color:#f5f5f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2933;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#f5f5f5;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                    style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 12px 32px rgba(15,23,42,0.12);">
                    <tr>
                        <td align="center"
                            style="background:linear-gradient(135deg,#6558f5,#22d3ee);padding:32px 24px 28px;border-bottom:1px solid rgba(255,255,255,0.2);">
                            <h1
                                style="margin:0;font-size:24px;line-height:1.3;color:#ffffff;font-weight:600;text-transform:uppercase;letter-spacing:1px;">
                                {{ config('app.name') }} Insights
                            </h1>
                            <p style="margin:12px 0 0;font-size:15px;color:rgba(255,255,255,0.85);">
                                Discover the latest updates, courses, and community highlights.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px 40px 32px;">
                            <p style="margin:0 0 18px;font-size:16px;color:#475467;">
                                Hello {{ $recipientName ?? 'there' }},
                            </p>
                            <div style="font-size:16px;line-height:1.7;color:#1f2933;">
                                {!! $content !!}
                            </div>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:28px auto 0;">
                                <tr>
                                    <td style="border-radius:999px;background-color:#6558f5;">
                                        <a href="{{ config('app.url') }}" target="_blank"
                                            style="display:inline-block;padding:14px 34px;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;">
                                            Visit {{ config('app.name') }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:28px 0 0;font-size:15px;color:#4b5563;">
                                Thanks for being part of our learning community.<br>
                                — The {{ config('app.name') }} Team
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f8f9ff;padding:24px 32px;">
                            <p style="margin:0 0 12px;font-size:13px;color:#4b5563;">
                                You're receiving this email because you subscribed to updates from {{ config('app.name') }}.
                                If you no longer wish to receive these messages, you can update your preferences or reply to this
                                message.
                            </p>
                            <p style="margin:0;font-size:13px;color:#4b5563;">
                                © {{ now()->year }} {{ config('app.name') }} · {{ config('app.url') }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>

