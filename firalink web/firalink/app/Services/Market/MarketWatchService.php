<?php

namespace App\Services\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Lang;

/**
 * Market watch rows (schema 1): the one place that builds what the API, the website and the app show.
 * Every number comes from a published market_prices row. An unavailable row carries no number at all.
 * Previous close is the last stored close on an earlier day; change, direction and the sparkline follow from it.
 */
class MarketWatchService
{
    public const SCHEMA = 1;

    private const TREND = ['bullish' => 'up', 'bearish' => 'down', 'sideways' => 'sideways'];

    public function __construct(private readonly MarketFreshnessService $freshness) {}

    public static function cacheKey(string $locale): string
    {
        return 'market:watch:v' . self::SCHEMA . ':' . $locale;
    }

    public function build(): array
    {
        return Cache::remember(
            self::cacheKey(app()->getLocale()),
            (int) config('market.watch.cache_seconds', 60),
            fn () => $this->compute(),
        );
    }

    private function compute(): array
    {
        $rows = MarketSeries::query()
            ->where('enabled', true)
            ->with(['source', 'trend'])
            ->orderBy('sort_order')->orderBy('id')
            ->get()
            ->map(fn (MarketSeries $series) => $this->row($series))
            ->values();

        $priced = $rows->filter(fn (array $row) => $row['price'] !== null);
        $latest = $priced->sortByDesc('as_of')->first();
        $homeKeys = $rows->where('on_home', true)->take((int) config('market.watch.home_rows', 4))->pluck('key')->values();

        return [
            'schema' => self::SCHEMA,
            'generated_at' => now()->toIso8601String(),
            'status' => $latest
                ? ['status' => $latest['status'], 'label' => $latest['status_label']]
                : ['status' => 'unavailable', 'label' => __('market.freshness.unavailable')],
            'sources' => $priced->pluck('source.attribution')->filter()->unique()->values()->all(),
            'disclaimer' => __('market.watch.disclaimer'),
            'home' => [
                // Home shows the card only once one of its rows has a real value.
                'visible' => $rows->whereIn('key', $homeKeys)->contains(fn (array $row) => $row['price'] !== null),
                'keys' => $homeKeys->all(),
            ],
            'rows' => $rows->all(),
        ];
    }

    public function row(MarketSeries $series): array
    {
        $decimals = (int) ($series->decimals ?? 2);
        $points = (int) config('market.watch.sparkline_points', 30);
        $latest = $series->prices()->where('status', 'published')->latest('source_time')->first();
        $freshness = $this->freshness->for($series, $latest);
        $available = $latest !== null && $freshness['status'] !== 'unavailable';

        $price = $prev = $change = $changePct = $direction = null;
        $sparkline = [];
        if ($available) {
            $closes = $this->dailyCloses($series, $points + 1);
            $price = round((float) $latest->value, $decimals);
            $latestDay = $latest->source_time->toDateString();
            foreach (array_reverse($closes) as $close) {
                if ($close['day'] < $latestDay) {
                    $prev = round($close['value'], $decimals);
                    break;
                }
            }
            if ($prev !== null) {
                $change = round($price - $prev, $decimals);
                $changePct = $prev > 0 ? round($change / $prev * 100, 2) : null;
                $direction = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat');
            }
            $sparkline = array_map(fn (array $c) => round($c['value'], $decimals), array_slice($closes, -$points));
        }

        $name = $this->text("market.watch.names.{$series->symbol}", $series->name);
        $shortName = $this->text("market.watch.short.{$series->symbol}", $series->short_name ?: $series->name);
        $unitKey = $series->currency . '_' . $series->unit;

        $row = [
            'key' => $series->symbol,
            'name' => $name,
            'short_name' => $shortName,
            'kind' => $series->price_type,
            'currency' => $series->currency,
            'unit' => $series->unit,
            'unit_label' => $this->text("market.watch.units.{$unitKey}", $series->currency . '/' . $series->unit),
            'decimals' => $decimals,
            'price' => $price,
            'prev_close' => $prev,
            'change' => $change,
            'change_pct' => $changePct,
            'direction' => $direction,
            'trend' => $available && $series->trend ? (self::TREND[$series->trend->trend] ?? null) : null,
            'status' => $freshness['status'],
            // A stale price is shown muted with its own date, not with an age in minutes.
            'status_label' => $freshness['status'] === 'stale' && $available
                ? __('market.watch.stale', ['date' => $latest->source_time->toDateString()])
                : $freshness['label'],
            'as_of' => $available ? $latest->source_time->toIso8601String() : null,
            'source' => [
                'name' => $series->source->name,
                'attribution' => $series->source->attribution,
            ],
            'sparkline' => $sparkline,
            'spark_ref' => $prev,
            'on_home' => (bool) $series->on_home,
        ];
        $row['a11y_label'] = $this->spoken($row, $unitKey);

        return $row;
    }

    /** Last published value of each day (UTC), oldest first. @return array<int, array{day: string, value: float}> */
    private function dailyCloses(MarketSeries $series, int $days): array
    {
        $lastPerDay = $series->prices()->where('status', 'published')
            ->selectRaw('DATE(source_time) as day, MAX(source_time) as last_time')
            ->groupBy('day')->orderByDesc('day')->limit($days)->pluck('last_time');
        if ($lastPerDay->isEmpty()) {
            return [];
        }

        return $series->prices()->where('status', 'published')->whereIn('source_time', $lastPerDay->all())
            ->orderBy('source_time')->get(['value', 'source_time'])
            ->map(fn (MarketPrice $p) => ['day' => $p->source_time->toDateString(), 'value' => (float) $p->value])
            ->all();
    }

    /** One sentence for screen readers, e.g. "ICO composite, 247.26 US cents per pound, up 1.84, 0.75 percent, Daily close · 2026-11-26." */
    private function spoken(array $row, string $unitKey): string
    {
        if ($row['price'] === null) {
            return __('market.watch.a11y.unavailable', ['name' => $row['short_name']]);
        }
        $format = fn (float $v, int $d) => number_format(abs($v), $d, '.', ',');
        $change = match ($row['direction']) {
            'up' => __('market.watch.a11y.up', ['change' => $format($row['change'], $row['decimals']), 'pct' => $format((float) $row['change_pct'], 2)]),
            'down' => __('market.watch.a11y.down', ['change' => $format($row['change'], $row['decimals']), 'pct' => $format((float) $row['change_pct'], 2)]),
            'flat' => __('market.watch.a11y.flat'),
            default => __('market.watch.a11y.no_previous'),
        };

        return __('market.watch.a11y.available', [
            'name' => $row['short_name'],
            'price' => $format($row['price'], $row['decimals']),
            'unit' => $this->text("market.watch.units_spoken.{$unitKey}", $row['unit_label']),
            'change' => $change,
            'status' => $row['status_label'],
        ]);
    }

    private function text(string $key, string $fallback): string
    {
        return Lang::has($key) ? __($key) : $fallback;
    }
}
