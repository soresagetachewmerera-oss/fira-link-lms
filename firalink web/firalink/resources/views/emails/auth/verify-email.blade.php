<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Verify your email</title>
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
                        <td align="center" style="background:linear-gradient(135deg,#f97316,#facc15);padding:34px;">

                            <h1 style="margin:16px 0 0;font-size:24px;line-height:1.4;color:#ffffff;font-weight:600;">
                                Confirm your email address
                            </h1>
                            <p style="margin:8px 0 0;font-size:15px;color:rgba(255,255,255,0.85);">
                                Secure your {{ config('app.name') }} account in one click.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;color:#475467;">Hi {{ $user->name ?? 'there' }},
                            </p>
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#1f2933;">
                                Thanks for creating an account with {{ config('app.name') }}. To keep your account
                                secure and unlock all features, please verify your email address by clicking the button
                                below.
                            </p>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td style="border-radius:999px;background-color:#1f2937;">
                                        <a href="{{ $verificationUrl }}" target="_blank"
                                            style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;">
                                            Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;font-size:15px;color:#4b5563;">
                                This link will expire in 24 hours for your security. If the button above doesn't work,
                                copy and paste the following link into your browser:
                            </p>
                            <p style="word-break:break-all;margin:0 0 24px;font-size:14px;color:#6366f1;">
                                {{ $verificationUrl }}
                            </p>
                            <p style="margin:0 0 16px;font-size:15px;color:#4b5563;">
                                If you didn't create an account, you can safely ignore this email.
                            </p>
                            <p style="margin:24px 0 0;font-size:15px;color:#4b5563;">
                                Cheers,<br>
                                The {{ config('app.name') }} Team
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
