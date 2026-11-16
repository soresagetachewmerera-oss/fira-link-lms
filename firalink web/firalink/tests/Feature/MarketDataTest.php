<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Services\Market\MarketIngestionService;
use App\Services\Market\MarketReadService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Coffee market data: only stored, validated prices with source and source time; suspicious values
 * wait for review; old or missing data is shown as Stale or Unavailable, never as current.
 * Uses fixed fixtures and a fake HTTP client. The live smoke test is opt-in (MARKET_LIVE_SMOKE_TESTS=true).
 */
class MarketDataTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

    private const ICO_URL = 'https://data.ico.org/api/globe/icip';
    private const NINJAS_URL = 'https://api.api-ninjas.com/v1/commodityprice*';

    protected function setUp(): void
    {
        parent::setUp();
        Sleep::fake();
        Cache::flush();
        CarbonImmutable::setTestNow('2026-11-16 12:00:00');
        \Illuminate\Support\Carbon::setTestNow('2026-11-16 12:00:00');
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        \Illuminate\Support\Carbon::setTestNow();
        parent::tearDown();
    }

    private function icoRow(string $date, float $icip = 249.09): array
    {
        return ['date' => $date, 'ICIP' => $icip, 'COL_MILDS' => 339.70, 'OTH_MILDS' => 321.01, 'BRA_NAT' => 279.43, 'ROBUSTAS' => 157.29, 'NY_ARABICA' => 272.35];
    }

    private function enable(string $slug): MarketSource
    {
        $source = MarketSource::where('slug', $slug)->firstOrFail();
        $source->forceFill(['enabled' => true])->save();

        return $source;
    }

    private function fakeIco(array $rows): void
    {
        Http::fake([self::ICO_URL => Http::response($rows, 200)]);
    }

    private function storePublished(string $symbol, float $value, string $time): MarketPrice
    {
        $series = MarketSeries::where('symbol', $symbol)->firstOrFail();

        return MarketPrice::create([
            'series_id' => $series->id, 'value' => $value, 'currency' => $series->currency, 'unit' => $series->unit,
            'source_time' => $time, 'fetched_at' => now(), 'status' => 'published',
        ]);
    }

    public function test_with_every_source_off_the_overview_shows_unavailable_and_no_number(): void
    {
        $response = $this->getJson('/api/market/overview')->assertOk();

        $response->assertJsonPath('data.hero.symbol', 'KC_ACTIVE')
            ->assertJsonPath('data.hero.value', null)
            ->assertJsonPath('data.hero.freshness.status', 'unavailable')
            ->assertJsonPath('data.hero.trend.label', 'not_enough_data')
            ->assertJsonPath('data.hero.source.name', 'API Ninjas / ICE Coffee C');
        foreach ($response->json('data.benchmarks') as $row) {
            $this->assertNull($row['value'], $row['symbol'] . ' must not show a number while its source is off');
            $this->assertSame('unavailable', $row['freshness']['status']);
        }
        $this->assertSame([], $response->json('data.summary.source_rows'));
    }

    public function test_ico_fetch_publishes_values_with_the_source_date_as_daily_close(): void
    {
        $this->enable('ico_daily');
        $this->fakeIco([$this->icoRow('2026-11-11', 248.00), $this->icoRow('2026-11-12', 249.09)]);

        $results = app(MarketIngestionService::class)->fetch('ico_daily');
        $this->assertSame('success', $results[0]['status']);

        $this->assertSame(2, MarketSeries::where('symbol', 'ICO_COMPOSITE')->first()->prices()->count(), 'history rows are stored too');
        $price = MarketSeries::where('symbol', 'ICO_COMPOSITE')->first()->prices()->latest('source_time')->first();
        $this->assertSame('249.09000000', $price->value);
        $this->assertSame('2026-11-12 00:00:00', $price->source_time->format('Y-m-d H:i:s'));
        $this->assertSame('published', $price->status);

        $row = collect($this->getJson('/api/market/overview')->json('data.benchmarks'))->firstWhere('symbol', 'ICO_COMPOSITE');
        $this->assertSame('249.09000000', $row['value']);
        $this->assertSame('daily_close', $row['freshness']['status']);
        $this->assertSame('2026-11-12T00:00:00+00:00', $row['source_time']);
        $this->assertSame('International Coffee Organization (ICO)', $row['source']['attribution']);
        $this->assertNotNull(MarketSource::where('slug', 'ico_daily')->first()->last_success_at);
    }

    public function test_dry_run_prints_live_values_but_writes_nothing_even_for_a_disabled_source(): void
    {
        $this->fakeIco([$this->icoRow('2026-11-12')]);

        $this->artisan('market:fetch', ['source' => 'ico_daily', '--dry-run' => true])
            ->expectsOutputToContain('ICO_COMPOSITE 249.09 USX/lb | source time 2026-11-12T00:00:00+00:00 | published')
            ->assertSuccessful();

        $this->assertSame(0, MarketPrice::count());
        $source = MarketSource::where('slug', 'ico_daily')->first();
        $this->assertFalse($source->enabled);
        $this->assertNull($source->last_attempt_at);
    }

    public function test_a_disabled_source_is_never_fetched_for_real(): void
    {
        Http::fake();
        $result = app(MarketIngestionService::class)->fetch('ico_daily');

        $this->assertSame('disabled', $result[0]['status']);
        Http::assertNothingSent();
        $this->assertSame(0, MarketPrice::count());
    }

    public function test_a_spike_is_held_in_needs_review_and_not_published(): void
    {
        $this->enable('ico_daily');
        $this->storePublished('ICO_COMPOSITE', 249.09, '2026-11-12 00:00:00');
        $this->fakeIco([$this->icoRow('2026-11-13', 400.00)]); // +60%, limit 15%

        app(MarketIngestionService::class)->fetch('ico_daily');

        $held = MarketPrice::where('status', 'needs_review')->whereHas('series', fn ($q) => $q->where('symbol', 'ICO_COMPOSITE'))->first();
        $this->assertNotNull($held);
        $this->assertStringContainsString('exceeds', $held->review_reason);

        $row = collect($this->getJson('/api/market/overview')->json('data.benchmarks'))->firstWhere('symbol', 'ICO_COMPOSITE');
        $this->assertSame('249.09000000', $row['value'], 'the held spike must not be shown');
    }

    public function test_bad_values_are_rejected_and_not_stored(): void
    {
        $this->enable('ico_daily');
        $this->fakeIco([['date' => '2026-11-12', 'ICIP' => -5, 'COL_MILDS' => 'n/a', 'OTH_MILDS' => 0, 'BRA_NAT' => 279.43, 'ROBUSTAS' => 157.29]]);

        $rows = collect(app(MarketIngestionService::class)->fetch('ico_daily')[0]['rows'])->keyBy('symbol');

        $this->assertSame('rejected', $rows['ICO_COMPOSITE']['status']);
        $this->assertSame('rejected', $rows['ICO_OTHER_MILDS']['status']);
        $this->assertSame('rejected', $rows['ICO_COLOMBIAN_MILDS']['status'], 'a non-numeric value is rejected, not guessed');
        $this->assertSame(0, MarketPrice::whereHas('series', fn ($q) => $q->whereIn('symbol', ['ICO_COMPOSITE', 'ICO_OTHER_MILDS', 'ICO_COLOMBIAN_MILDS']))->count());
        $this->assertSame(2, MarketPrice::count());
    }

    public function test_the_live_ico_response_shape_is_read_and_a_unit_change_stores_nothing(): void
    {
        $this->enable('ico_daily');
        // Same shape as data.ico.org/api/globe/icip on 2026-11-16 (values from that response).
        $live = [
            'daily' => [
                ['BRA_NAT' => 273.64, 'COL_MILDS' => 332.14, 'ICIP' => 245.15, 'LDN_ROBUSTA' => 146.62, 'NY_ARABICA' => 262.55, 'OTH_MILDS' => 313.2, 'ROBUSTAS' => 158.12, 'date' => '2026-11-11', 'pct' => -0.36],
                ['BRA_NAT' => 279.43, 'COL_MILDS' => 339.7, 'ICIP' => 249.09, 'LDN_ROBUSTA' => 145.06, 'NY_ARABICA' => 272.35, 'OTH_MILDS' => 321.01, 'ROBUSTAS' => 157.29, 'date' => '2026-11-12', 'pct' => 1.61],
            ],
            'open_from' => '2026-01-01', 'range' => ['2026-01-02', '2026-11-12'], 'source' => 'ico.org oscura.i_cip_daily_prices',
            'units' => 'US cents/lb', 'window' => '2026 (anonymous). The full daily record needs an ICO Data subscription.',
        ];
        $changed = $live;
        $changed['units'] = 'USD/kg';
        Http::fake([self::ICO_URL => Http::sequence()->push($live)->push($changed)]);

        $result = app(MarketIngestionService::class)->fetch('ico_daily')[0];
        $this->assertSame('success', $result['status']);
        $this->assertSame(10, MarketPrice::where('status', 'published')->count());
        $row = collect($this->getJson('/api/market/overview')->json('data.benchmarks'))->firstWhere('symbol', 'ICO_COMPOSITE');
        $this->assertSame('249.09000000', $row['value']);
        $this->assertSame('2026-11-12T00:00:00+00:00', $row['source_time']);

        MarketPrice::query()->delete();
        $result = app(MarketIngestionService::class)->fetch('ico_daily')[0];
        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('units changed', $result['error']);
        $this->assertSame(0, MarketPrice::count());
    }

    public function test_a_wrong_unit_from_the_source_is_rejected(): void
    {
        $this->enable('api_ninjas_coffee');
        config(['market.api_ninjas_key' => 'test-key-not-real']);
        Http::fake([self::NINJAS_URL => Http::response(['exchange' => 'ICE', 'name' => 'Coffee', 'price' => 2.79, 'currency_unit' => 'USD', 'unit' => 'lb', 'updated' => now()->subMinutes(20)->timestamp])]);

        $row = app(MarketIngestionService::class)->fetch('api_ninjas_coffee')[0]['rows'][0];

        $this->assertSame('rejected', $row['status']);
        $this->assertSame(0, MarketPrice::count());
    }

    public function test_a_failed_source_records_the_error_and_never_fakes_a_value(): void
    {
        $this->enable('ico_daily');
        Http::fake([self::ICO_URL => Http::response('down', 503)]);

        $this->artisan('market:fetch', ['source' => 'ico_daily'])->assertFailed();

        $source = MarketSource::where('slug', 'ico_daily')->first();
        $this->assertStringContainsString('503', $source->last_error);
        $this->assertSame(0, MarketPrice::count());
        $this->getJson('/api/market/overview')->assertJsonPath('data.benchmarks.1.value', null);
    }

    public function test_the_same_source_time_is_stored_once(): void
    {
        $this->enable('ico_daily');
        $this->fakeIco([$this->icoRow('2026-11-12')]);
        $service = app(MarketIngestionService::class);

        $service->fetch('ico_daily');
        $second = collect($service->fetch('ico_daily')[0]['rows']);

        $this->assertSame(5, MarketPrice::count());
        $this->assertTrue($second->every(fn ($row) => $row['status'] === 'duplicate'));
    }

    public function test_a_running_fetch_holds_a_lock_so_a_second_one_does_not_overlap(): void
    {
        $this->enable('ico_daily');
        Http::fake();
        $lock = Cache::lock('market:fetch:ico_daily', 120);
        $lock->get();

        $result = app(MarketIngestionService::class)->fetch('ico_daily');

        $this->assertSame('locked', $result[0]['status']);
        Http::assertNothingSent();
        $lock->release();
    }

    public function test_old_prices_turn_stale_then_unavailable_and_are_never_live(): void
    {
        $this->enable('api_ninjas_coffee');
        $this->storePublished('KC_ACTIVE', 279.25, '2026-11-16 11:40:00'); // 20 min old, max age 30

        $hero = fn () => $this->getJson('/api/market/overview')->json('data.hero');
        $this->assertSame('delayed', $hero()['freshness']['status']);
        $this->assertSame(20, $hero()['freshness']['delay_minutes']);

        $this->travel(30)->minutes();
        Cache::flush();
        $stale = $hero();
        $this->assertSame('stale', $stale['freshness']['status']);
        $this->assertSame('279.25000000', $stale['value'], 'a stale value is shown only with its Stale label and real time');
        $this->assertSame('2026-11-16T11:40:00+00:00', $stale['source_time']);

        $this->travel(60)->minutes();
        Cache::flush();
        $gone = $hero();
        $this->assertSame('unavailable', $gone['freshness']['status']);
        $this->assertNull($gone['value']);
    }

    public function test_turning_a_source_off_makes_its_prices_unavailable(): void
    {
        $source = $this->enable('ico_daily');
        $this->storePublished('ICO_COMPOSITE', 249.09, '2026-11-15 00:00:00');
        $source->forceFill(['enabled' => false])->save();
        MarketReadService::forgetCache();

        $row = collect($this->getJson('/api/market/overview')->json('data.benchmarks'))->firstWhere('symbol', 'ICO_COMPOSITE');
        $this->assertNull($row['value']);
        $this->assertSame('unavailable', $row['freshness']['status']);
    }

    public function test_trend_needs_twenty_daily_closes_and_uses_one_close_per_day(): void
    {
        $this->enable('ico_daily');
        $series = MarketSeries::where('symbol', 'ICO_COMPOSITE')->first();
        $trends = app(\App\Services\Market\MarketTrendService::class);

        // Many values on one day count as one close.
        for ($i = 0; $i < 30; $i++) {
            $this->storePublished('ICO_COMPOSITE', 200 + $i, '2026-11-15 ' . sprintf('%02d', intdiv($i, 2)) . ':' . ($i % 2 ? '30' : '00') . ':00');
        }
        $this->assertSame('not_enough_data', $trends->compute($series)->trend);

        MarketPrice::query()->delete();
        for ($day = 25; $day >= 1; $day--) {
            $this->storePublished('ICO_COMPOSITE', 300 - $day * 2, now()->subDays($day)->startOfDay()->toDateTimeString());
        }
        $trend = $trends->compute($series);
        $this->assertSame('bullish', $trend->trend);
        $this->assertSame(25, $trend->sample_size);
        $this->assertSame('daily_close_utc', $trend->inputs['basis']);

        MarketPrice::query()->delete();
        for ($day = 25; $day >= 1; $day--) {
            $this->storePublished('ICO_COMPOSITE', 250 + ($day % 2), now()->subDays($day)->startOfDay()->toDateTimeString());
        }
        $this->assertSame('sideways', $trends->compute($series)->trend);
    }

    public function test_series_endpoint_returns_published_points_with_times_and_validates_input(): void
    {
        $this->enable('ico_daily');
        $this->storePublished('ICO_COMPOSITE', 248.00, '2026-11-11 00:00:00');
        $this->storePublished('ICO_COMPOSITE', 249.09, '2026-11-12 00:00:00');
        $held = $this->storePublished('ICO_COMPOSITE', 999.00, '2026-11-13 00:00:00');
        $held->forceFill(['status' => 'needs_review'])->save();

        $points = $this->getJson('/api/market/series?symbol=ICO_COMPOSITE&range=1M')->assertOk()->json('data.points');
        $this->assertSame([['value' => '248.00000000', 'source_time' => '2026-11-11T00:00:00+00:00'], ['value' => '249.09000000', 'source_time' => '2026-11-12T00:00:00+00:00']], $points);

        $this->getJson('/api/market/series?symbol=ICO_COMPOSITE&range=5Y')->assertStatus(422);
        $this->getJson('/api/market/series?symbol=NOPE&range=1M')->assertNotFound();
    }

    public function test_sources_endpoint_lists_attribution(): void
    {
        $names = collect($this->getJson('/api/market/sources')->assertOk()->json('data'))->pluck('attribution');
        $this->assertContains('International Coffee Organization (ICO)', $names);
        $this->assertContains('Ethiopia Commodity Exchange (ECX)', $names);
    }

    public function test_old_generated_price_endpoints_return_unavailable_without_numbers(): void
    {
        foreach (['/api/market/chart-data', '/api/market/live-tick', '/api/market/ticker'] as $url) {
            $response = $this->getJson($url)->assertStatus(410)->assertJsonPath('status', 'unavailable');
            $this->assertSame(['success', 'status', 'message', 'use'], array_keys($response->json()), $url);
        }
    }

    public function test_home_and_toolkit_no_longer_send_unsourced_or_estimated_prices(): void
    {
        \App\Models\CoffeeMarketBenchmark::query()->create([
            'benchmark_name' => 'Seeded benchmark', 'market_date' => now()->toDateString(),
            'arabica_c_cents_lb' => 248.50, 'differential_cents_lb' => 12.25, 'indicative_fob_usd_lb' => 2.61,
            'ecx_reference_etb_per_kg' => 512.75, 'grade_description' => 'Seeded', 'source' => 'ICE', 'trend' => 'up',
        ]);

        $this->getJson('/api/home')->assertOk()->assertJsonPath('data.market_items', []);
        $this->getJson('/api/coffee-toolkit/hub-summary')->assertOk()->assertJsonPath('data.market_benchmarks', []);
        $intel = $this->getJson('/api/coffee-toolkit/market-intelligence')->assertOk();
        $intel->assertJsonPath('macro', null)->assertJsonPath('data', []);
        $this->assertStringNotContainsString('248.5', $intel->getContent());
    }

    public function test_public_market_endpoints_are_rate_limited_per_ip(): void
    {
        config(['market.public_rate_per_minute' => 3]);
        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/market/overview')->assertOk();
        }
        $this->getJson('/api/market/overview')->assertStatus(429);
    }

    public function test_amharic_freshness_labels(): void
    {
        $this->getJson('/api/market/overview', ['Accept-Language' => 'am'])
            ->assertJsonPath('data.hero.freshness.label', 'አይገኝም');
    }

    public function test_the_provider_key_never_appears_in_responses(): void
    {
        $this->enable('api_ninjas_coffee');
        config(['market.api_ninjas_key' => 'secret-key-should-not-leak']);
        Http::fake([self::NINJAS_URL => Http::response(['exchange' => 'ICE', 'name' => 'Coffee', 'price' => 279.25, 'updated' => now()->subMinutes(16)->timestamp])]);

        app(MarketIngestionService::class)->fetch('api_ninjas_coffee');

        Http::assertSent(fn ($request) => $request->hasHeader('X-Api-Key', 'secret-key-should-not-leak'));
        foreach (['/api/market/overview', '/api/market/sources', '/api/market/series?symbol=KC_ACTIVE&range=1D'] as $url) {
            $this->assertStringNotContainsString('secret-key-should-not-leak', $this->getJson($url)->getContent());
        }
        $this->assertSame('delayed', $this->getJson('/api/market/overview')->json('data.hero.freshness.status'));
    }

    public function test_missing_provider_key_fails_closed(): void
    {
        $this->enable('api_ninjas_coffee');
        config(['market.api_ninjas_key' => null]);
        Http::fake();

        $result = app(MarketIngestionService::class)->fetch('api_ninjas_coffee')[0];

        $this->assertSame('failed', $result['status']);
        $this->assertStringContainsString('API_NINJAS_API_KEY', $result['error']);
        Http::assertNothingSent();
    }

    public function test_scheduled_run_fetches_only_enabled_sources_that_are_due(): void
    {
        $ico = $this->enable('ico_daily');
        $ico->forceFill(['last_attempt_at' => now()->subMinutes(10)])->save(); // refresh is daily
        $service = app(MarketIngestionService::class);

        $this->assertSame([], $service->fetch());

        $ico->forceFill(['last_attempt_at' => now()->subDay()])->save();
        $this->fakeIco([$this->icoRow('2026-11-12')]);
        $this->assertSame(['ico_daily'], collect($service->fetch())->pluck('source')->all());

        $ninjas = MarketSource::where('slug', 'api_ninjas_coffee')->first();
        $this->assertFalse($service->isDue($ninjas, CarbonImmutable::parse('2026-11-15 12:00:00')), 'futures are not fetched on weekends');
    }

    public function test_admin_review_needs_staff_and_a_reason_and_is_audited(): void
    {
        $this->enable('ico_daily');
        $held = $this->storePublished('ICO_COMPOSITE', 400.00, '2026-11-13 00:00:00');
        $held->forceFill(['status' => 'needs_review', 'review_reason' => 'Change too large'])->save();

        $student = $this->makeUser();
        $this->actingAs($student)->post(route('admin.market-data.approve', $held), ['reason' => 'Checked against ICO PDF'])->assertForbidden();

        $admin = $this->makeUser('admin');
        $this->actingAsStaff($admin)->get(route('admin.market-data.index'))->assertOk()->assertSee('Change too large');
        $this->actingAsStaff($admin)->post(route('admin.market-data.approve', $held), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->actingAsStaff($admin)->post(route('admin.market-data.approve', $held), ['reason' => 'Checked against ICO PDF'])->assertRedirect();

        $held->refresh();
        $this->assertSame('published', $held->status);
        $this->assertSame($admin->id, $held->reviewed_by);
        $this->assertTrue(AuditLog::where('action', 'market_price_approved')->where('auditable_id', $held->id)->exists());
        $this->actingAsStaff($admin)->post(route('admin.market-data.reject', $held), ['reason' => 'Second try'])->assertStatus(409);
    }

    public function test_admin_can_switch_a_source_and_it_is_audited(): void
    {
        $admin = $this->makeUser('admin');
        $source = MarketSource::where('slug', 'ico_daily')->first();

        $this->actingAsStaff($admin)->post(route('admin.market-data.toggle', $source))->assertRedirect();

        $this->assertTrue($source->fresh()->enabled);
        $this->assertTrue(AuditLog::where('action', 'market_source_toggled')->where('auditable_id', $source->id)->exists());
    }

    /** Opt-in: MARKET_LIVE_SMOKE_TESTS=true php artisan test --filter=live_smoke */
    public function test_live_smoke_ico_returns_a_parseable_row(): void
    {
        if (!config('market.live_smoke_tests')) {
            $this->markTestSkipped('Live market smoke test is opt-in (MARKET_LIVE_SMOKE_TESTS=true).');
        }
        CarbonImmutable::setTestNow();
        \Illuminate\Support\Carbon::setTestNow();
        $result = app(MarketIngestionService::class)->fetch('ico_daily', true)[0];
        $this->assertSame('success', $result['status'], $result['error'] ?? '');
        $this->assertNotEmpty($result['rows']);
    }
}
