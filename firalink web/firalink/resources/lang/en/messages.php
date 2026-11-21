<?php

/*
 * Catalogue message texts (messaging 2026-11-21). One block per NotificationType key:
 * messages.<key>.<part>, part = subject, preheader, eyebrow, heading, body, cta, push_title, push_body, sms.
 * Each group (A to K) is added in its own slice, in English and Amharic.
 * Push and in-app texts must be safe on a lock screen: no amounts, names, reasons or codes.
 */
return [
    'fallback' => [
        'title' => 'Fira Link',
        'body' => 'You have an update. Open Fira Link to see it.',
    ],
];
