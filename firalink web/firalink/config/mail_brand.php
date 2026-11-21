<?php

/*
 * Brand sheet for email, SMS and PDF (messaging slice 2). Frozen tokens: do not add colours.
 * The app (Flutter) and the website keep their own tokens; these apply to messages only (decision D-1).
 */
$domain = \Illuminate\Support\Str::after((string) env('MAIL_FROM_ADDRESS', 'no-reply@firalink.com'), '@') ?: 'firalink.com';

return [
    'name' => 'Fira Link',
    'company' => 'Fira Link Business Solutions',
    'address' => env('MAIL_COMPANY_ADDRESS', 'Addis Ababa, Ethiopia'),

    // Sender per class (decision D-5). The mailboxes must exist and be covered by SPF and DKIM.
    'from' => [
        'security' => env('MAIL_FROM_SECURITY', 'security@' . $domain),
        'transactional' => env('MAIL_FROM_NO_REPLY', 'no-reply@' . $domain),
        'product' => env('MAIL_FROM_NO_REPLY', 'no-reply@' . $domain),
        'marketing' => env('MAIL_FROM_NO_REPLY', 'no-reply@' . $domain),
    ],
    'support_email' => env('MAIL_SUPPORT_ADDRESS', 'support@' . $domain),

    'colors' => [
        'purple' => '#6D1A95',
        'deep' => '#3B0764',
        'tint' => '#F3E8FF',
        'page' => '#F9F7FA',
        'text' => '#1E1B4B',
        'muted' => '#6B7280',
        'hairline' => '#E9E4F0',
        'white' => '#FFFFFF',
    ],

    // Most mail clients ignore web fonts: the stack falls back to Arial and Noto Sans Ethiopic.
    'font' => "Inter, Arial, 'Noto Sans Ethiopic', 'Nyala', sans-serif",

    'radius' => ['card' => 16, 'button' => 12, 'box' => 12, 'pill' => 12],

    // The current Fira Link logo as hosted PNG @2x on white (readable when a client darkens the page).
    'logo' => [
        'path' => 'assets/email/fira-link-logo@2x.png',
        'width' => 134,
        'height' => 40,
    ],
];
