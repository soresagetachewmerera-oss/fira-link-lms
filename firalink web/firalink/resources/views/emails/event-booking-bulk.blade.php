<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $mailSubject ?? $event->title }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2933;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f5f5;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.12);">
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg,#2563eb,#7c3aed);padding:32px;">
                            
                            <h1 style="margin:0;font-size:22px;line-height:1.4;color:#ffffff;font-weight:600;">
                                {{ $event->title }}
                            </h1>
                            <p style="margin:8px 0 0;font-size:16px;color:rgba(255,255,255,0.85);">
                                {{ optional($event->start_date)->format('M d, Y') ?? 'Date to be announced' }}
                                @if($event->location)
                                    • {{ $event->location }}
                                @endif
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;color:#475467;">
                                Hello {{ $booking->full_name }},
                            </p>
                            <div style="font-size:16px;line-height:1.6;color:#1f2933;margin-bottom:24px;">
                                {!! nl2br(e($bodyContent)) !!}
                            </div>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td style="border-radius:999px;background-color:#2563eb;">
                                        <a href="{{ route('events.show', $event->slug) }}" target="_blank" style="display:inline-block;padding:14px 28px;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;">
                                            View Event Details
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;font-size:14px;color:#667085;">
                                Need help? Reply directly to this email or reach us at {{ $event->contact_email ?? config('mail.from.address') }}.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="background-color:#f0f4ff;padding:24px;">
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

