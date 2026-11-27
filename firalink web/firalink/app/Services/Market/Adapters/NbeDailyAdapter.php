<?php

namespace App\Services\Market\Adapters;

use App\Models\MarketSeries;
use App\Models\MarketSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * National Bank of Ethiopia (NBE) official daily indicative exchange rates.
 * Fetches daily indicative USD/ETB rate for export revenue conversion and statutory calculations.
 * Returns array of quotes: symbol, value, currency (ETB), unit (USD), source_time, raw_ref.
 */
class NbeDailyAdapter implements MarketSourceAdapter
{
    public const SOURCE_SLUG = 'nbe';

    public function fetch(MarketSource $source): array
    {
        $url = $source->url ?: 'https://nbe.gov.et/exchange-rate/';
        $timeout = (int) ($source->configuration['timeout_seconds'] ?? config('market.http_timeout_seconds', 10));

        $response = Http::acceptJson()
            ->timeout($timeout)
            ->retry((array) config('market.http_backoff_ms', [500, 2000]), throw: false)
            ->get($url);

        if (!$response->successful()) {
            throw new RuntimeException('NBE endpoint returned HTTP ' . $response->status() . '.');
        }

        $payload = $response->json();
        $hash = hash('sha256', $response->body());
        $now = CarbonImmutable::now('UTC');

        // Parse rate: accepts JSON structured format or extracts from standard NBE structure
        $rateData = $this->extractRate($payload);
        if (!$rateData) {
            throw new RuntimeException('NBE response did not contain valid USD/ETB rate.');
        }

        $sourceTime = isset($rateData['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $rateData['date'])
            ? CarbonImmutable::parse($rateData['date'], 'UTC')->startOfDay()->toIso8601String()
            : $now->startOfDay()->toIso8601String();

        $series = MarketSeries::query()->where('source_id', $source->id)->where('enabled', true)->get();
        $quotes = [];

        foreach ($series as $item) {
            $currencyPair = strtoupper((string) ($item->configuration['pair'] ?? 'USD/ETB'));
            if ($currencyPair === 'USD/ETB' || $item->symbol === 'NBE_USD_ETB') {
                $quotes[] = [
                    'symbol' => $item->symbol,
                    'value' => (string) $rateData['rate'],
                    'currency' => 'ETB',
                    'unit' => 'USD',
                    'source_time' => $sourceTime,
                    'raw_ref' => [
                        'source' => 'National Bank of Ethiopia',
                        'buying' => $rateData['buying'] ?? null,
                        'selling' => $rateData['selling'] ?? null,
                        'transaction_rate' => $rateData['rate'],
                        'response_sha256' => $hash,
                    ],
                ];
            }
        }

        if ($quotes === []) {
            // Default quote if series is not yet specifically configured
            $quotes[] = [
                'symbol' => 'NBE_USD_ETB',
                'value' => (string) $rateData['rate'],
                'currency' => 'ETB',
                'unit' => 'USD',
                'source_time' => $sourceTime,
                'raw_ref' => [
                    'source' => 'National Bank of Ethiopia',
                    'buying' => $rateData['buying'] ?? null,
                    'selling' => $rateData['selling'] ?? null,
                    'transaction_rate' => $rateData['rate'],
                    'response_sha256' => $hash,
                ],
            ];
        }

        return $quotes;
    }

    /**
     * Extract USD indicative exchange rate from payload.
     *
     * @param mixed $payload
     * @return array{rate: float|string, buying?: float|string, selling?: float|string, date?: string}|null
     */
    protected function extractRate(mixed $payload): ?array
    {
        if (!is_array($payload)) {
            return null;
        }

        // Direct key mapping if available in json
        if (isset($payload['USD'])) {
            $usd = $payload['USD'];
            if (is_array($usd)) {
                $rate = $usd['rate'] ?? $usd['central_rate'] ?? $usd['middle_rate'] ?? $usd['selling_rate'] ?? null;
                if (is_numeric($rate) && (float) $rate > 0) {
                    return [
                        'rate' => (float) $rate,
                        'buying' => $usd['buying_rate'] ?? null,
                        'selling' => $usd['selling_rate'] ?? null,
                        'date' => $payload['date'] ?? null,
                    ];
                }
            } elseif (is_numeric($usd) && (float) $usd > 0) {
                return ['rate' => (float) $usd, 'date' => $payload['date'] ?? null];
            }
        }

        // Array of currencies
        $rates = $payload['rates'] ?? $payload['data'] ?? (array_is_list($payload) ? $payload : []);
        foreach ($rates as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = strtoupper((string) ($row['currency'] ?? $row['code'] ?? $row['symbol'] ?? ''));
            if ($code === 'USD' || str_contains($code, 'USD')) {
                $rate = $row['rate'] ?? $row['central_rate'] ?? $row['middle_rate'] ?? $row['selling_rate'] ?? $row['value'] ?? null;
                if (is_numeric($rate) && (float) $rate > 0) {
                    return [
                        'rate' => (float) $rate,
                        'buying' => $row['buying_rate'] ?? $row['buying'] ?? null,
                        'selling' => $row['selling_rate'] ?? $row['selling'] ?? null,
                        'date' => $row['date'] ?? $payload['date'] ?? null,
                    ];
                }
            }
        }

        return null;
    }
}
