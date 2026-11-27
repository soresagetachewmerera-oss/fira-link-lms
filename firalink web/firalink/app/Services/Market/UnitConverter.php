<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;

/**
 * Converts coffee prices between US cents/lb, USD/lb, USD/kg and ETB/kg.
 * ETB needs the NBE birr/US dollar rate: it is offered only while a current (not stale, not unavailable)
 * published rate exists, and every converted answer carries that rate, its source and its date.
 */
class UnitConverter
{
    public const LB_PER_KG = 2.20462262185;
    public const UNITS = ['usc_lb', 'usd_lb', 'usd_kg', 'etb_kg'];
    public const FX_SYMBOL = 'ETB_USD';

    private ?array $fx = null;
    private bool $fxLoaded = false;

    public function __construct(private readonly MarketFreshnessService $freshness) {}

    /** The unit key a series is stored in, or null when it is not a coffee price per weight (the FX rate itself). */
    public static function nativeUnit(MarketSeries $series): ?string
    {
        return match ($series->currency . '/' . $series->unit) {
            'USX/lb' => 'usc_lb',
            'USD/lb' => 'usd_lb',
            'USD/kg' => 'usd_kg',
            'ETB/kg' => 'etb_kg',
            'ETB/feresula_17kg' => 'etb_feresula',
            default => null,
        };
    }

    /** Units this series can be shown in right now. */
    public function unitsFor(MarketSeries $series): array
    {
        $native = self::nativeUnit($series);
        if ($native === null) {
            return [];
        }
        $usd = ['usc_lb', 'usd_lb', 'usd_kg'];
        $hasFx = $this->fx() !== null;
        $units = $native === 'etb_feresula' || $native === 'etb_kg'
            ? array_merge(['etb_kg'], $hasFx ? $usd : [])
            : array_merge($usd, $hasFx ? ['etb_kg'] : []);

        return array_values(array_unique($units));
    }

    /** Current NBE rate (birr per US dollar) with its source and date, or null. */
    public function fx(): ?array
    {
        if ($this->fxLoaded) {
            return $this->fx;
        }
        $this->fxLoaded = true;
        $series = MarketSeries::query()->where('symbol', self::FX_SYMBOL)->where('enabled', true)->with('source')->first();
        $price = $series?->prices()->where('status', 'published')->latest('source_time')->first();
        if (!$series || !$price) {
            return null;
        }
        $state = $this->freshness->for($series, $price)['status'];
        if (in_array($state, ['stale', 'unavailable'], true)) {
            return null; // never convert with an old rate
        }

        return $this->fx = [
            'rate' => (float) $price->value,
            'as_of' => $price->source_time->toIso8601String(),
            'source' => $series->source->attribution,
        ];
    }

    /** Factor that turns a value in the series' unit into $target, or null when not possible. */
    public function factor(MarketSeries $series, string $target): ?float
    {
        $native = self::nativeUnit($series);
        if ($native === null || !in_array($target, $this->unitsFor($series), true)) {
            return null;
        }
        if ($native === $target) {
            return 1.0;
        }
        // Everything through USD per kg.
        $rate = $this->fx()['rate'] ?? null;
        $toUsdKg = match ($native) {
            'usc_lb' => self::LB_PER_KG / 100,
            'usd_lb' => self::LB_PER_KG,
            'usd_kg' => 1.0,
            'etb_kg' => $rate ? 1 / $rate : null,
            'etb_feresula' => $rate ? 1 / 17 / $rate : null,
        };
        $fromUsdKg = match ($target) {
            'usc_lb' => 100 / self::LB_PER_KG,
            'usd_lb' => 1 / self::LB_PER_KG,
            'usd_kg' => 1.0,
            'etb_kg' => $native === 'etb_feresula' ? null : $rate,
        };
        if ($native === 'etb_feresula' && $target === 'etb_kg') {
            return 1 / 17;
        }

        return $toUsdKg !== null && $fromUsdKg !== null ? $toUsdKg * $fromUsdKg : null;
    }

    public static function decimalsFor(string $unit, int $native): int
    {
        return match ($unit) {
            'usc_lb' => 2,
            'usd_lb', 'usd_kg' => 4,
            'etb_kg' => 2,
            default => $native,
        };
    }

    public static function label(string $unit): string
    {
        return __("market.watch.unit_keys.{$unit}");
    }
}
