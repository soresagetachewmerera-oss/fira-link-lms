<?php

return [
    // Business workspaces one person may own. Never tied to a plan; support can raise it per account.
    'max_owned_businesses' => (int) env('WORKSPACES_MAX_OWNED_BUSINESSES', 3),

    // Countries offered in Business setup (S-103). Ethiopia first (the default), then the main
    // coffee origins and buyer markets. Stored on the organization by name.
    'countries' => [
        'Ethiopia', 'Australia', 'Austria', 'Bahrain', 'Belgium', 'Brazil', 'Canada', 'China', 'Colombia',
        'Czech Republic', 'Denmark', 'Djibouti', 'Egypt', 'Finland', 'France', 'Germany', 'Greece', 'India',
        'Indonesia', 'Ireland', 'Israel', 'Italy', 'Japan', 'Jordan', 'Kenya', 'Kuwait', 'Lebanon',
        'Malaysia', 'Mexico', 'Morocco', 'Netherlands', 'New Zealand', 'Nigeria', 'Norway', 'Oman',
        'Poland', 'Portugal', 'Qatar', 'Russia', 'Rwanda', 'Saudi Arabia', 'Singapore', 'Somalia',
        'South Africa', 'South Korea', 'Spain', 'Sudan', 'Sweden', 'Switzerland', 'Taiwan', 'Tanzania',
        'Thailand', 'Turkey', 'Uganda', 'Ukraine', 'United Arab Emirates', 'United Kingdom',
        'United States', 'Vietnam', 'Yemen',
    ],
];
