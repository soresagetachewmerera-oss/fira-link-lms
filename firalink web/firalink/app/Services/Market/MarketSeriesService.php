<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Detail data for one instrument (schema 1): points for a range, statistics and an optional unit
 * conversion. Points are stored, published prices only (one close per day; every price for 1D).
 */
class MarketSeriesService
{
    public const RANGES = ['1D', '1W', '1M', '3M', '1Y', '5Y'];

    public function __construct(
        private readonly MarketWatchService $watch,
        private readonly UnitConverter $units,
    ) {}

    public function rangesFor(MarketSeries $series): array
    {
        $intraday = $series->supports_intraday && $series->source?->enabled;

        return array_values(array_filter(self::RANGES, fn ($r) => $r !== '1D' || $intraday));
    }

    public function build(MarketSeries $series, string $range, ?string $unit = null): array
    {
        $series->loadMissing(['source', 'trend']);
        $key = sprintf('market:series:v1:%s:%s:%s:%s', $series->symbol, $range, $unit ?? 'native', app()->getLocale());

        return Cache::remember($key, (int) config('market.watch.cache_seconds', 60), fn () => $this->compute($series, $range, $unit));
    }

    private function compute(MarketSeries $series, string $range, ?string $unit): array
    {
        $row = $this->watch->row($series);
        $native = UnitConverter::nativeUnit($series);
        $target = $unit ?? $native;
        $factor = $target === null ? 1.0 : $this->units->factor($series, $target);
        if ($factor === null) {
            $target = $native; // not possible right now (no current NBE rate): stay in the stored unit
            $factor = 1.0;
        }
        // The NBE rate is part of the answer whenever birr and dollars are mixed.
        $mixesCurrencies = $target !== $native
            && !($native === 'etb_feresula' && $target === 'etb_kg')
            && ($target === 'etb_kg' || in_array($native, ['etb_kg', 'etb_feresula'], true));
        $decimals = $target === null || $target === $native ? (int) $series->decimals : UnitConverter::decimalsFor($target, (int) $series->decimals);
        $conv = fn (?float $v) => $v === null ? null : round($v * $factor, $decimals);
        $available = $row['price'] !== null;

        $points = $available ? $this->points($series, $range) : [];
        $closes = $available ? $this->dailyCloses($series, now()->subYear()->startOfDay()) : [];
        $last20 = array_slice($closes, -20);

        return [
            'schema' => 1,
            'key' => $series->symbol,
            'name' => $row['name'],
            'short_name' => $row['short_name'],
            'range' => $range,
            'ranges' => $this->rangesFor($series),
            'unit' => ['key' => $target, 'label' => $target && $target !== $native ? UnitConverter::label($target) : $row['unit_label']],
            'units' => array_map(fn ($u) => ['key' => $u, 'label' => $u === $native ? $row['unit_label'] : UnitConverter::label($u)], $this->units->unitsFor($series)),
            'fx' => $mixesCurrencies ? $this->units->fx() : null,
            'decimals' => $decimals,
            'status' => $row['status'],
            'status_label' => $row['status_label'],
            'as_of' => $row['as_of'],
            'source' => $row['source'],
            'price' => $conv($row['price']),
            'change' => $conv($row['change']),
            'change_pct' => $row['change_pct'],
            'direction' => $row['direction'],
            'points' => array_map(fn (array $p) => ['t' => $p['t'], 'v' => $conv($p['v'])], $points),
            'stats' => [
                'prev_close' => $conv($row['prev_close']),
                'high_52w' => $closes ? $conv(max(array_column($closes, 'v'))) : null,
                'low_52w' => $closes ? $conv(min(array_column($closes, 'v'))) : null,
                'avg_20d' => count($last20) >= 20 ? $conv(array_sum(array_column($last20, 'v')) / count($last20)) : null,
            ],
            'disclaimer' => __('market.watch.disclaimer'),
        ];
    }

    /** @return array<int, array{t: string, v: float}> */
    private function points(MarketSeries $series, string $range): array
    {
        $since = match ($range) {
            '1D' => now()->subDay(),
            '1W' => now()->subWeek(),
            '1M' => now()->subMonth(),
            '3M' => now()->subMonths(3),
            '1Y' => now()->subYear(),
            '5Y' => now()->subYears(5),
        };
        if ($range === '1D') {
            return $series->prices()->where('status', 'published')->where('source_time', '>=', $since)->orderBy('source_time')
                ->get(['value', 'source_time'])
                ->map(fn (MarketPrice $p) => ['t' => $p->source_time->toIso8601String(), 'v' => (float) $p->value])->all();
        }

        return $this->dailyCloses($series, CarbonImmutable::instance($since));
    }

    /** Last published price of each day since $since, oldest first. */
    private function dailyCloses(MarketSeries $series, \DateTimeInterface $since): array
    {
        $lastPerDay = $series->prices()->where('status', 'published')->where('source_time', '>=', $since)
            ->selectRaw('DATE(source_time) as day, MAX(source_time) as last_time')->groupBy('day')->pluck('last_time');
        if ($lastPerDay->isEmpty()) {
            return [];
        }

        return $series->prices()->where('status', 'published')->whereIn('source_time', $lastPerDay->all())->orderBy('source_time')
            ->get(['value', 'source_time'])
            ->map(fn (MarketPrice $p) => ['t' => $p->source_time->toIso8601String(), 'v' => (float) $p->value])->all();
    }
}
