<?php

namespace App\Support\Market;

/**
 * SVG paths for a market watch sparkline: the line, the area under it and the y of the previous-close
 * reference line. The scale includes the reference, so the dashed line always sits inside the box.
 * Returns null for fewer than two finite values (no line is drawn rather than a made-up one).
 */
final class SparklinePath
{
    /**
     * @param  array<int, float|int|string>  $values oldest first
     * @return array{line: string, area: string, ref_y: float|null, last_x: float, last_y: float}|null
     */
    public static function build(array $values, float|int|null $ref = null, float $width = 96.0, float $height = 34.0, float $padding = 3.0): ?array
    {
        $nums = [];
        foreach ($values as $raw) {
            if (is_numeric($raw) && is_finite((float) $raw)) {
                $nums[] = (float) $raw;
            }
        }
        $count = count($nums);
        if ($count < 2) {
            return null;
        }

        $scale = $nums;
        $hasRef = $ref !== null && is_finite((float) $ref);
        if ($hasRef) {
            $scale[] = (float) $ref;
        }
        $min = min($scale);
        $span = max($scale) - $min;
        $usableW = max(1.0, $width - 2 * $padding);
        $usableH = max(1.0, $height - 2 * $padding);
        $y = fn (float $v) => round($span > 0 ? $padding + (1 - ($v - $min) / $span) * $usableH : $padding + $usableH / 2, 2);

        $parts = [];
        foreach ($nums as $i => $value) {
            $parts[] = ($i === 0 ? 'M' : 'L') . round($padding + ($i / ($count - 1)) * $usableW, 2) . ',' . $y($value);
        }
        $line = implode(' ', $parts);
        $right = round($padding + $usableW, 2);
        $bottom = round($height - $padding, 2);

        return [
            'line' => $line,
            'area' => $line . " L{$right},{$bottom} L{$padding},{$bottom} Z",
            'ref_y' => $hasRef ? $y((float) $ref) : null,
            'last_x' => $right,
            'last_y' => $y(end($nums)),
        ];
    }
}
