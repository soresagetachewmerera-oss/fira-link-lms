<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Course Updated</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2933;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f5f5;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.12);">
                <tr>
                    <td align="center" style="background:linear-gradient(135deg,#22d3ee,#0ea5e9);padding:34px;">
                        <h1 style="margin:16px 0 0;font-size:24px;line-height:1.4;color:#ffffff;font-weight:600;">
                            {{ $course->title }} was just updated
                        </h1>
                        <p style="margin:8px 0 0;font-size:15px;color:rgba(255,255,255,0.85);">
                            {{ $instructor->name }} made changes on {{ $occurredAt->format('M d, Y') }}.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px;font-size:16px;color:#475467;">Hello {{ $studentName ?? 'there' }},</p>
                        <p style="margin:0 0 16px;font-size:16px;color:#1f2933;line-height:1.6;">
                            We wanted to let you know that <strong>{{ $course->title }}</strong> has new updates so you can stay in sync with the latest content.
                        </p>
                        @if($summary)
                            <div style="background-color:#f8fafc;border-left:4px solid #22d3ee;padding:16px;border-radius:12px;margin-bottom:24px;">
                                <strong style="display:block;margin-bottom:8px;color:#0f172a;">Highlights</strong>
                                <p style="margin:0;font-size:15px;color:#475467;line-height:1.6;">{!! nl2br(e($summary)) !!}</p>
                            </div>
                        @endif
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                            <tr>
                                <td style="border-radius:999px;background-color:#0f172a;">
                                    <a href="{{ route('courses.show', $course->slug ?? $course->id) }}" target="_blank" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;">
                                        See What's New
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0 0 16px;font-size:14px;color:#4b5563;">
                            Tip: Mark important lessons as complete so we can better tailor reminders to your progress.
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
