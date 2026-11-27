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
];
