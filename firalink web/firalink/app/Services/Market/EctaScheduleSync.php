<?php

namespace App\Services\Market;

use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * ECTA minimum export prices for the market watch, from the price lists an admin uploaded and PUBLISHED
 * (coffee_minimum_price_datasets). One market series per tracked grade (coffee type, origin, grade, processing).
 * Each effective date becomes one stored price, validated like any other source. A price whose list is
 * no longer published is withdrawn, so it is never shown again.
 */
class EctaScheduleSync
{
    public const SOURCE = 'ecta_lists';
    public const FIELDS = ['coffee_type', 'origin', 'grade', 'processing_method'];

    public function __construct(
        private readonly MarketValidationService $validator,
        private readonly MarketTrendService $trends,
    ) {}

    /** Grades found in published lists, with their latest price and date. */
    public function grades(): Collection
    {
        return CoffeeMinimumPriceRecord::query()
            ->whereIn('dataset_id', $this->publishedDatasetIds())
            ->where('status', 'valid')
            ->orderBy('effective_date')->orderBy('id')
            ->get()
            ->groupBy(fn (CoffeeMinimumPriceRecord $r) => self::descriptorKey($r->only(self::FIELDS)))
            ->map(function (Collection $records, string $key) {
                $last = $records->last();

                return $last->only(self::FIELDS) + [
                    'key' => $key,
                    'symbol' => self::symbolFor($last->only(self::FIELDS)),
                    'latest_price' => (float) $last->minimum_price,
                    'price_unit' => $last->price_unit,
                    'effective_date' => $last->effective_date?->toDateString(),
                ];
            })->values();
    }

    public static function descriptorKey(array $descriptor): string
    {
        return implode('|', array_map(fn ($f) => trim((string) ($descriptor[$f] ?? '')), self::FIELDS));
    }

    public static function symbolFor(array $descriptor): string
    {
        return 'ECTA_' . strtoupper(substr(sha1(Str::lower(self::descriptorKey($descriptor))), 0, 10));
    }

    /** Start tracking a grade (creates its series). With $home it becomes the ECTA row on Home. */
    public function track(array $descriptor, bool $home = false): MarketSeries
    {
        $descriptor = array_map(fn ($v) => trim((string) $v), array_intersect_key($descriptor, array_flip(self::FIELDS)));
        $source = MarketSource::query()->where('slug', self::SOURCE)->firstOrFail();
        $label = trim("{$descriptor['origin']} {$descriptor['grade']}");

        $series = MarketSeries::query()->firstOrCreate(['symbol' => self::symbolFor($descriptor)], [
            'source_id' => $source->id,
            'name' => Str::limit("ECTA minimum: {$descriptor['origin']} grade {$descriptor['grade']}, {$descriptor['processing_method']} {$descriptor['coffee_type']}", 250, ''),
            'short_name' => Str::limit("ECTA {$label}", 40, ''),
            'price_type' => 'weekly_regulatory_floor',
            'currency' => 'USD', 'unit' => 'lb', 'decimals' => 2, 'supports_intraday' => false,
            'minimum_value' => 0.25, 'maximum_value' => 20, 'maximum_change_percent' => 25,
            'is_hero' => false, 'on_home' => false, 'enabled' => true, 'sort_order' => 20,
            'configuration' => ['ecta' => $descriptor],
        ]);

        if ($home) {
            MarketSeries::query()->where('source_id', $source->id)->where('id', '!=', $series->id)->update(['on_home' => false]);
            $series->forceFill(['on_home' => true])->save();
        }
        MarketReadService::forgetCache();

        return $series;
    }

    /** @return array<int, array> one result per stored or checked date */
    public function syncAll(bool $dryRun = false): array
    {
        $source = MarketSource::query()->where('slug', self::SOURCE)->first();
        if (!$source) {
            return [];
        }

        return MarketSeries::query()->where('source_id', $source->id)->where('enabled', true)->get()
            ->flatMap(fn (MarketSeries $series) => $this->sync($series, $dryRun))
            ->values()->all();
    }

