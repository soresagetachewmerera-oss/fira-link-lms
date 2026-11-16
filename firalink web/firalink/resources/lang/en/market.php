<?php

return [
    'disclaimer' => 'Prices are indicative and are not trading advice.',
    'freshness' => [
        'live' => 'Live', 'delayed' => 'Delayed :minutes min', 'daily_close' => 'Daily close · :date',
        'stale' => 'Stale · :minutes min old', 'unavailable' => 'Unavailable',
    ],
    'summary' => [
        'unavailable' => 'A current hero benchmark is unavailable. Check the source status before using market data.',
        'available' => ':name is :value :currency/:unit. :freshness. Source time and attribution are shown with the price.',
    ],
    'legacy_unavailable' => 'Generated live ticks have been retired. Use the sourced market endpoints.',
    'admin' => [
        'sources' => 'Sources', 'source' => 'Source', 'enabled' => 'On', 'disabled' => 'Off', 'terms' => 'Terms',
        'last_success' => 'Last success', 'last_error' => 'Last error', 'never' => 'Never', 'series' => 'Series',
        'turn_on' => 'Turn on', 'turn_off' => 'Turn off', 'fetch_now' => 'Fetch now',
        'confirm_toggle' => 'Change whether this source is used? Turning a source on means its prices are shown to everyone.',
        'review_title' => 'Prices waiting for review', 'review_empty' => 'No prices are waiting for review.',
        'value' => 'Value', 'source_time' => 'Source time', 'why_held' => 'Why it was held', 'reason' => 'Reason (required)',
        'approve' => 'Approve and publish', 'reject' => 'Reject',
        'title' => 'Coffee market sources', 'source_updated' => 'Source status updated.',
        'fetch_complete' => 'Source fetch completed.', 'fetch_failed' => 'Source fetch failed. Review the source error.',
        'price_approved' => 'Price approved and published.', 'price_rejected' => 'Price rejected.', 'already_reviewed' => 'This row was already reviewed.',
    ],
];
