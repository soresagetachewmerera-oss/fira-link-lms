<?php

return [
    'api_ninjas_key' => env('API_NINJAS_API_KEY'),
    'http_timeout_seconds' => (int) env('MARKET_HTTP_TIMEOUT', 10),
    // Retries with growing waits (milliseconds) after a failed request.
    'http_backoff_ms' => [500, 2000],
    'public_rate_per_minute' => (int) env('MARKET_PUBLIC_RATE_PER_MINUTE', 60),
    'trend' => [
        'minimum_samples' => 20,
        'mean_threshold_percent' => 1.5,
        'slope_threshold_percent' => 0.25,
    ],
    // Market watch rows (/api/market/watch, website and app).
    'watch' => [
        'cache_seconds' => 60,
        'http_max_age_seconds' => 300,
        'sparkline_points' => 30,
        'home_rows' => 4,
    ],
    // Staff mailbox told when an automatic source misses this many fetch cycles in a row.
    'admin_email' => env('MARKET_ADMIN_EMAIL', env('VERIFICATION_ADMIN_EMAIL')),
    'outage_cycles' => 2,
    'ai_enabled' => (bool) env('MARKET_AI_ENABLED', false),
    'live_smoke_tests' => (bool) env('MARKET_LIVE_SMOKE_TESTS', false),

    // Freshness policy per instrument (or category): live_window_seconds (only if licensed real-time feed exists),
    // delayed_minutes, daily_close_grace_hours, stale_after_hours.
    // The server computes status: live, delayed, daily_close, schedule, stale, unavailable. Clients never decide status.
    'freshness' => [
        'default' => [
            'live_window_seconds' => null, // No zero-delay feed by default
            'delayed_minutes' => 15,
            'daily_close_grace_hours' => 36,
            'stale_after_hours' => 72,
        ],
        'KC_ACTIVE' => [
            'live_window_seconds' => null, // ICE Arabica delayed 15m per exchange rules
            'delayed_minutes' => 15,
            'daily_close_grace_hours' => 24,
            'stale_after_hours' => 48,
        ],
        'ICO_COMPOSITE' => [
            'live_window_seconds' => null, // Daily indicator
            'delayed_minutes' => null,
            'daily_close_grace_hours' => 36,
            'stale_after_hours' => 72,
        ],
        'weekly_regulatory_floor' => [
            'live_window_seconds' => null,
            'delayed_minutes' => null,
            'daily_close_grace_hours' => 168, // Weekly regulatory schedule (7 days)
            'stale_after_hours' => 336, // Stale after 14 days
        ],
        'NBE_USD_ETB' => [
            'live_window_seconds' => null,
            'delayed_minutes' => null,
            'daily_close_grace_hours' => 36,
            'stale_after_hours' => 72,
        ],
    ],
];
