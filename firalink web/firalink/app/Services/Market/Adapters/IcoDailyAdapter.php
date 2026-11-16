<?php

namespace App\Services\Market\Adapters;

use App\Models\MarketSeries;
use App\Models\MarketSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * ICO daily indicator prices (I-CIP and group prices), US cents per lb, one row per business day.
 * Live shape (checked 2026-11-16): {"daily":[{"date":"2026-11-12","ICIP":249.09,...}], "units":"US cents/lb", ...}.
 * Returns every row in the response, oldest first, so history and trends fill from the first fetch;
 * rows already stored are skipped by the ingestion service.
 */
class IcoDailyAdapter implements MarketSourceAdapter
{
    public const UNITS = 'US cents/lb';

    public function fetch(MarketSource $source): array
    {
        $response = Http::acceptJson()
            ->timeout((int) ($source->configuration['timeout_seconds'] ?? config('market.http_timeout_seconds')))
            ->retry((array) config('market.http_backoff_ms', [500, 2000]), throw: false)
            ->get($source->url);

        if (!$response->successful()) {
            throw new RuntimeException('ICO returned HTTP ' . $response->status() . '.');
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            throw new RuntimeException('ICO response was not JSON data.');
        }
        if (isset($payload['units']) && trim((string) $payload['units']) !== self::UNITS) {
            throw new RuntimeException('ICO units changed to "' . substr((string) $payload['units'], 0, 40) . '"; nothing stored.');
        }

        $rows = $this->rows($payload);
        $series = MarketSeries::query()->where('source_id', $source->id)->where('enabled', true)->get();
        $hash = hash('sha256', $response->body());
        $quotes = [];

        foreach ($rows as $row) {
            $date = $row['date'] ?? null;
            if (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                continue;
            }
            $sourceTime = CarbonImmutable::parse($date, 'UTC')->startOfDay()->toIso8601String();
            foreach ($series as $item) {
                $field = $item->configuration['field'] ?? null;
                $value = $field ? ($row[$field] ?? null) : null;
                if ($value === null || is_array($value)) {
                    continue; // field missing that day: nothing stored, nothing guessed
                }
                // Non-numeric, zero or negative values go on to validation, which rejects and logs them.
                $quotes[] = [
                    'symbol' => $item->symbol,
                    'value' => (string) $value,
                    'currency' => 'USX',
                    'unit' => 'lb',
                    'source_time' => $sourceTime,
                    'raw_ref' => ['field' => $field, 'response_sha256' => $hash],
                ];
            }
        }

        if ($quotes === []) {
            throw new RuntimeException('ICO response contained no recognized indicator rows.');
        }

        return $quotes;
    }

    /** @return array<int, array> rows oldest first */
    private function rows(array $payload): array
    {
        foreach (['daily', 'data', 'prices', 'results'] as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                $payload = $payload[$key];
                break;
            }
        }
        if (!array_is_list($payload)) {
            $payload = [$payload];
        }
        $rows = array_values(array_filter($payload, 'is_array'));
        if ($rows === []) {
            throw new RuntimeException('ICO response contained no rows.');
        }
        usort($rows, fn (array $a, array $b) => strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? '')));

        return $rows;
    }
}
