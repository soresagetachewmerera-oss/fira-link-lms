<?php

namespace App\Services\Market\Adapters;

use App\Models\MarketSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ApiNinjasCoffeeAdapter implements MarketSourceAdapter
{
    public function fetch(MarketSource $source): array
    {
        $key = config('market.api_ninjas_key');
        if (!$key) {
            throw new RuntimeException('API_NINJAS_API_KEY is not configured.');
        }

        $response = Http::acceptJson()
            ->withHeaders(['X-Api-Key' => $key])
            ->timeout(config('market.http_timeout_seconds'))
            ->retry((array) config('market.http_backoff_ms', [500, 2000]), throw: false)
            ->get($source->url);

        if (!$response->successful()) {
            throw new RuntimeException('API Ninjas returned HTTP '.$response->status().'.');
        }

        $payload = $response->json();
        if (array_is_list($payload ?? [])) {
            $payload = $payload[0] ?? [];
        }

        $price = $payload['price'] ?? null;
        $updated = $payload['updated'] ?? null;
        if (!is_numeric($price) || !$updated) {
            throw new RuntimeException('API Ninjas response did not contain a numeric price and source time.');
        }

        $sourceTime = is_numeric($updated)
            ? CarbonImmutable::createFromTimestampUTC((int) $updated)
            : CarbonImmutable::parse($updated)->utc();

        return [[
            'symbol' => 'KC_ACTIVE',
            'value' => (string) $price,
            'currency' => strtoupper((string) ($payload['currency_unit'] ?? 'USX')),
            'unit' => strtolower((string) ($payload['unit'] ?? 'lb')),
            'source_time' => $sourceTime->toIso8601String(),
            'raw_ref' => [
                'exchange' => $payload['exchange'] ?? null,
                'contract' => $payload['futures_contract'] ?? null,
                'contract_month' => $payload['futures_contract_month'] ?? null,
                'response_sha256' => hash('sha256', $response->body()),
            ],
        ]];
    }
}
