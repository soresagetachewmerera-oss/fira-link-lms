<?php

namespace App\Services\Trade;

/**
 * Finds contact details or off-platform invitations in a free-text message (quote notes now,
 * chat later): phone numbers, emails, links and messenger names. Deals stay inside Fira Link.
 */
class ContactDetector
{
    private const PATTERNS = [
        'email' => '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i',
        'link' => '/\b(?:https?:\/\/|www\.)\S+|\b[a-z0-9-]+\.(?:com|net|org|et|io|me|co)\b/i',
        'messenger' => '/\b(?:whats\s*app|telegram|signal|viber|imo|wechat|t\.me|wa\.me)\b|ዋትስአፕ|ቴሌግራም/iu',
    ];

    /** @return list<string> the kinds found, e.g. ['phone', 'messenger'] */
    public static function find(?string $text): array
    {
        $text = (string) $text;
        if (trim($text) === '') {
            return [];
        }
        $found = [];
        foreach (self::PATTERNS as $kind => $pattern) {
            if (preg_match($pattern, $text)) {
                $found[] = $kind;
            }
        }
        // A phone number: nine or more digits in one run (spaces, dots, dashes, brackets and a
        // leading + allowed), but not a date like 2026-11-14.
        if (preg_match_all('/\+?\d[\d\s().-]{7,}\d/', $text, $m)) {
            foreach ($m[0] as $candidate) {
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($candidate))) {
                    continue;
                }
                if (strlen(preg_replace('/\D/', '', $candidate)) >= 9) {
                    $found[] = 'phone';
                    break;
                }
            }
        }

        return $found;
    }
}