    public function sync(MarketSeries $series, bool $dryRun = false): array
    {
        $descriptor = $series->configuration['ecta'] ?? null;
        if (!is_array($descriptor)) {
            return [];
        }
        $published = $this->publishedDatasetIds();
        $rows = [];

        // A list that is no longer published takes its prices with it.
        foreach ($series->prices()->whereIn('status', ['published', 'needs_review'])->get() as $price) {
            if (!in_array((int) ($price->raw_ref['dataset_id'] ?? 0), $published, true)) {
                $rows[] = ['symbol' => $series->symbol, 'source_time' => $price->source_time->toDateString(), 'value' => (string) $price->value, 'currency' => $series->currency, 'unit' => $series->unit, 'status' => 'withdrawn', 'reason' => 'Its price list is no longer published.'];
                if (!$dryRun) {
                    $price->update(['status' => 'withdrawn', 'review_reason' => 'Its price list is no longer published.']);
                }
            }
        }

        $query = CoffeeMinimumPriceRecord::query()->whereIn('dataset_id', $published)->where('status', 'valid');
        foreach (self::FIELDS as $field) {
            $query->where($field, $descriptor[$field] ?? '');
        }
        // One value per effective date: the most recently uploaded list wins.
        $byDate = $query->orderBy('effective_date')->orderBy('dataset_id')->orderBy('id')->get()
            ->groupBy(fn (CoffeeMinimumPriceRecord $r) => $r->effective_date->toDateString())
            ->map(fn (Collection $records) => $records->last());

        foreach ($byDate as $date => $record) {
            $sourceTime = CarbonImmutable::parse($date, 'UTC')->startOfDay();
            $quote = [
                'symbol' => $series->symbol,
                'value' => (string) $record->minimum_price,
                'currency' => (string) $record->currency,
                'unit' => $record->price_unit === 'USD/lb' ? 'lb' : (string) $record->price_unit,
                'source_time' => $sourceTime->toIso8601String(),
                'raw_ref' => ['dataset_id' => (int) $record->dataset_id, 'record_id' => (int) $record->id, 'effective_date' => $date],
            ];
            $row = ['symbol' => $series->symbol, 'source_time' => $date, 'value' => $quote['value'], 'currency' => $quote['currency'], 'unit' => $quote['unit']];

            $existing = $series->prices()->where('source_time', $sourceTime)->first();
            if ($existing && ($existing->raw_ref['record_id'] ?? null) === $quote['raw_ref']['record_id'] && $existing->status !== 'withdrawn') {
                $rows[] = $row + ['status' => 'duplicate', 'reason' => 'Already stored from this list.'];
                continue;
            }

            $check = $this->validator->validate($series, $quote);
            $rows[] = $row + ['status' => $check['status'], 'reason' => $check['reason']];
            if ($dryRun || $check['status'] === 'rejected') {
                continue;
            }
            $values = [
                'value' => $quote['value'], 'currency' => $quote['currency'], 'unit' => $quote['unit'],
                'fetched_at' => now(), 'status' => $check['status'], 'raw_ref' => $quote['raw_ref'],
                'review_reason' => $check['reason'], 'reviewed_by' => null, 'reviewed_at' => null,
            ];
            $existing
                ? $existing->update($values)
                : MarketPrice::query()->create($values + ['series_id' => $series->id, 'source_time' => $sourceTime]);
        }

        if (!$dryRun) {
            $this->trends->compute($series);
            MarketReadService::forgetCache();
        }

        return $rows;
    }

    /**
     * Called when an ECTA price list is saved or deleted (AppServiceProvider). Syncs only while the source is on;
     * the daily market:fetch run catches anything missed (for example records added after the list was saved).
     */
    public static function onListChanged(CoffeeMinimumPriceDataset $dataset, bool $deleted = false): void
    {
        if (!$deleted && !$dataset->wasChanged('status') && !$dataset->wasRecentlyCreated && $dataset->status !== 'published') {
            return;
        }
        if (!MarketSource::query()->where('slug', self::SOURCE)->where('enabled', true)->exists()) {
            return;
        }
        try {
            app(self::class)->syncAll();
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('market.ecta_sync_failed', ['dataset' => $dataset->id, 'error' => Str::limit($e->getMessage(), 300)]);
        }
    }

    /** @return array<int, int> */
    private function publishedDatasetIds(): array
    {
        return CoffeeMinimumPriceDataset::query()->where('status', 'published')->pluck('id')->map(fn ($id) => (int) $id)->all();
    }
}
