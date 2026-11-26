<?php

namespace Tests\Unit;

use App\Services\CoffeeExportService;
use App\Services\Market\HeroOverviewService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class HeroOverviewServiceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_build_degrades_gracefully_when_sources_are_off_and_ecta_missing(): void
    {
        $service = app(HeroOverviewService::class);
        $data = $service->build();

        $this->assertSame('ok', $data['status']);
        $this->assertNotEmpty($data['source']);
        $this->assertNotEmpty($data['as_of']);

        // Coffee market unavailable (no number invented)
        $coffee = $data['cards']['coffee_market'];
        $this->assertSame('unavailable', $coffee['status']);
        $this->assertNull($coffee['value']);
        $this->assertSame('Unavailable', $coffee['display_value']);
        $this->assertNull($coffee['sparkline']);

        // Verified lots 0 -> browse fallback (no number)
        $lots = $data['cards']['verified_lots'];
        $this->assertSame('browse_fallback', $lots['mode']);
        $this->assertNull($lots['value']);
        $this->assertMatchesRegularExpression('/^[^\d]*$/', $lots['display_value']);

        // Landed cost matches CoffeeExportService default run exactly
        $calc = app(CoffeeExportService::class)->calculateProfit([]);
        $expectedPerKg = round($calc['cost_breakdown_etb']['total_cost'] / $calc['net_weight_kg'], 2);
        $this->assertSame($expectedPerKg, $data['cards']['landed_cost']['value']);
        $this->assertSame($calc['incoterm'], $data['cards']['landed_cost']['incoterm']);

        // Fira AI never invents an accuracy percentage
        $ai = $data['cards']['fira_ai'];
        $this->assertNull($ai['eval_score']);
        $this->assertSame('Sources named', $ai['chip']);
        $this->assertStringNotContainsString('100%', $ai['feature_line']);

        // Floating ECTA card omitted when no published dataset
        $this->assertNull($data['floating_ecta']);

        // Proof band omits missing ICO & ECTA stats and keeps enabled tools count
        $keys = array_column($data['proof_band'], 'key');
        $this->assertSame(['free_tools'], $keys);
    }

    public function test_forget_cache_clears_en_and_am_keys(): void
    {
        Cache::put('hero:overview:v1:en', ['cached' => true], 600);
        Cache::put('hero:overview:v1:am', ['cached' => true], 600);

        HeroOverviewService::forgetCache();

        $this->assertFalse(Cache::has('hero:overview:v1:en'));
        $this->assertFalse(Cache::has('hero:overview:v1:am'));
    }
}
