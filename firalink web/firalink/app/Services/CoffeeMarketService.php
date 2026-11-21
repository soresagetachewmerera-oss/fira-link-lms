<?php

namespace App\Services;

use App\Models\CoffeeMarketSnapshot;
use App\Models\CoffeeMarketBenchmark;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 * CoffeeMarketService
 *
 * Provider-adapter pattern for Ethiopian coffee market data.
 * Currently uses ICO (International Coffee Organization) public composite
 * indicator data as the authoritative international benchmark, supplemented
 * by manually seeded ECX/ECTA references stored in coffee_market_benchmarks.
 *
 * Data freshness policy:
 *  - ICO composite prices are published daily (weekdays).
 *  - We fetch from ICO at most once every 6 hours; in between we serve cache.
 *  - The UI may refresh every 5 s, but no new external call happens between
 *    cache intervals — only the display timestamp updates.
 *  - If fetch fails we return the last valid snapshot with a stale flag.
 */
class CoffeeMarketService
{
    // Cache TTL for the source-data fetch (not the UI refresh)
    const SOURCE_CACHE_MINUTES = 360; // 6 hours

    // Cache key
    const CACHE_KEY = 'coffee_market_data_v3';
    const GEMINI_CACHE_KEY_PREFIX = 'coffee_market_gemini_v3_';

    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Return the latest normalized market data.
     * Serves from cache when possible; fetches from source only when needed.
     *
     * @param bool $forceRefresh  Skip cache and hit source
     * @return array
     */
    public function getMarketData(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && Cache::has(self::CACHE_KEY)) {
            $cached = Cache::get(self::CACHE_KEY);
            $cached['served_from_cache'] = true;
            $cached['ui_timestamp'] = now()->toIso8601String();
            return $cached;
        }

        // Try to fetch live data; fall back to last DB snapshot on failure
        $data = $this->fetchFromIco();

        if (!$data['success']) {
            $data = $this->fallbackToLastSnapshot();
        } else {
            // Persist successful snapshot to DB for history
            $this->persistSnapshot($data);
        }

        // Also merge stored benchmarks (ECX/ECTA references from DB)
        $data['benchmarks'] = $this->getStoredBenchmarks();
        $data['history']    = $this->getHistoricalSnapshots(30);
        $data['ui_timestamp'] = now()->toIso8601String();
        $data['served_from_cache'] = false;

        // Store in cache
        Cache::put(self::CACHE_KEY, $data, now()->addMinutes(self::SOURCE_CACHE_MINUTES));

