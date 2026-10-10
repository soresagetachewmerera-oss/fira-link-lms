<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Course Review</title>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;font-family:'Helvetica Neue',Helvetica,Arial,sans-serif;color:#1f2933;">
<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#f5f5f5;padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" role="presentation" style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(15,23,42,0.12);">
                <tr>
                    <td align="center" style="background:linear-gradient(135deg,#7c3aed,#6366f1);padding:34px;">
                        <h1 style="margin:16px 0 0;font-size:24px;line-height:1.4;color:#ffffff;font-weight:600;">
                            {{ $review->user?->name ?? 'A learner' }} left a review
                        </h1>
                        <p style="margin:8px 0 0;font-size:15px;color:rgba(255,255,255,0.85);">
                            Course: {{ $review->course?->title ?? 'Your course' }}
                        </p>
                    </td>
                </tr>
                <tr>
                    <td style="padding:32px;">
                        <p style="margin:0 0 16px;font-size:16px;color:#475467;">Hi {{ $review->course?->instructor?->name ?? 'Instructor' }},</p>
                        <p style="margin:0 0 16px;font-size:16px;color:#1f2933;line-height:1.6;">
                            Good news! @if($review->user) <strong>{{ $review->user->name }}</strong>@else A student@endif just shared their thoughts on <strong>{{ $review->course?->title ?? 'your course' }}</strong>.
                        </p>
                        <div style="background-color:#f5f3ff;border-left:4px solid #7c3aed;padding:16px;border-radius:12px;margin-bottom:24px;">
                            <strong style="display:block;margin-bottom:8px;color:#312e81;">Rating: {{ $review->rating }}/5</strong>
                            <p style="margin:0;font-size:15px;color:#4338ca;line-height:1.6;">{!! nl2br(e($review->comment ?? 'No comment provided.')) !!}</p>
                        </div>
                        <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
                            <tr>
                                <td style="border-radius:999px;background-color:#1f2937;">
                                    <a href="{{ route('instructor.reviews.index') }}" target="_blank" style="display:inline-block;padding:14px 32px;color:#ffffff;text-decoration:none;font-weight:600;font-size:16px;">
                                        View All Reviews
                                    </a>
                                </td>
                            </tr>
                        </table>
                        <p style="margin:0;font-size:14px;color:#4b5563;">
                            Respond promptly to keep learners engaged and show you value their feedback.
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
