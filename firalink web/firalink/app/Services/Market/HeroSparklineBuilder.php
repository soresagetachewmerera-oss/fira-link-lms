<?php

namespace App\Services\Market;

/**
 * Pure server-side helpers for hero SVG sparklines and trend words.
 */
class HeroSparklineBuilder
{
    /**
     * Normalize an ordered series of numeric closes (oldest first) into SVG polyline/area paths.
     *
     * @param  array<int, float|int|string>  $values
     * @return array{line: string, area: string, min: float, max: float, count: int}|null
     */
    public static function buildPath(
        array $values,
        float $width = 200.0,
        float $height = 52.0,
        float $padding = 4.0
    ): ?array {
        $nums = [];
        foreach (array_slice($values, -30) as $raw) {
            if (is_numeric($raw)) {
                $val = (float) $raw;
                if (is_finite($val)) {
                    $nums[] = $val;
                }
            }
        }

        $count = count($nums);
        if ($count < 2) {
            return null;
        }

        $min = min($nums);
        $max = max($nums);
        $span = $max - $min;
        $usableWidth = max(1.0, $width - ($padding * 2));
        $usableHeight = max(1.0, $height - ($padding * 2));

        $points = [];
        foreach ($nums as $index => $val) {
            $x = $padding + ($index / ($count - 1)) * $usableWidth;
            if ($span <= 0.0) {
                $y = $padding + ($usableHeight / 2.0);
            } else {
                $normalized = ($val - $min) / $span;
                $y = $padding + ((1.0 - $normalized) * $usableHeight);
            }
            $points[] = [round($x, 1), round($y, 1)];
        }

        $lineParts = [];
        foreach ($points as $i => [$px, $py]) {
            $lineParts[] = ($i === 0 ? 'M' : 'L') . $px . ',' . $py;
        }
        $line = implode(' ', $lineParts);

        $firstX = $points[0][0];
        $lastX = $points[$count - 1][0];
        $baselineY = round($height, 1);
        $area = $line . " L{$lastX},{$baselineY} L{$firstX},{$baselineY} Z";

        return [
            'line' => $line,
            'area' => $area,
            'min' => $min,
            'max' => $max,
            'count' => $count,
        ];
    }

    /**
     * Map stored MarketTrend label to plain human trend word (Up / Down / Sideways).
     */
    public static function trendWordFromLabel(?string $trendLabel): ?string
    {
        return match ($trendLabel) {
            'bullish' => 'Up',
            'bearish' => 'Down',
            'sideways' => 'Sideways',
            default => null,
        };
    }

    /**
     * Compute trend word directly from a chronological price series (oldest first).
     * Mirrors MarketTrendService thresholds (20-day mean + 5-day slope), falling back
     * safely when fewer than minimum samples exist.
     *
     * @param  array<int, float|int|string>  $values
     */
    public static function trendWordFromSeries(
        array $values,
        int $minimumSamples = 20,
        float $meanThresholdPercent = 1.5,
        float $slopeThresholdPercent = 0.25
    ): ?string {
        $nums = [];
        foreach ($values as $raw) {
            if (is_numeric($raw)) {
                $nums[] = (float) $raw;
            }
        }

        $count = count($nums);
        if ($count < $minimumSamples) {
            return null;
        }

        $last = $nums[$count - 1];
        $window20 = array_slice($nums, -20);
        $average = array_sum($window20) / count($window20);
        if ($average <= 0.0) {
            return 'Sideways';
        }

        $window5 = array_slice($nums, -5);
        $slope = self::linearSlope($window5);
        $meanDelta = (($last - $average) / $average) * 100.0;
        $slopePercent = ($slope / $average) * 100.0;

        if ($meanDelta >= $meanThresholdPercent && $slopePercent >= $slopeThresholdPercent) {
            return 'Up';
        }
        if ($meanDelta <= -$meanThresholdPercent && $slopePercent <= -$slopeThresholdPercent) {
            return 'Down';
        }

        return 'Sideways';
    }

    /**
     * @param  array<int, float>  $values
     */
    private static function linearSlope(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }
        $xMean = ($count - 1) / 2.0;
        $yMean = array_sum($values) / $count;
        $numerator = 0.0;
        $denominator = 0.0;
        foreach ($values as $i => $val) {
            $numerator += ($i - $xMean) * ($val - $yMean);
            $denominator += ($i - $xMean) ** 2;
        }

        return $denominator > 0.0 ? $numerator / $denominator : 0.0;
    }
}
