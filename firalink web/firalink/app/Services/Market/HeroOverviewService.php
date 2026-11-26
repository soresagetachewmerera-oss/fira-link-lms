<?php

namespace App\Services\Market;

use App\Models\CoffeeListing;
use App\Models\CoffeeMinimumPriceDataset;
use App\Models\MarketSeries;
use App\Services\CoffeeExportService;
use Illuminate\Support\Facades\Cache;

/**
 * Builds the server-side overview model shared by HomeController and GET /api/market/overview.
 * Every figure is backed by database tables, MarketReadService, or CoffeeExportService; never invented.
 */
class HeroOverviewService
{
    public const CACHE_TTL_SECONDS = 600; // 10 minutes (within 5–15 min window)

    public function __construct(
        private readonly MarketFreshnessService $freshness,
        private readonly MarketTrendService $trends,
        private readonly CoffeeExportService $exportCalculator,
    ) {}

    public static function forgetCache(): void
    {
        foreach (MarketReadService::LOCALES as $locale) {
            Cache::forget('hero:overview:v1:' . $locale);
        }
    }

    public function build(): array
    {
        return Cache::remember('hero:overview:v1:' . app()->getLocale(), self::CACHE_TTL_SECONDS, function () {
            $coffeeMarket = $this->coffeeMarketCard();
            $ecta = $this->ectaStats();
            $verifiedLots = $this->verifiedLotsCard();
            $landedCost = $this->landedCostCard();
            $firaAi = $this->firaAiCard();
            $freeToolsCount = $this->enabledFreeToolsCount();

            return [
                'cards' => [
                    'coffee_market' => $coffeeMarket,
                    'verified_lots' => $verifiedLots,
                    'landed_cost' => $landedCost,
                    'fira_ai' => $firaAi,
                ],
                'floating_ecta' => $ecta,
                'proof_band' => $this->buildProofBand($coffeeMarket, $ecta, $freeToolsCount),
                'source' => 'Fira Link Platform server overview',
                'as_of' => now()->toIso8601String(),
                'status' => 'ok',
            ];
        });
    }

    private function coffeeMarketCard(): array
    {
        try {
            $series = MarketSeries::query()
                ->where('symbol', 'ICO_COMPOSITE')
                ->where('enabled', true)
                ->with(['source', 'trend'])
                ->first();
        } catch (\Throwable) {
            $series = null;
        }

        if (!$series || !$series->source) {
            return [
                'status' => 'unavailable',
                'value' => null,
                'display_value' => 'Unavailable',
                'unit' => 'US cents/lb',
                'unit_short' => 'US¢/lb',
                'trend' => null,
                'cadence' => 'Daily close',
                'status_text' => 'Unavailable',
                'source' => 'International Coffee Organization (ICO)',
                'as_of' => null,
                'as_of_date' => null,
                'sparkline' => null,
            ];
        }

        $price = $series->prices()
            ->where('status', 'published')
            ->latest('source_time')
            ->first();

        $fresh = $this->freshness->for($series, $price);
        $sourceName = $series->source->attribution ?: $series->source->name;
        $status = match ($fresh['status']) {
            'unavailable' => 'unavailable',
            'stale' => 'stale',
            default => 'available',
        };

        if ($status === 'unavailable' || !$price) {
            return [
                'status' => 'unavailable',
                'value' => null,
                'display_value' => 'Unavailable',
                'unit' => 'US cents/lb',
                'unit_short' => 'US¢/lb',
                'trend' => null,
                'cadence' => 'Daily close',
                'status_text' => 'Unavailable',
                'source' => $sourceName,
                'as_of' => null,
                'as_of_date' => null,
                'sparkline' => null,
            ];
        }

        $dailyCloses = array_slice($this->trends->dailyCloses($series), -30);
        $sparkline = HeroSparklineBuilder::buildPath($dailyCloses);
        $trendWord = HeroSparklineBuilder::trendWordFromLabel($series->trend?->trend)
            ?? HeroSparklineBuilder::trendWordFromSeries($dailyCloses);

        $date = $price->source_time->utc()->format('Y-m-d');
        $cadenceDate = 'Daily close · ' . $date;
        if ($status === 'stale') {
            $statusText = 'Stale · ' . $date;
        } else {
            $statusText = $trendWord ? "{$trendWord} · Daily close · {$date}" : $cadenceDate;
        }

        return [
            'status' => $status,
            'value' => round((float) $price->value, 2),
            'display_value' => number_format((float) $price->value, 2),
            'unit' => 'US cents/lb',
            'unit_short' => 'US¢/lb',
            'trend' => $trendWord,
            'cadence' => 'Daily close',
            'status_text' => $statusText,
            'source' => $sourceName,
            'as_of' => $price->source_time->toIso8601String(),
            'as_of_date' => $date,
            'sparkline' => $sparkline,
        ];
    }

