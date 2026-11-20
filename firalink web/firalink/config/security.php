<?php

/**
 * Access control settings (auth gating, 2026-11-20). The route allow-list is in config/public_routes.php.
 */
return [
    // Class A (public) API routes, per IP.
    'public_rate_per_minute' => (int) env('PUBLIC_RATE_PER_MINUTE', 120),

    // Class D (sensitive) actions, on top of fresh authentication.
    'sensitive_per_user_per_minute' => 20,
    'sensitive_per_ip_per_minute' => 30,

    // Fira AI questions from guests (no account), per IP per day. Signed-in users are not counted here.
    'ai_guest_per_day' => (int) env('AI_GUEST_PER_DAY', 30),

    // Denied requests (401 with a token, 403) are written to the audit log at most once per user (or IP),
    // route and status in this window, so a client retrying in a loop cannot flood the table.
    'denied_audit_window_minutes' => 10,
];
