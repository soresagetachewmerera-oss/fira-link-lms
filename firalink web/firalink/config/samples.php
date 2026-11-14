<?php

/*
| Samples (slice 6). Buyers ship on their own courier account; Fira Link does not book couriers yet.
|
| Payment of the sample fee through Chapa is MOCKED behind SAMPLE_PAYMENTS_MOCK until the leaked
| Chapa keys are rotated. With the flag off (the default) a paid sample cannot be paid at all:
| "Payments are not available yet". The mock is refused in production.
*/

return [
    'payments_mock' => (bool) env('SAMPLE_PAYMENTS_MOCK', false),

    // The seller ships within this many days of payment (shown as "Ship by").
    'ship_within_days' => (int) env('SAMPLE_SHIP_WITHIN_DAYS', 7),

    // Seal ID printed on a tamper-evident seal: letters, digits and dashes.
    'seal_id_pattern' => '/^[A-Z0-9][A-Z0-9-]{3,31}$/',

    'tracking_urls' => [
        'dhl' => 'https://www.dhl.com/global-en/home/tracking/tracking-express.html?submit=1&tracking-id=%s',
        'fedex' => 'https://www.fedex.com/fedextrack/?trknbr=%s',
    ],
];