    private function verifiedLotsCard(): array
    {
        $marketplaceLaunched = (bool) config('marketplace.launched', true);
        try {
            $query = CoffeeListing::query()->where('status', 'active')->visibleToBuyers();
            $count = $marketplaceLaunched ? (int) $query->count() : 0;
            $latestAt = $count > 0 ? $query->max('updated_at') : null;
            $regionBars = $count > 0
                ? CoffeeListing::query()
                    ->where('status', 'active')
                    ->visibleToBuyers()
                    ->selectRaw('origin_region, COUNT(*) as c')
                    ->groupBy('origin_region')
                    ->orderByDesc('c')
                    ->limit(5)
                    ->pluck('c')
                    ->map(fn ($v) => (int) $v)
                    ->values()
                    ->all()
                : [];
        } catch (\Throwable) {
            $count = 0;
            $latestAt = null;
            $regionBars = [];
        }

        if ($count === 0) {
            return [
                'status' => 'pre_launch',
                'mode' => 'browse_fallback',
                'value' => null,
                'display_value' => 'Coffee B2B',
                'subtitle' => 'Browse verified lots',
                'status_text' => 'Verified exporter directory',
                'bars' => [],
                'source' => 'Fira Link Marketplace',
                'as_of' => now()->toIso8601String(),
            ];
        }

        $maxBar = max(1, ...$regionBars);
        $barsNormalized = array_map(fn (int $c) => round($c / $maxBar, 3), $regionBars);

        return [
            'status' => 'available',
            'mode' => 'count',
            'value' => $count,
            'display_value' => number_format($count),
            'subtitle' => 'Published & verified exporters',
            'status_text' => 'Verified tier · Active lots',
            'bars' => $barsNormalized,
            'source' => 'Fira Link Marketplace',
            'as_of' => $latestAt ? \Illuminate\Support\Carbon::parse($latestAt)->toIso8601String() : now()->toIso8601String(),
        ];
    }

    private function landedCostCard(): array
    {
        $calc = $this->exportCalculator->calculateProfit([]);
        $netKg = max(1.0, (float) ($calc['net_weight_kg'] ?? 1));
        $breakdown = $calc['cost_breakdown_etb'] ?? [];
        $totalCostEtb = max(1.0, (float) ($breakdown['total_cost'] ?? 0));
        $etbPerKg = round($totalCostEtb / $netKg, 2);
        $incoterm = (string) ($calc['incoterm'] ?? 'FOB');

        $shares = [
            round(((float) ($breakdown['purchase_cost'] ?? 0) / $totalCostEtb) * 100, 1),
            round((((float) ($breakdown['processing_cost'] ?? 0) + (float) ($breakdown['packaging_cost'] ?? 0)) / $totalCostEtb) * 100, 1),
            round((((float) ($breakdown['transport_corridor'] ?? 0) + (float) ($breakdown['handling_customs'] ?? 0)) / $totalCostEtb) * 100, 1),
        ];

        return [
            'status' => 'available',
            'value' => $etbPerKg,
            'display_value' => number_format($etbPerKg, 0),
            'unit' => 'ETB/kg',
            'incoterm' => $incoterm,
            'status_text' => "Example shipment · {$incoterm}",
            'cost_shares' => $shares,
            'source' => 'Fira Link Export Profit Calculator',
            'as_of' => now()->toIso8601String(),
        ];
    }

