<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Course Enrollment</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2933;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f5f5;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.12);">
                <tr>
                    <td align="center" style="background:linear-gradient(135deg,#10b981,#14b8a6);padding:34px;">
                        <h1 style="margin:16px 0 0;font-size:24px;line-height:1.4;color:#ffffff;font-weight:600;">
                            New Enrollment in {{ $course->title }}
                        </h1>
                        <p style="margin:8px 0 0;font-size:15px;color:rgba(255,255,255,0.85);">
                            {{ $student->name }} joined on {{ $enrolledAt->format('M d, Y') }}.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px;font-size:16px;color:#475467;">Hi {{ $course->instructor?->name ?? 'Instructor' }},</p>
                        <p style="margin:0 0 16px;font-size:16px;color:#1f2933;line-height:1.6;">
                            Great news! <strong>{{ $student->name }}</strong> just enrolled in <strong>{{ $course->title }}</strong>. Keep the momentum going by welcoming them or sharing a quick orientation note.
                        </p>
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:24px 0;font-size:15px;color:#1f2933;line-height:1.8;">
                           
                            <tr>
                                <td width="40%" style="font-weight:600;">Enrollment Time</td>
                                <td>{{ $enrolledAt->format('M d, Y H:i') }}</td>
                            </tr>
                        </table>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                            <tr>
                                <td style="border-radius:999px;background-color:#0f172a;">
                                    <a href="{{ route('instructor.courses') }}" target="_blank" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;">
                                        View Course Dashboard
                                    </a>
                                </td>
                            </tr>
                        </table>
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
