<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use Illuminate\Support\Facades\Cache;

class MarketReadService
{
    public const LOCALES = ['en', 'am'];

    public function __construct(private readonly MarketFreshnessService $freshness) {}

    public static function forgetCache(): void
    {
        foreach (self::LOCALES as $locale) {
            Cache::forget('market:overview:v1:' . $locale);
            Cache::forget(MarketWatchService::cacheKey($locale));
        }
        HeroOverviewService::forgetCache();
    }

    public function overview(): array
    {
        return Cache::remember('market:overview:v1:'.app()->getLocale(), 60, function () {
            $series = MarketSeries::query()
                ->where('enabled', true)
                ->with(['source', 'trend'])
                ->orderBy('sort_order')
                ->get();
            $items = $series->map(fn (MarketSeries $item) => $this->serializeSeries($item))->values()->all();
            $hero = collect($items)->first(fn (array $item) => $item['is_hero'] && !empty($item['value']))
                ?? collect($items)->first(fn (array $item) => !empty($item['value']))
                ?? collect($items)->first(fn (array $item) => $item['is_hero'])
                ?? ($items[0] ?? null);

            return [
                'hero' => $hero,
                'benchmarks' => $items,
                'summary' => $this->summary($hero),
                'disclaimer' => __('market.disclaimer'),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }

    public function series(MarketSeries $series, string $range): array
    {
        $since = match ($range) {
            '1D' => now()->subDay(),
            '1W' => now()->subWeek(),
            '1M' => now()->subMonth(),
            '3M' => now()->subMonths(3),
            '1Y' => now()->subYear(),
        };
        $points = $series->prices()
            ->where('status', 'published')
            ->where('source_time', '>=', $since)
            ->orderBy('source_time')
            ->get();

        return [
            'series' => $this->serializeSeries($series->loadMissing(['source', 'trend'])),
            'range' => $range,
            'points' => $points->map(fn (MarketPrice $price) => [
                'value' => $price->value,
                'source_time' => $price->source_time->toIso8601String(),
            ])->all(),
        ];
    }

    public function sources(): array
    {
        return MarketSource::query()->orderBy('name')->get()->map(fn (MarketSource $source) => [
            'slug' => $source->slug,
            'name' => $source->name,
            'kind' => $source->kind,
            'url' => $source->url,
            'enabled' => $source->enabled,
            'attribution' => $source->attribution,
            'last_success_at' => $source->last_success_at?->toIso8601String(),
        ])->all();
    }

    public function serializeSeries(MarketSeries $series): array
    {
        $price = $series->prices()->where('status', 'published')->latest('source_time')->first();
        $freshness = $this->freshness->for($series, $price);

        return [
            'symbol' => $series->symbol,
            'name' => $series->name,
            'price_type' => $series->price_type,
            'value' => $freshness['status'] === 'unavailable' ? null : $price?->value,
            'currency' => $series->currency,
            'unit' => $series->unit,
            'is_hero' => $series->is_hero,
            'source' => [
                'name' => $series->source->name,
                'url' => $series->source->url,
                'attribution' => $series->source->attribution,
            ],
            'source_time' => $price?->source_time?->toIso8601String(),
            'fetched_at' => $price?->fetched_at?->toIso8601String(),
            'freshness' => $freshness,
            'trend' => $series->trend ? [
                'label' => $series->trend->trend,
                'computed_at' => $series->trend->computed_at->toIso8601String(),
                'sample_size' => $series->trend->sample_size,
                'inputs' => $series->trend->inputs,
            ] : ['label' => 'not_enough_data', 'computed_at' => null, 'sample_size' => 0, 'inputs' => null],
        ];
    }

    private function summary(?array $hero): array
    {
        if (!$hero || !$hero['value']) {
            return ['text' => __('market.summary.unavailable'), 'source_rows' => []];
        }

        return [
            'text' => __('market.summary.available', [
                'name' => $hero['name'],
                'value' => $hero['value'],
                'currency' => $hero['currency'],
                'unit' => $hero['unit'],
                'freshness' => $hero['freshness']['label'],
            ]),
            'source_rows' => [$hero['symbol']],
        ];
    }
}
