<?php

namespace App\Services\Verification;

use App\Models\Organization;

/**
 * Fira ID: FL-XXXX-XXXX, 8 characters from a 31-letter alphabet without look-alikes
 * (no 0/O, 1/I/L). About 852 billion combinations, from random_int (CSPRNG), never sequential.
 */
final class FiraId
{
    public const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public static function generate(): string
    {
        $chars = '';
        $max = strlen(self::ALPHABET) - 1;
        for ($i = 0; $i < 8; $i++) {
            $chars .= self::ALPHABET[random_int(0, $max)];
        }

        return 'FL-' . substr($chars, 0, 4) . '-' . substr($chars, 4, 4);
    }

    public static function generateUnique(): string
    {
        do {
            $id = self::generate();
        } while (Organization::withTrashed()->where('fira_id', $id)->exists());

        return $id;
    }

    /** Accepts "fl-abcd-efgh", "FLABCDEFGH" or spaces; returns FL-XXXX-XXXX or null. */
    public static function normalize(string $input): ?string
    {
        $raw = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $input));
        if (str_starts_with($raw, 'FL')) {
            $raw = substr($raw, 2);
        }
        if (strlen($raw) !== 8 || strspn($raw, self::ALPHABET) !== 8) {
            return null;
        }

        return 'FL-' . substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
    }
}
