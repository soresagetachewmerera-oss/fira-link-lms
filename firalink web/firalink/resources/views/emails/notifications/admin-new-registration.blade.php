<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Registration</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2933;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f5f5;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.12);">
                <tr>
                    <td align="center" style="background:linear-gradient(135deg,#2563eb,#7c3aed);padding:34px;">
                        <h1 style="margin:16px 0 0;font-size:24px;line-height:1.4;color:#ffffff;font-weight:600;">
                            New {{ ucfirst($user->role) }} Registration
                        </h1>
                        <p style="margin:8px 0 0;font-size:15px;color:rgba(255,255,255,0.85);">
                            {{ $user->name }} just created an account on {{ config('app.name') }}.
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px;font-size:16px;color:#475467;">Hi Admin,</p>
                        <p style="margin:0 0 16px;font-size:16px;color:#1f2933;line-height:1.6;">
                            A new {{ $user->role }} has joined the platform. You can review their details below.
                        </p>
                        <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin:0 0 24px;font-size:15px;color:#1f2933;line-height:1.8;">
                            <tr>
                                <td width="40%" style="font-weight:600;">Name</td>
                                <td>{{ $user->name }}</td>
                            </tr>
                            <tr>
                                <td width="40%" style="font-weight:600;">Email</td>
                                <td><a href="mailto:{{ $user->email }}" style="color:#2563eb;text-decoration:none;">{{ $user->email }}</a></td>
                            </tr>
                            <tr>
                                <td width="40%" style="font-weight:600;">Role</td>
                                <td>{{ ucfirst($user->role) }}</td>
                            </tr>
                            <tr>
                                <td width="40%" style="font-weight:600;">Registered</td>
                                <td>{{ $user->created_at?->format('M d, Y H:i') ?? now()->format('M d, Y H:i') }}</td>
                            </tr>
                        </table>
                        <p style="margin:0;font-size:14px;color:#4b5563;">
                            Need to review this account? Visit the admin dashboard to approve, assign roles, or follow up.
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