    private function firaAiCard(): array
    {
        return [
            'status' => 'available',
            'feature_line' => 'Ask about prices and export steps',
            'chip' => 'Sources named',
            'eval_score' => null,
            'source' => 'Fira AI Market Snapshot',
            'as_of' => now()->toIso8601String(),
        ];
    }

    private function ectaStats(): ?array
    {
        try {
            $dataset = CoffeeMinimumPriceDataset::query()
                ->where('status', 'published')
                ->latest('effective_date')
                ->first();
        } catch (\Throwable) {
            return null;
        }

        if (!$dataset) {
            return null;
        }

        $snap = $dataset->computeSnapshot();
        if (($snap['records_count'] ?? 0) <= 0) {
            return null;
        }

        return [
            'status' => 'available',
            'title' => 'ECTA schedule updated',
            'effective_date' => $snap['effective_date'],
            'spec_count' => (int) $snap['records_count'],
            'lowest_price' => $snap['lowest_price'],
            'highest_price' => $snap['highest_price'],
            'unit' => $snap['unit'] ?? 'USD/lb',
            'source' => $dataset->source ?: 'Ethiopian Coffee and Tea Authority (ECTA)',
            'as_of' => $dataset->updated_at?->toIso8601String(),
        ];
    }

    private function enabledFreeToolsCount(): int
    {
        return count(array_filter(
            (array) config('fira.free_tools', []),
            fn ($item) => is_array($item) && ($item['enabled'] ?? false) === true
        ));
    }

    /**
     * Proof band shows 3 to 5 non-missing stats from stored data.
     *
     * @return array<int, array{key: string, value: string, unit: string, label: string, sublabel: string, source: string, as_of: ?string}>
     */
    private function buildProofBand(array $coffeeMarket, ?array $ecta, int $freeToolsCount): array
    {
        $stats = [];

        if ($coffeeMarket['status'] !== 'unavailable' && $coffeeMarket['value'] !== null) {
            $stats[] = [
                'key' => 'ico',
                'value' => number_format((float) $coffeeMarket['value'], 1),
                'unit' => 'US¢/lb',
                'label' => 'ICO composite',
                'sublabel' => $coffeeMarket['status'] === 'stale'
                    ? "ICO · Stale ({$coffeeMarket['as_of_date']})"
                    : "ICO · {$coffeeMarket['cadence']} ({$coffeeMarket['as_of_date']})",
                'source' => $coffeeMarket['source'],
                'as_of' => $coffeeMarket['as_of'],
            ];
        }

        if ($ecta) {
            if ($ecta['lowest_price'] !== null) {
                $stats[] = [
                    'key' => 'ecta_low',
                    'value' => '$' . number_format((float) $ecta['lowest_price'], 2),
                    'unit' => 'USD/lb',
                    'label' => 'ECTA lowest floor',
                    'sublabel' => "ECTA · {$ecta['effective_date']}",
                    'source' => $ecta['source'],
                    'as_of' => $ecta['as_of'],
                ];
            }
            if ($ecta['highest_price'] !== null) {
                $stats[] = [
                    'key' => 'ecta_high',
                    'value' => '$' . number_format((float) $ecta['highest_price'], 2),
                    'unit' => 'USD/lb',
                    'label' => 'ECTA highest ceiling',
                    'sublabel' => "ECTA · {$ecta['effective_date']}",
                    'source' => $ecta['source'],
                    'as_of' => $ecta['as_of'],
                ];
            }
            if ($ecta['spec_count'] > 0) {
                $stats[] = [
                    'key' => 'ecta_specs',
                    'value' => number_format($ecta['spec_count']),
                    'unit' => 'specs',
                    'label' => 'ECTA price specs',
                    'sublabel' => "ECTA · {$ecta['effective_date']}",
                    'source' => $ecta['source'],
                    'as_of' => $ecta['as_of'],
                ];
            }
        }

        if ($freeToolsCount > 0) {
            $stats[] = [
                'key' => 'free_tools',
                'value' => (string) $freeToolsCount,
                'unit' => 'tools',
                'label' => 'Free trade tools',
                'sublabel' => 'Calculator, market & readiness',
                'source' => 'Fira Link Free Tools',
                'as_of' => now()->toIso8601String(),
            ];
        }

        return $stats;
    }
}
