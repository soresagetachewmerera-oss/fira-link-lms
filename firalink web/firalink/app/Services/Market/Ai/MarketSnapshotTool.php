<?php

namespace App\Services\Market\Ai;

use App\Services\Market\MarketReadService;

/**
 * Server-side tool get_market_snapshot: the stored, published market rows with source, source time
 * and freshness. Fira AI may state a price only if it is in this result. Rows without a current value
 * carry value null and freshness "unavailable".
 */
class MarketSnapshotTool
{
    public const NAME = 'get_market_snapshot';

    public function __construct(private readonly MarketReadService $market) {}

    public function snapshot(): array
    {
        return self::fromOverview($this->market->overview());
    }

    public static function fromOverview(array $overview): array
    {
        $rows = [];
        foreach ($overview['benchmarks'] ?? [] as $b) {
            $status = $b['freshness']['status'] ?? 'unavailable';
            $hasValue = $b['value'] !== null && $status !== 'unavailable';
            $rows[] = [
                'symbol' => $b['symbol'],
                'name' => $b['name'],
                'value' => $hasValue ? rtrim(rtrim((string) $b['value'], '0'), '.') : null,
                'unit' => self::unitLabel($b['currency'], $b['unit']),
                'price_type' => $b['price_type'],
                'freshness' => $status,
                'freshness_label' => $b['freshness']['label'] ?? '',
                'source' => $b['source']['attribution'] ?? $b['source']['name'] ?? '',
                'source_time' => $hasValue ? ($b['source_time'] ?? null) : null,
                'trend' => $b['trend']['label'] ?? 'not_enough_data',
            ];
        }

        return ['tool' => self::NAME, 'as_of' => $overview['generated_at'] ?? now()->toIso8601String(), 'rows' => $rows];
    }

    public static function unitLabel(string $currency, string $unit): string
    {
        return match ($currency . '/' . $unit) {
            'USX/lb' => 'US cents/lb',
            'USD/lb' => 'USD/lb',
            'ETB/feresula_17kg' => 'ETB per feresula (17 kg)',
            default => $currency . '/' . $unit,
        };
    }

    /** Rows that have a current value. */
    public static function withValues(array $snapshot): array
    {
        return array_values(array_filter($snapshot['rows'] ?? [], fn ($r) => $r['value'] !== null));
    }
}
