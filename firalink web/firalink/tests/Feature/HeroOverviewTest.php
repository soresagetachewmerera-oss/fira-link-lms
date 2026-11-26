<?php

namespace Tests\Feature;

use App\Models\CoffeeListing;
use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Models\MarketTrend;
use App\Models\Organization;
use App\Models\User;
use App\Services\CoffeeExportService;
use App\Services\Market\HeroOverviewService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

class HeroOverviewTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        CarbonImmutable::setTestNow('2026-11-26 12:00:00');
        \Illuminate\Support\Carbon::setTestNow('2026-11-26 12:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        \Illuminate\Support\Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedIcoSeries(int $daysAgo = 0, float $value = 249.09, string $trend = 'bullish'): void
    {
        $source = MarketSource::where('slug', 'ico_daily')->firstOrFail();
        $source->forceFill(['enabled' => true, 'last_success_at' => now()])->save();
        $series = MarketSeries::where('symbol', 'ICO_COMPOSITE')->firstOrFail();

        for ($i = 29; $i >= 0; $i--) {
            $time = now()->utc()->subDays($daysAgo + $i)->startOfDay();
            $v = $i === 0 ? $value : round($value - ($i * 0.25), 2);
            MarketPrice::updateOrCreate(
                ['series_id' => $series->id, 'source_time' => $time],
                [
                    'value' => $v,
                    'currency' => $series->currency,
                    'unit' => $series->unit,
                    'fetched_at' => now(),
                    'status' => 'published',
                ]
            );
        }

        MarketTrend::updateOrCreate(
            ['series_id' => $series->id],
            [
                'computed_at' => now(),
                'trend' => $trend,
                'last_close' => $value,
                'average_20' => $value - 2.0,
                'slope_5' => 0.4,
                'sample_size' => 30,
                'inputs' => ['basis' => 'daily_close_utc'],
            ]
        );
    }

    private function seedEctaDataset(string $effectiveDate = '2026-11-21'): CoffeeMinimumPriceDataset
    {
        $ds = CoffeeMinimumPriceDataset::create([
            'title' => 'ECTA Weekly Export Minimum Schedule',
            'source' => 'ECTA (Ethiopian Coffee & Tea Authority)',
            'file_name' => 'ecta.xlsx',
            'effective_date' => $effectiveDate,
            'version' => 'test-hero',
            'total_records' => 2,
            'valid_records' => 2,
            'duplicate_records' => 0,
            'review_required_records' => 0,
            'status' => 'published',
        ]);

        CoffeeMinimumPriceRecord::create([
            'dataset_id' => $ds->id, 'coffee_type' => 'Jimma Natural Grade 5', 'origin' => 'Jimma',
            'grade' => 'Grade 5', 'processing_method' => 'Natural', 'minimum_price' => 2.37,
            'price_unit' => 'USD/lb', 'currency' => 'USD', 'effective_date' => $effectiveDate, 'status' => 'valid',
        ]);
        CoffeeMinimumPriceRecord::create([
            'dataset_id' => $ds->id, 'coffee_type' => 'Yirgacheffe Washed Grade 1', 'origin' => 'Yirgacheffe',
            'grade' => 'Grade 1', 'processing_method' => 'Washed', 'minimum_price' => 9.87,
            'price_unit' => 'USD/lb', 'currency' => 'USD', 'effective_date' => $effectiveDate, 'status' => 'valid',
        ]);

        return $ds;
    }

    public function test_home_renders_all_cards_floating_ecta_and_proof_stats_from_fixtures(): void
    {
        $this->seedIcoSeries(0, 251.40, 'bullish');
        $this->seedEctaDataset('2026-11-21');

        $seller = $this->makeUser();
        $org = $this->approveBusiness($this->makeOrganization($seller), 'exporter');
        CoffeeListing::create([
            'seller_id' => $seller->id,
            'organization_id' => $org->id,
            'title' => 'Sidama Washed G1 Fixture Lot',
            'slug' => 'sidama-washed-g1-fixture-lot',
            'origin_region' => 'Sidama',
            'coffee_type' => 'Arabica',
            'processing_method' => 'washed',
            'grade' => 'grade_1',
            'total_quantity_bags' => 320,
            'available_quantity_bags' => 320,
            'minimum_order_bags' => 50,
            'bag_weight_kg' => 60,
            'indicative_price_usd_per_lb' => 4.20,
            'status' => 'active',
        ]);

        $calc = app(CoffeeExportService::class)->calculateProfit([]);
        $expectedCostPerKg = number_format(round($calc['cost_breakdown_etb']['total_cost'] / $calc['net_weight_kg'], 2), 0);

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('251.40', $html);
        $this->assertStringContainsString('Up · Daily close', $html);
        $this->assertStringContainsString('International Coffee Organization (ICO)', $html);
        $this->assertStringContainsString($expectedCostPerKg, $html);
        $this->assertStringContainsString('Example shipment · FOB', $html);
        $this->assertStringContainsString('Ask about prices and export steps', $html);
        $this->assertStringContainsString('Sources named', $html);
        $this->assertStringContainsString('ECTA schedule updated', $html);
        $this->assertStringContainsString('Effective 2026-11-21 · 2 specs', $html);
        $this->assertStringContainsString('$2.37', $html);
        $this->assertStringContainsString('$9.87', $html);
        $this->assertStringNotContainsString('100% sourced', $html);

        // Also works for authenticated user
        $user = User::factory()->create();
        $this->actingAs($user)->get('/')->assertOk()->assertSee('The trade platform built for Ethiopian coffee');
    }

    public function test_stale_and_unavailable_states_and_zero_verified_lots_fallback(): void
    {
        // Stale ICO (older than 7200 minutes = 5 days, within 3x = 15 days -> 8 days ago)
        $this->seedIcoSeries(8, 247.26, 'sideways');

        // Deactivate any existing visible lots so verified count is 0
        CoffeeListing::query()->update(['status' => 'draft']);
        CoffeeMinimumPriceDataset::query()->update(['status' => 'archived']);
        HeroOverviewService::forgetCache();

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Stale · 2026-11-18', $html);
        $this->assertStringContainsString('Browse verified lots', $html);
        $this->assertStringNotContainsString('ECTA schedule updated', $html);
        $this->assertStringNotContainsString('ECTA lowest floor', $html);

        // Turn off ICO completely -> Unavailable, never old price
        MarketSource::where('slug', 'ico_daily')->update(['enabled' => false]);
        HeroOverviewService::forgetCache();

        $htmlOff = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Unavailable', $htmlOff);
        $this->assertStringNotContainsString('247.26', $htmlOff);
    }

    public function test_api_market_overview_includes_site_hero_payload(): void
    {
        $this->seedIcoSeries(0, 249.09, 'sideways');
        $this->seedEctaDataset('2026-11-21');

        $response = $this->getJson('/api/market/overview')->assertOk();
        $response->assertJsonPath('data.site_hero.status', 'ok')
            ->assertJsonPath('data.site_hero.cards.coffee_market.display_value', '249.09')
            ->assertJsonPath('data.site_hero.cards.fira_ai.chip', 'Sources named')
            ->assertJsonPath('data.site_hero.floating_ecta.effective_date', '2026-11-21')
            ->assertJsonPath('data.site_hero.floating_ecta.spec_count', 2);
    }
}
