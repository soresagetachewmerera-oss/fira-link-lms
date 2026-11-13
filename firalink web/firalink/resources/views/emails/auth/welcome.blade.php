<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ config('app.name') }}</title>
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
                        <td align="center" style="background:linear-gradient(135deg,#6558f5,#22d3ee);padding:36px;">

                            <h1 style="margin:16px 0 0;font-size:26px;line-height:1.4;color:#ffffff;font-weight:600;">
                                Welcome to {{ config('app.name') }}
                            </h1>
                            <p style="margin:8px 0 0;font-size:15px;color:rgba(255,255,255,0.85);">
                                We're excited to help you start learning without limits.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 16px;font-size:16px;color:#475467;">Hi {{ $user->name ?? 'there' }},
                            </p>
                            <p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#1f2933;">
                                Thanks for joining {{ config('app.name') }}! You're now part of a vibrant community of
                                learners and instructors around the world. Here are a few things you can do next:
                            </p>
                            <ul style="margin:0 0 24px 20px;padding:0;font-size:16px;color:#1f2933;line-height:1.7;">
                                <li>Browse our featured courses and bookmark your favorites.</li>
                                <li>Customize your learning goals to get smarter recommendations.</li>
                                <li>Download our mobile app to learn on the go.</li>
                            </ul>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                                <tr>
                                    <td style="border-radius:999px;background-color:#6558f5;">
                                        <a href="{{ config('app.url') }}" target="_blank"
                                            style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;">
                                            Explore Your Dashboard
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0 0 16px;font-size:15px;color:#4b5563;">
                                Need help getting started? Our support team is just a reply away or visit our Help
                                Center anytime.
                            </p>
                            <p style="margin:24px 0 0;font-size:15px;color:#4b5563;">
                                Happy learning!<br>
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