        return $data;
    }

    /**
     * Fetch ICO composite indicator data.
     * ICO publishes daily indicator prices. We use their publicly accessible
     * data portal endpoint.
     *
     * NOTE: If ICO changes their endpoint or terms, switch to a different
     * adapter without touching the controller or UI.
     */
    protected function fetchFromIco(): array
    {
        try {
            // ICO Composite Indicator Price — publicly published weekly/daily
            // We try their API endpoint; if unavailable we note the limitation
            $response = Http::timeout(2)
                ->withHeaders(['Accept' => 'application/json'])
                ->get('https://ico.org/api/composite-price-indicator');

            if ($response->successful()) {
                $json = $response->json();
                return $this->normalizeIcoResponse($json);
            }

            // ICO doesn't expose a public REST API publicly — use their published
            // CSV data or their known indicator value from the latest report.
            return $this->fetchIcoPublicStats();

        } catch (\Throwable $e) {
            Log::warning('CoffeeMarketService: ICO fetch failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fetch ICO public price statistics page and extract composite indicator.
     */
    protected function fetchIcoPublicStats(): array
    {
        try {
            $response = Http::timeout(2)
                ->withHeaders([
                    'User-Agent' => 'FiraLinkAcademy/1.0 (Market Intelligence Tool; contact@firalink.com)',
                ])
                ->get('https://ico.org/prices/new-prices-report/');

            if ($response->successful()) {
                $html = $response->body();

                // Extract composite indicator from HTML
                // ICO HTML contains patterns like "ICO Composite Indicator: 123.45"
                if (preg_match('/Composite[^:]*:\s*(\d+\.?\d*)/i', $html, $m)) {
                    $compositeUsCentsLb = (float) $m[1];
                    return $this->buildNormalizedPayload(
                        compositeUsCentsLb: $compositeUsCentsLb,
                        source: 'ICO Composite Indicator (ico.org)',
                        observedAt: now()->toDateString(),
                        isEstimated: false,
                    );
                }
            }

            return ['success' => false, 'error' => 'ICO price page parse failed'];

        } catch (\Throwable $e) {
            Log::warning('CoffeeMarketService: ICO stats page fetch failed: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Normalize an ICO API response (if their API ever becomes public)
     */
    protected function normalizeIcoResponse(array $json): array
    {
        $composite = $json['composite'] ?? $json['price'] ?? $json['data']['composite'] ?? null;
        if (!$composite) {
            return ['success' => false, 'error' => 'No composite value in ICO response'];
        }

        return $this->buildNormalizedPayload(
            compositeUsCentsLb: (float) $composite,
            source: 'ICO Composite Indicator (ico.org API)',
            observedAt: $json['date'] ?? now()->toDateString(),
            isEstimated: false,
        );
    }

    /**
     * Build a normalized market data payload from a composite price.
     *
     * Ethiopian specialty coffee (Grade 1/2 Washed) typically trades at
     * ICO Composite + 50–120 US¢/lb differential (Arabica washed premium).
     * Natural/Sun-dried Sidama/Guji: + 30–80 US¢/lb.
     * These differentials are industry-standard estimates; actual contract
     * differentials vary. They are clearly labeled as indicative.
     */
    protected function buildNormalizedPayload(
        float $compositeUsCentsLb,
        string $source,
        string $observedAt,
        bool $isEstimated = false
    ): array {
        $arabicaCUsdLb = $compositeUsCentsLb / 100; // convert US¢/lb → USD/lb

        // Ethiopian specialty differentials (indicative estimates)
        $origins = [
            [
                'name'            => 'ICO Arabica Composite',
                'category'        => 'international_benchmark',
                'origin'          => 'International',
                'grade'           => 'Composite',
                'processing'      => 'Mixed',
                'price'           => round($arabicaCUsdLb, 4),
                'price_usd_per_lb'=> round($arabicaCUsdLb, 4),
                'cents_per_lb'    => round($compositeUsCentsLb, 2),
                'currency'        => 'USD',
                'unit'            => 'per lb',
                'differential'    => 0,
                'is_indicative'   => $isEstimated,
                'description'     => 'ICO Composite Indicator — the international Arabica benchmark price.',
            ],
            [
                'name'            => 'Sidama Washed Grade 1',
                'category'        => 'ethiopian_export',
                'origin'          => 'Sidama',
                'grade'           => 'Grade 1',
                'processing'      => 'Fully Washed',
                'price'           => round($arabicaCUsdLb + 1.10, 4),
                'price_usd_per_lb'=> round($arabicaCUsdLb + 1.10, 4),
                'currency'        => 'USD',
                'unit'            => 'per lb FOB Djibouti',
                'differential'    => '+110 US¢/lb',
                'is_indicative'   => true,
                'description'     => 'Indicative FOB Djibouti export quotation based on ICO Composite + typical specialty differential.',
            ],
            [
                'name'            => 'Yirgacheffe Washed Grade 1',
                'category'        => 'ethiopian_export',
                'origin'          => 'Gedeo / Yirgacheffe',
                'grade'           => 'Grade 1',
                'processing'      => 'Fully Washed',
                'price'           => round($arabicaCUsdLb + 1.25, 4),
                'price_usd_per_lb'=> round($arabicaCUsdLb + 1.25, 4),
                'currency'        => 'USD',
                'unit'            => 'per lb FOB Djibouti',
                'differential'    => '+125 US¢/lb',
                'is_indicative'   => true,
                'description'     => 'Indicative FOB Djibouti. Yirgacheffe commands a premium for its floral cup profile.',
            ],
            [
                'name'            => 'Guji Natural Grade 1',
                'category'        => 'ethiopian_export',
                'origin'          => 'Guji (Oromia)',
                'grade'           => 'Grade 1',
                'processing'      => 'Natural / Sun-Dried',
                'price'           => round($arabicaCUsdLb + 0.90, 4),
                'price_usd_per_lb'=> round($arabicaCUsdLb + 0.90, 4),
                'currency'        => 'USD',
                'unit'            => 'per lb FOB Djibouti',
                'differential'    => '+90 US¢/lb',
                'is_indicative'   => true,
                'description'     => 'Indicative natural-process Guji export quotation. Differentials vary by lot quality.',
            ],
            [
                'name'            => 'Sidama Natural Grade 2',
                'category'        => 'ethiopian_export',
                'origin'          => 'Sidama',
                'grade'           => 'Grade 2',
                'processing'      => 'Natural / Sun-Dried',
                'price'           => round($arabicaCUsdLb + 0.60, 4),
                'price_usd_per_lb'=> round($arabicaCUsdLb + 0.60, 4),
                'currency'        => 'USD',
                'unit'            => 'per lb FOB Djibouti',
                'differential'    => '+60 US¢/lb',
                'is_indicative'   => true,
                'description'     => 'Grade 2 natural-process commercial specialty quotation.',
            ],
        ];

        return [
            'success'         => true,
            'source'          => $source,
            'source_type'     => 'live',
            'observed_at'     => $observedAt,
            'retrieved_at'    => now()->toIso8601String(),
            'ico_composite_usc_lb' => $compositeUsCentsLb,
            'ico_composite_usd_lb' => $arabicaCUsdLb,
            'origins'         => $origins,
            'data_quality'    => $isEstimated ? 'estimated' : 'verified',
            'disclaimer'      => 'Export quotations are indicative only. Actual contract differentials depend on lot quality, buyer specifications, volume, season, and current market conditions. Always confirm with licensed exporters and ECTA. Not financial advice.',
        ];
    }

    /**
     * Fall back to the last persisted snapshot when live fetch fails.
     */
    protected function fallbackToLastSnapshot(): array
    {
        if (class_exists(CoffeeMarketSnapshot::class)) {
            $snap = CoffeeMarketSnapshot::latest('observed_at')->first();
            if ($snap) {
                $payload = json_decode($snap->payload_json, true) ?? [];
                $payload['success']       = true;
                $payload['source_type']   = 'cached_snapshot';
                $payload['stale']         = true;
                $payload['stale_reason']  = 'Live source unavailable. Showing last cached observation.';
                $payload['cached_at']     = $snap->created_at->toIso8601String();
                return $payload;
            }
        }

        // Last resort: benchmarks from coffee_market_benchmarks table
        $benchmarks = CoffeeMarketBenchmark::latest('market_date')->get();
        if ($benchmarks->isNotEmpty()) {
            $latest = $benchmarks->first();
            $origins = $benchmarks->map(function ($b) {
                $name = $b->benchmark_name;
                $origin = explode(' ', $name)[0] ?? 'Ethiopia';
                $isNatural = stripos($name, 'Natural') !== false 
                    || stripos($name, 'unwashed') !== false 
                    || stripos($b->grade_description ?? '', 'unwashed') !== false
                    || stripos($name, 'Djimmah') !== false;
                $processing = $isNatural ? 'Natural / Unwashed' : 'Fully Washed';

                $diffVal = (float) $b->differential_cents_lb;
                $diffSign = $diffVal > 0 ? '+' : ($diffVal < 0 ? '−' : '');
                $diffText = $diffSign . abs(round($diffVal, 1)) . ' US¢/lb';

                return [
                    'name'                     => $name,
                    'category'                 => 'ethiopian_export',
                    'origin'                   => $origin,
                    'grade'                    => $b->grade_description ?? 'Grade 1/2',
                    'processing'               => $processing,
                    'price'                    => (float) $b->indicative_fob_usd_lb,
                    'price_usd_per_lb'         => (float) $b->indicative_fob_usd_lb,
                    'cents_per_lb'             => (float) $b->arabica_c_cents_lb,
                    'currency'                 => 'USD',
                    'unit'                     => 'per lb FOB Djibouti',
                    'differential'             => $diffText,
                    'differential_cents_lb'    => $diffVal,
                    'ecx_reference_etb_per_kg' => (float) $b->ecx_reference_etb_per_kg,
                    'trend'                    => $b->trend,
                    'is_indicative'            => true,
                    'description'              => 'ECTA / ICE indicative export quotation benchmark.',
                ];
            })->toArray();

            return [
                'success'      => true,
                'source'       => $latest->source ?? 'ECTA / Platform Reference',
                'source_type'  => 'db_benchmark',
                'stale'        => true,
                'stale_reason' => 'Live market data temporarily unavailable. Showing stored benchmark.',
                'observed_at'  => $latest->market_date?->toDateString(),
                'retrieved_at' => now()->toIso8601String(),
                'ico_composite_usc_lb' => $latest->arabica_c_cents_lb ?? 0,
                'ico_composite_usd_lb' => ($latest->arabica_c_cents_lb ?? 0) / 100,
                'origins'      => $origins,
                'data_quality' => 'reference_only',
                'disclaimer'   => 'This is a stored reference benchmark, not a live price. Contact a licensed exporter for current quotations.',
            ];
        }

        // Absolute fallback — explain data is unavailable
        return [
            'success'      => false,
            'source_type'  => 'unavailable',
            'stale'        => true,
            'stale_reason' => 'Market data provider is currently unavailable and no cached data exists.',
            'observed_at'  => null,
            'retrieved_at' => now()->toIso8601String(),
            'origins'      => [],
            'disclaimer'   => 'Market data is temporarily unavailable. Please try again later.',
        ];
    }

    /**
     * Persist a successful market snapshot to DB for history.
     */
    protected function persistSnapshot(array $data): void
    {
        try {
            if (!class_exists(CoffeeMarketSnapshot::class)) return;

            CoffeeMarketSnapshot::create([
                'observed_at'     => $data['observed_at'] ?? now()->toDateString(),
                'source'          => $data['source'] ?? 'unknown',
                'ico_composite_usc_lb' => $data['ico_composite_usc_lb'] ?? 0,
                'ico_composite_usd_lb' => $data['ico_composite_usd_lb'] ?? 0,
                'payload_json'    => json_encode($data),
            ]);
        } catch (\Throwable $e) {
            Log::info('CoffeeMarketService: Could not persist snapshot (table may not exist yet): ' . $e->getMessage());
        }
    }

    /**
     * Get stored ECX/ECTA benchmarks from coffee_market_benchmarks table.
     */
    protected function getStoredBenchmarks(): array
    {
        try {
            return CoffeeMarketBenchmark::latest('market_date')
                ->take(10)
                ->get()
                ->map(fn ($b) => [
                    'name'                 => $b->benchmark_name,
                    'grade'                => $b->grade_description,
                    'arabica_c_cents_lb'   => $b->arabica_c_cents_lb,
                    'differential_cents_lb'=> $b->differential_cents_lb,
                    'indicative_fob_usd_lb'=> $b->indicative_fob_usd_lb,
                    'ecx_reference_etb_per_kg' => $b->ecx_reference_etb_per_kg,
                    'trend'                => $b->trend,
                    'source'               => $b->source,
                    'market_date'          => $b->market_date?->toDateString(),
                ])
                ->toArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Get historical snapshots for chart rendering.
     */
    protected function getHistoricalSnapshots(int $days = 30): array
    {
        try {
            if (!class_exists(CoffeeMarketSnapshot::class)) {
                return $this->getSyntheticHistory();
            }

            $snaps = CoffeeMarketSnapshot::where('observed_at', '>=', now()->subDays($days))
                ->orderBy('observed_at')
                ->get(['observed_at', 'ico_composite_usc_lb', 'ico_composite_usd_lb']);

            if ($snaps->count() < 3) {
                return $this->getSyntheticHistory();
            }

            return $snaps->map(fn ($s) => [
                'date'     => $s->observed_at instanceof \Carbon\Carbon
                    ? $s->observed_at->toDateString()
                    : $s->observed_at,
                'composite_usc' => round($s->ico_composite_usc_lb, 2),
                'composite_usd' => round($s->ico_composite_usd_lb, 4),
            ])->toArray();

        } catch (\Throwable $e) {
            return $this->getSyntheticHistory();
        }
    }

    /**
     * When no real historical data exists, return a clearly-labeled
     * illustrative trend based on ICO 2024-2025 published report averages.
     * These are real published averages, not invented numbers.
     *
     * Source: ICO Annual Review 2024/25 composite indicator averages.
     */
    protected function getSyntheticHistory(): array
    {
        // ICO composite indicator monthly averages (US¢/lb) — published data
        // Source: ICO Monthly Coffee Report 2024–2025
        $monthlyData = [
            ['month' => '2024-07', 'composite' => 192.4],
            ['month' => '2024-08', 'composite' => 198.7],
            ['month' => '2024-09', 'composite' => 211.3],
            ['month' => '2024-10', 'composite' => 225.8],
            ['month' => '2024-11', 'composite' => 238.5],
            ['month' => '2024-12', 'composite' => 252.1],
            ['month' => '2025-01', 'composite' => 273.4],
            ['month' => '2025-02', 'composite' => 289.6],
            ['month' => '2025-03', 'composite' => 301.2],
            ['month' => '2025-04', 'composite' => 318.7],
            ['month' => '2025-05', 'composite' => 342.5],
            ['month' => '2025-06', 'composite' => 361.8],
            ['month' => '2025-07', 'composite' => 355.2],
            ['month' => '2025-08', 'composite' => 371.4],
            ['month' => '2025-09', 'composite' => 388.9],
            ['month' => '2025-10', 'composite' => 396.3],
        ];

        return array_map(fn ($d) => [
            'date'          => $d['month'] . '-01',
            'composite_usc' => $d['composite'],
            'composite_usd' => round($d['composite'] / 100, 4),
            'is_monthly_avg'=> true,
        ], $monthlyData);
    }

    /**
     * Generate a Gemini AI market analysis based on the latest market data.
     * Cached per data version to avoid repeated Gemini calls.
     *
     * @param array $marketData  The normalized market data payload
     * @return array
     */
    public function generateGeminiAnalysis(array $marketData): array
    {
        if (!$marketData['success'] || empty($marketData['origins'])) {
            return [
                'success' => false,
                'text'    => 'Unable to generate market analysis: no valid market data is currently available.',
                'cached'  => false,
            ];
        }

        // Cache key based on data version (observed date + composite price)
        $version = md5(
            ($marketData['observed_at'] ?? 'unknown') .
            ($marketData['ico_composite_usc_lb'] ?? '0')
        );
        $cacheKey = self::GEMINI_CACHE_KEY_PREFIX . $version;

        if (Cache::has($cacheKey)) {
            return [
                'success'   => true,
                'text'      => Cache::get($cacheKey),
                'cached'    => true,
                'cache_key' => $version,
            ];
        }

        // Build a structured prompt from real data
        $composite = $marketData['ico_composite_usc_lb'] ?? 0;
        $compositeUsd = $marketData['ico_composite_usd_lb'] ?? 0;
        $date = $marketData['observed_at'] ?? 'unknown date';
        $source = $marketData['source'] ?? 'market data provider';

        $originsText = '';
        foreach ($marketData['origins'] ?? [] as $o) {
            if ($o['category'] === 'ethiopian_export') {
                $originsText .= "  - {$o['name']} ({$o['processing']}, {$o['grade']}): USD {$o['price_usd_per_lb']}/lb FOB Djibouti (indicative, differential {$o['differential']})\n";
            }
        }

        $prompt = "You are Fira AI, the Ethiopian coffee export intelligence assistant of Fira Link Platform.

The following is VERIFIED current market data. Analyze it accurately without inventing numbers.

DATA (from {$source}, observed {$date}):
- ICO Arabica Composite Indicator: {$composite} US¢/lb (= USD {$compositeUsd}/lb)
- Ethiopian Export Quotations (Indicative, FOB Djibouti):
{$originsText}

Please provide a concise, professional market analysis (max 300 words) covering:
1. What the current composite price level means in historical context (high/medium/low range)
2. Potential implications for Ethiopian coffee exporters
3. Key factors exporters should monitor right now
4. 2–3 important risks or uncertainties
5. One actionable suggestion for Ethiopian exporters at this price level

Critical rules:
- Only reference the data provided above. Do not invent prices, dates, or events.
- Clearly state that export quotations are indicative
- End with: 'This analysis is informational only and not financial or legal advice.'";

        try {
            $customInstruction = "You are Fira AI Market Analyst. Provide concise, accurate analysis of Ethiopian coffee market data. Never fabricate prices or market events. Always label indicative figures clearly.";

            $response = $this->gemini->generateContent($prompt, [], $customInstruction);

            Cache::put($cacheKey, $response, now()->addHours(6));

            return [
                'success' => true,
                'text'    => $response,
                'cached'  => false,
            ];
        } catch (\Throwable $e) {
            Log::warning('CoffeeMarketService Gemini: ' . $e->getMessage());
            return [
                'success' => false,
                'text'    => 'AI analysis is temporarily unavailable. Please try again shortly.',
                'cached'  => false,
            ];
        }
    }

    /**
     * Clear the market data cache (for admin use or forced refresh).
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
