<?php

namespace App\Support;

/**
 * SMS segment count (messaging). Text in the GSM-7 alphabet: 160 characters in one SMS, 153 per
 * part when longer. Anything else (Amharic, emoji, curly quotes) is sent as UCS-2: 70 characters
 * in one SMS, 67 per part.
 */
final class SmsSegments
{
    private const GSM7 = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
    private const GSM7_EXT = "^{}\\[~]|€\f";

    /** @return array{encoding: string, characters: int, segments: int, per_segment: int} */
    public static function analyse(string $text): array
    {
        $chars = mb_str_split($text);
        $gsm = true;
        $units = 0;
        foreach ($chars as $ch) {
            if (mb_strpos(self::GSM7, $ch) !== false) {
                $units++;
            } elseif (mb_strpos(self::GSM7_EXT, $ch) !== false) {
                $units += 2; // escape + character
            } else {
                $gsm = false;
                break;
            }
        }

        if ($gsm) {
            $single = 160;
            $multi = 153;
            $length = $units;
        } else {
            $single = 70;
            $multi = 67;
            // UCS-2 counts UTF-16 code units (characters outside the basic plane take two).
            $length = intdiv(strlen(mb_convert_encoding($text, 'UTF-16BE', 'UTF-8')), 2);
        }
        $segments = $length === 0 ? 0 : ($length <= $single ? 1 : (int) ceil($length / $multi));

        return [
            'encoding' => $gsm ? 'GSM-7' : 'UCS-2',
            'characters' => $length,
            'segments' => $segments,
            'per_segment' => $segments > 1 ? $multi : $single,
        ];
    }
}
