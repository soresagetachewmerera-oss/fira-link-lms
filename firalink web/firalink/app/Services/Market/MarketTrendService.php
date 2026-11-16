<?php

namespace App\Services\Market;

use App\Models\MarketSeries;
use App\Models\MarketTrend;

/**
 * Trend from stored, published prices only (Gemini never sets it).
 * Uses one close per UTC day (the last published value of that day): last close vs the 20-day mean,
 * plus the least-squares slope of the last 5 closes. Fewer than the minimum closes: not_enough_data.
 */
class MarketTrendService
{
    public function compute(MarketSeries $series): MarketTrend
    {
        $values = collect($this->dailyCloses($series))->map(fn ($v) => (float) $v)->values();
        $minimum = (int) config('market.trend.minimum_samples', 20);
        $meanThreshold = (float) config('market.trend.mean_threshold_percent', 1.5);
        $slopeThreshold = (float) config('market.trend.slope_threshold_percent', 0.25);
        $trend = 'not_enough_data';
        $average = null;
        $slope = null;
        $last = $values->last();

        if ($values->count() >= $minimum) {
            $average = $values->slice(-20)->avg();
            $slope = $this->linearSlope($values->slice(-5)->values()->all());
            $meanDelta = $average > 0 ? (($last - $average) / $average) * 100 : 0;
            $slopePercent = $average > 0 ? ($slope / $average) * 100 : 0;
            $trend = match (true) {
                $meanDelta >= $meanThreshold && $slopePercent >= $slopeThreshold => 'bullish',
                $meanDelta <= -$meanThreshold && $slopePercent <= -$slopeThreshold => 'bearish',
                default => 'sideways',
            };
        }

        return MarketTrend::query()->updateOrCreate(['series_id' => $series->id], [
            'computed_at' => now(), 'trend' => $trend, 'last_close' => $last,
            'average_20' => $average, 'slope_5' => $slope, 'sample_size' => $values->count(),
            'inputs' => [
                'basis' => 'daily_close_utc',
                'minimum_samples' => $minimum,
                'mean_threshold_percent' => $meanThreshold,
                'slope_threshold_percent' => $slopeThreshold,
            ],
        ]);
    }

    /** Last published value of each UTC day, oldest first, at most 60 days. */
    public function dailyCloses(MarketSeries $series): array
    {
        $lastPerDay = $series->prices()->where('status', 'published')
            ->selectRaw('DATE(source_time) as day, MAX(source_time) as last_time')
            ->groupBy('day')->orderByDesc('day')->limit(60)->pluck('last_time');
        if ($lastPerDay->isEmpty()) {
            return [];
        }

        return $series->prices()->where('status', 'published')->whereIn('source_time', $lastPerDay->all())
            ->orderBy('source_time')->pluck('value')->all();
    }

    private function linearSlope(array $values): float
    {
        $count = count($values);
        if ($count < 2) {
            return 0.0;
        }
        $xMean = ($count - 1) / 2;
        $yMean = array_sum($values) / $count;
        $numerator = $denominator = 0.0;
        foreach ($values as $x => $value) {
            $numerator += ($x - $xMean) * ($value - $yMean);
            $denominator += ($x - $xMean) ** 2;
        }

        return $denominator > 0 ? $numerator / $denominator : 0.0;
    }
}
