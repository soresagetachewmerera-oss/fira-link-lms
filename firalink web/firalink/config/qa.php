<?php

return [
    // QA tools (qa:seed, qa:code, qa:confirm-payment, qa:cleanup) never run in production.
    // On any other non-local server they also need QA_TOOLS_ENABLED=true.
    'enabled' => (bool) env('QA_TOOLS_ENABLED', false),

    // Every QA account uses this email domain; qa:code and qa:cleanup touch nothing else.
    'email_domain' => 'qa.firalink.test',

    // Phones you own (comma separated) that may receive a QA sign-in code for a new account.
    'phones' => array_values(array_filter(array_map('trim', explode(',', (string) env('QA_PHONES', ''))))),
];
