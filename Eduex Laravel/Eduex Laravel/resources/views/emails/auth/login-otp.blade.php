<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Login Verification Code</title>
</head>

<body
    style="margin:0;padding:0;background-color:#f5f5f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2933;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation"
        style="background-color:#f5f5f5;padding:24px 0;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" role="presentation"
                    style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.12);">
                    <tr>
                        <td align="center" style="background:linear-gradient(135deg,#22c55e,#22d3ee);padding:32px;">

                            <h1 style="margin:16px 0 0;font-size:24px;line-height:1.4;color:#ffffff;font-weight:600;">
                                Complete Your Sign-In
                            </h1>
                            <p style="margin:8px 0 0;font-size:15px;color:rgba(255,255,255,0.85);">
                                Enter the verification code below to finish logging in.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;color:#475467;">Hello {{ $user->name ?? 'there' }},
                            </p>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#1f2933;">
                                We received a request to sign in to your {{ config('app.name') }} account. Use the
                                one-time passcode below. It expires in {{ $expiresAt->diffInMinutes(now()) }} minutes.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" align="center"
                                style="margin:0 auto 24px;">
                                <tr>
                                    <td
                                        style="background-color:#1f2937;color:#ffffff;font-size:32px;font-weight:700;letter-spacing:8px;padding:18px 32px;border-radius:12px;">
                                        {{ $code }}
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;font-size:15px;color:#4b5563;">
                                If you did not try to sign in, please reset your password immediately or contact
                                support.
                            </p>
                            <hr style="border:none;border-top:1px solid #e5e7eb;margin:32px 0;">
                            <p style="margin:0;font-size:13px;color:#6b7280;">This code will expire at
                                {{ $expiresAt->format('H:i A T') }}.</p>
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
