<?php

namespace Tests\Feature\Market;

use App\Models\CoffeeMinimumPriceDataset;
use App\Models\CoffeeMinimumPriceRecord;
use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Models\User;
use App\Notifications\FiraNotification;
use App\Services\Market\EctaScheduleSync;
use App\Services\Market\ManualPriceService;
use App\Services\Market\MarketIngestionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Market watch, slice 1 (2026-11-27): /api/market/watch rows built only from stored, published prices,
 * the ECTA schedule from published price lists, the hand-typed NBE rate with a second reviewer,
 * and the staff alert when a source misses two fetch cycles.
 */
class MarketWatchTest extends TestCase
{
    use DatabaseTransactions;

    private const ROW_KEYS = [
        'key', 'name', 'short_name', 'kind', 'currency', 'unit', 'unit_label', 'decimals', 'price', 'prev_close',
        'change', 'change_pct', 'direction', 'trend', 'status', 'status_label', 'as_of', 'source', 'sparkline',
        'spark_ref', 'on_home', 'ranges', 'units', 'a11y_label',
    ];

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

    private function enable(string $slug, array $extra = []): MarketSource
    {
        $source = MarketSource::where('slug', $slug)->firstOrFail();
        $source->forceFill(['enabled' => true] + $extra)->save();

        return $source;
    }

    private function store(string $symbol, float $value, string $time, string $status = 'published'): MarketPrice
    {
        $series = MarketSeries::where('symbol', $symbol)->firstOrFail();

        return MarketPrice::create([
            'series_id' => $series->id, 'value' => $value, 'currency' => $series->currency, 'unit' => $series->unit,
            'source_time' => $time, 'fetched_at' => now(), 'status' => $status,
        ]);
    }

    /** One published close per day, ending on $lastDay with $last, rising $step a day. */
    private function history(string $symbol, int $days, float $last, float $step, string $lastDay = '2026-11-16'): void
    {
        for ($i = $days - 1; $i >= 0; $i--) {
            $this->store($symbol, round($last - $i * $step, 4), CarbonImmutable::parse($lastDay)->subDays($i)->toDateString() . ' 00:00:00');
        }
    }

    private function watch(array $headers = []): array
    {
        Cache::flush();

        return $this->getJson('/api/market/watch', $headers)->assertOk()->json('data');
    }

    private function row(array $data, string $key): array
    {
        $row = collect($data['rows'])->firstWhere('key', $key);
        $this->assertNotNull($row, "Row {$key} is missing.");

        return $row;
    }

    private function ectaList(string $effective, float $price, string $status = 'published'): CoffeeMinimumPriceDataset
    {
        $dataset = CoffeeMinimumPriceDataset::query()->create([
            'title' => "ECTA list {$effective}", 'file_name' => "ecta-{$effective}.xlsx", 'effective_date' => $effective, 'status' => $status,
        ]);
        CoffeeMinimumPriceRecord::query()->create([
            'dataset_id' => $dataset->id, 'coffee_type' => 'Arabica', 'origin' => 'Sidama', 'grade' => 'G2', 'processing_method' => 'Washed',
            'minimum_price' => $price, 'price_unit' => 'USD/lb', 'currency' => 'USD', 'effective_date' => $effective, 'status' => 'valid',
        ]);

        return $dataset;
    }

    private function reviewer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('reviewer');

        return $user;
    }

    // ── Rows and honesty ────────────────────────────────────────────────────

    public function test_with_every_source_off_every_row_is_unavailable_and_has_no_number(): void
    {
        $data = $this->watch();

        $this->assertSame(1, $data['schema']);
        $this->assertSame('unavailable', $data['status']['status']);
        $this->assertSame([], $data['sources']);
        $this->assertFalse($data['home']['visible']);
        $this->assertSame(['ICO_COMPOSITE', 'ETB_USD', 'KC_ACTIVE'], $data['home']['keys']);
        $this->assertSame('Market information only, not trading advice.', $data['disclaimer']);
        $this->assertNotContains('ECTA_MIN_EXPORT', array_column($data['rows'], 'key'), 'The feedless ECTA series is replaced by per-grade rows.');
        foreach ($data['rows'] as $row) {
            $this->assertSame(self::ROW_KEYS, array_keys($row), "{$row['key']}: row contract changed.");
            $this->assertSame('unavailable', $row['status'], $row['key']);
            foreach (['price', 'prev_close', 'change', 'change_pct', 'direction', 'trend', 'as_of', 'spark_ref'] as $field) {
                $this->assertNull($row[$field], "{$row['key']}.{$field}");
            }
            $this->assertSame([], $row['sparkline'], $row['key']);
            $this->assertStringContainsString('unavailable', $row['a11y_label']);
        }
    }

    public function test_daily_history_gives_price_previous_close_change_and_sparkline(): void
    {
        $this->enable('ico_daily');
        $this->history('ICO_COMPOSITE', 35, 249.09, 1.84);

        $data = $this->watch();
        $row = $this->row($data, 'ICO_COMPOSITE');

        $this->assertSame(249.09, $row['price']);
        $this->assertSame(247.25, $row['prev_close']);
        $this->assertSame(1.84, $row['change']);
        $this->assertSame(0.74, $row['change_pct']);
        $this->assertSame('up', $row['direction']);
        $this->assertSame('daily_close', $row['status']);
        $this->assertSame('Daily close · 2026-11-16', $row['status_label']);
        $this->assertSame('US¢/lb', $row['unit_label']);
        $this->assertCount(30, $row['sparkline']);
        $this->assertSame(249.09, end($row['sparkline']));
        $this->assertSame($row['prev_close'], $row['spark_ref']);
        $this->assertSame('International Coffee Organization (ICO)', $row['source']['attribution']);
        $this->assertSame('ICO composite, 249.09 US cents per pound, up 1.84, 0.74 percent, Daily close · 2026-11-16.', $row['a11y_label']);
        $this->assertTrue($data['home']['visible']);
        $this->assertSame(['International Coffee Organization (ICO)'], $data['sources']);
        $this->assertSame('daily_close', $data['status']['status']);
    }

    public function test_down_and_unchanged_rows(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COMPOSITE', 250.00, '2026-11-13 00:00:00');
        $this->store('ICO_COMPOSITE', 248.50, '2026-11-16 00:00:00');
        $this->store('ICO_ROBUSTAS', 157.29, '2026-11-13 00:00:00');
        $this->store('ICO_ROBUSTAS', 157.29, '2026-11-16 00:00:00');

        $data = $this->watch();
        $down = $this->row($data, 'ICO_COMPOSITE');
        $flat = $this->row($data, 'ICO_ROBUSTAS');

        $this->assertSame(['down', -1.5, -0.6], [$down['direction'], $down['change'], $down['change_pct']]);
        $this->assertStringContainsString('down 1.50, 0.60 percent', $down['a11y_label']);
        $this->assertSame(['flat', 0.0], [$flat['direction'], $flat['change']]);
        $this->assertStringContainsString('unchanged', $flat['a11y_label']);
    }

    public function test_a_single_price_has_no_change_yet(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COMPOSITE', 249.09, '2026-11-16 00:00:00');

        $row = $this->row($this->watch(), 'ICO_COMPOSITE');

        $this->assertSame(249.09, $row['price']);
        $this->assertNull($row['prev_close']);
        $this->assertNull($row['direction']);
        $this->assertStringContainsString('no previous close yet', $row['a11y_label']);
    }

    public function test_held_and_rejected_prices_never_show(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COMPOSITE', 249.09, '2026-11-15 00:00:00');
        $this->store('ICO_COMPOSITE', 999.00, '2026-11-16 00:00:00', 'needs_review');
        $this->store('ICO_ROBUSTAS', 1.00, '2026-11-16 00:00:00', 'rejected');

        $data = $this->watch();

        $this->assertSame(249.09, $this->row($data, 'ICO_COMPOSITE')['price']);
        $this->assertNull($this->row($data, 'ICO_ROBUSTAS')['price']);
        $this->assertStringNotContainsString('999', json_encode($data));
    }

    public function test_an_old_price_is_stale_with_its_date_then_unavailable(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COMPOSITE', 249.09, '2026-11-09 00:00:00');
        $this->store('ICO_ROBUSTAS', 157.29, '2026-10-20 00:00:00');

        $data = $this->watch();
        $stale = $this->row($data, 'ICO_COMPOSITE');
        $gone = $this->row($data, 'ICO_ROBUSTAS');

        $this->assertSame('stale', $stale['status']);
        $this->assertSame(249.09, $stale['price']);
        $this->assertSame('2026-11-09T00:00:00+00:00', $stale['as_of']);
        $this->assertSame('unavailable', $gone['status']);
        $this->assertNull($gone['price']);
    }

    public function test_delayed_futures_never_say_live_unless_the_source_is_real_time(): void
    {
        $this->enable('api_ninjas_coffee');
        $this->store('KC_ACTIVE', 372.40, '2026-11-15 18:00:00');
        $this->store('KC_ACTIVE', 375.10, '2026-11-16 11:40:00');

        $row = $this->row($this->watch(), 'KC_ACTIVE');
        $this->assertSame('delayed', $row['status']);
        $this->assertSame('Delayed 20 min', $row['status_label']);
        $this->assertSame(372.4, $row['prev_close'], 'Previous close is the last price of the day before.');
        $this->assertStringNotContainsString('Live', json_encode($this->watch()));

        $this->enable('api_ninjas_coffee', ['configuration' => ['real_time' => true, 'skip_weekends' => true]]);
        $this->assertSame('live', $this->row($this->watch(), 'KC_ACTIVE')['status']);
    }

    public function test_home_card_shows_only_once_a_home_row_has_a_value(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COLOMBIAN_MILDS', 339.70, '2026-11-16 00:00:00');
        $this->assertFalse($this->watch()['home']['visible'], 'Colombian milds is not a Home row.');

        $this->store('ICO_COMPOSITE', 249.09, '2026-11-16 00:00:00');
        $this->assertTrue($this->watch()['home']['visible']);
    }

    public function test_amharic_labels_keep_latin_numbers(): void
    {
        $this->enable('ico_daily');
        $this->history('ICO_COMPOSITE', 3, 249.09, 1.84);

        $data = $this->watch(['Accept-Language' => 'am']);
        $row = $this->row($data, 'ICO_COMPOSITE');

        $this->assertSame('ICO አጠቃላይ', $row['short_name']);
        $this->assertSame('የቀን መዝጊያ · 2026-11-16', $row['status_label']);
        $this->assertSame('የገበያ መረጃ ብቻ ነው፤ የንግድ ምክር አይደለም።', $data['disclaimer']);
        $this->assertStringContainsString('249.09', $row['a11y_label']);
        $this->assertSame(249.09, $row['price']);
    }

    // ── ECTA minimum prices from published lists ────────────────────────────

    public function test_ecta_rows_come_from_published_lists_with_an_official_schedule_label(): void
    {
        $this->enable('ecta_lists');
        $this->ectaList('2026-11-03', 3.85);
        $second = $this->ectaList('2026-11-10', 3.95);
        $this->ectaList('2026-11-14', 4.50, 'draft');

        $grades = app(EctaScheduleSync::class)->grades();
        $this->assertCount(1, $grades);
        $this->assertSame('Arabica|Sidama|G2|Washed', $grades[0]['key']);
        $this->assertSame(3.95, $grades[0]['latest_price'], 'The draft list is ignored.');

        $this->artisan('market:ecta-grades', ['--track' => 'Arabica|Sidama|G2|Washed', '--home' => true])->assertSuccessful();
        $this->artisan('market:fetch', ['source' => 'ecta_lists'])->assertSuccessful();

        $data = $this->watch();
        $symbol = EctaScheduleSync::symbolFor(['coffee_type' => 'Arabica', 'origin' => 'Sidama', 'grade' => 'G2', 'processing_method' => 'Washed']);
        $row = $this->row($data, $symbol);
        $this->assertSame(['schedule', 3.95, 3.85, 0.1, 'up'], [$row['status'], $row['price'], $row['prev_close'], $row['change'], $row['direction']]);
        $this->assertSame('Official schedule · effective 2026-11-10', $row['status_label']);
        $this->assertSame('USD/lb', $row['unit_label']);
        $this->assertSame(['ICO_COMPOSITE', $symbol, 'ETB_USD', 'KC_ACTIVE'], $data['home']['keys']);
        $this->assertTrue($data['home']['visible']);

        // Unpublishing a list withdraws its price at once (dataset observer).
        $second->update(['status' => 'archived']);
        $row = $this->row($this->watch(), $symbol);
        $this->assertSame(3.85, $row['price']);
        $this->assertSame('Official schedule · effective 2026-11-03', $row['status_label']);
        $this->assertSame('withdrawn', MarketPrice::where('value', 3.95)->value('status'));
    }

    public function test_ecta_lists_are_not_stored_while_the_source_is_off_and_dry_run_writes_nothing(): void
    {
        $this->ectaList('2026-11-10', 3.95);
        $series = app(EctaScheduleSync::class)->track(['coffee_type' => 'Arabica', 'origin' => 'Sidama', 'grade' => 'G2', 'processing_method' => 'Washed']);
        $this->ectaList('2026-11-12', 4.05);

        $this->artisan('market:fetch', ['source' => 'ecta_lists', '--dry-run' => true])
            ->expectsOutputToContain('4.05')
            ->assertSuccessful();
        $this->artisan('market:fetch', ['source' => 'ecta_lists'])->expectsOutputToContain('disabled');

        $this->assertSame(0, $series->prices()->count());
        $this->assertNull($this->row($this->watch(), $series->symbol)['price']);
    }

    public function test_an_ecta_list_in_another_unit_is_rejected(): void
    {
        $this->enable('ecta_lists');
        $dataset = CoffeeMinimumPriceDataset::query()->create(['title' => 'x', 'file_name' => 'x.xlsx', 'effective_date' => '2026-11-10', 'status' => 'published']);
        CoffeeMinimumPriceRecord::query()->create([
            'dataset_id' => $dataset->id, 'coffee_type' => 'Arabica', 'origin' => 'Guji', 'grade' => 'G1', 'processing_method' => 'Natural',
            'minimum_price' => 8.5, 'price_unit' => 'USD/kg', 'currency' => 'USD', 'effective_date' => '2026-11-10', 'status' => 'valid',
        ]);
        $series = app(EctaScheduleSync::class)->track(['coffee_type' => 'Arabica', 'origin' => 'Guji', 'grade' => 'G1', 'processing_method' => 'Natural']);

        $rows = app(EctaScheduleSync::class)->sync($series);

        $this->assertSame('rejected', $rows[0]['status']);
        $this->assertSame(0, $series->prices()->count());
    }

    // ── NBE rate typed in by hand ───────────────────────────────────────────

    public function test_a_typed_in_rate_waits_for_a_second_reviewer(): void
    {
        $this->enable('nbe_fx');
        $alice = $this->reviewer();
        $bob = $this->reviewer();

        $this->artisan('market:enter-price', [
            'symbol' => 'ETB_USD', 'value' => '155.4321', '--date' => '2026-11-16',
            '--source-url' => 'https://nbe.gov.et/exchange/indicatives-rates/', '--user' => $alice->email,
        ])->assertSuccessful();

        $price = MarketPrice::query()->whereHas('series', fn ($q) => $q->where('symbol', 'ETB_USD'))->firstOrFail();
        $this->assertSame('needs_review', $price->status);
        $this->assertSame($alice->id, $price->raw_ref['entered_by']);
        $this->assertNull($this->row($this->watch(), 'ETB_USD')['price'], 'Not shown before review.');

        $this->assertFalse(Gate::forUser($alice)->allows('review', $price), 'Nobody reviews their own entry.');
        $this->assertTrue(Gate::forUser($bob)->allows('review', $price));

        $price->update(['status' => 'published', 'reviewed_by' => $bob->id, 'reviewed_at' => now(), 'review_reason' => 'Matches the NBE page.']);
        $row = $this->row($this->watch(), 'ETB_USD');
        $this->assertSame(155.4321, $row['price']);
        $this->assertSame(4, $row['decimals']);
        $this->assertSame('ETB per USD', $row['unit_label']);
        $this->assertSame('National Bank of Ethiopia (NBE)', $row['source']['attribution']);
    }

    public function test_typed_in_rates_are_checked(): void
    {
        $alice = $this->reviewer();
        $manual = app(ManualPriceService::class);
        $fx = MarketSeries::where('symbol', 'ETB_USD')->firstOrFail();

        $cases = [
            'http link' => fn () => $manual->submit($fx, '155.4', '2026-11-16', 'http://nbe.gov.et/', $alice),
            'future date' => fn () => $manual->submit($fx, '155.4', '2026-11-17', 'https://nbe.gov.et/', $alice),
            'bad date' => fn () => $manual->submit($fx, '155.4', '16/11/2026', 'https://nbe.gov.et/', $alice),
            'out of range' => fn () => $manual->submit($fx, '-3', '2026-11-16', 'https://nbe.gov.et/', $alice),
            'fetched series' => fn () => $manual->submit(MarketSeries::where('symbol', 'ICO_COMPOSITE')->first(), '249', '2026-11-16', 'https://ico.org/', $alice),
            'not a reviewer' => fn () => $manual->submit($fx, '155.4', '2026-11-16', 'https://nbe.gov.et/', User::factory()->create()),
        ];
        foreach ($cases as $label => $attempt) {
            try {
                $attempt();
                $this->fail("Accepted: {$label}");
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
        $this->assertSame(0, $fx->prices()->count());
    }

    // ── Missed fetch cycles ─────────────────────────────────────────────────

    public function test_a_source_that_misses_two_cycles_alerts_staff_once_per_outage(): void
    {
        config(['market.admin_email' => 'market-desk@example.test']);
        Notification::fake();
        $this->enable('ico_daily', ['last_success_at' => now()->subDays(3), 'last_attempt_at' => now()->subDay()]);
        Http::fake(['https://data.ico.org/*' => Http::response('down', 500)]);

        $this->artisan('market:fetch')->assertFailed();
        // Next scheduled run: nothing is due yet, and the same outage is not reported twice.
        $this->artisan('market:fetch')->assertSuccessful();

        Notification::assertSentOnDemandTimes(FiraNotification::class, 1);
        Notification::assertSentOnDemand(FiraNotification::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'market-desk@example.test'
            && $n->type === \App\Notifications\Catalog\NotificationType::StaffMarketSourceDown);

        // It recovers, then goes down again: a new outage, a new alert.
        MarketSource::where('slug', 'ico_daily')->first()->forceFill(['last_success_at' => now(), 'last_error' => null])->save();
        $this->travel(3)->days();
        MarketSource::where('slug', 'ico_daily')->first()->forceFill(['last_error' => 'HTTP 500', 'last_attempt_at' => now()])->save();
        $this->assertSame(['ico_daily'], app(MarketIngestionService::class)->alertOutages(CarbonImmutable::now('UTC')));
    }

    public function test_a_recent_success_or_a_manual_source_never_alerts(): void
    {
        config(['market.admin_email' => 'market-desk@example.test']);
        Notification::fake();
        $this->enable('ico_daily', ['last_success_at' => now()->subHours(30), 'last_attempt_at' => now(), 'last_error' => 'HTTP 500']);
        $this->enable('nbe_fx', ['last_attempt_at' => now()->subDays(9), 'last_error' => 'x']);

        $this->assertSame([], app(MarketIngestionService::class)->alertOutages(CarbonImmutable::now('UTC')));
        Notification::assertNothingSent();
    }

    // ── HTTP contract ───────────────────────────────────────────────────────

    public function test_watch_is_public_cached_and_answers_304_for_an_unchanged_version(): void
    {
        $first = $this->getJson('/api/market/watch')->assertOk();
        $etag = $first->headers->get('ETag');

        $this->assertNotEmpty($etag);
        $this->assertStringContainsString('public', $first->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=300', $first->headers->get('Cache-Control'));
        $this->assertStringContainsString('Accept-Language', $first->headers->get('Vary'));

        $second = $this->get('/api/market/watch', ['If-None-Match' => $etag]);
        $second->assertStatus(304);
        $this->assertSame('', $second->getContent());
    }

    public function test_watch_is_rate_limited_per_ip(): void
    {
        config(['market.public_rate_per_minute' => 3]);
        for ($i = 0; $i < 3; $i++) {
            $this->getJson('/api/market/watch')->assertOk();
        }
        $this->getJson('/api/market/watch')->assertStatus(429);
    }

    public function test_one_day_range_only_for_an_intraday_source_that_is_on(): void
    {
        $this->getJson('/api/market/series?symbol=ICO_COMPOSITE&range=1D')->assertStatus(422)->assertJsonPath('code', 'RANGE_UNAVAILABLE');
        $this->getJson('/api/market/series?symbol=KC_ACTIVE&range=1D')->assertStatus(422);

        $this->enable('api_ninjas_coffee');
        $this->getJson('/api/market/series?symbol=KC_ACTIVE&range=1D')->assertOk();
        $this->getJson('/api/market/series?symbol=ICO_COMPOSITE&range=1M')->assertOk();
    }

    public function test_payload_stays_under_8_kb_gzipped_with_every_row_full(): void
    {
        foreach (['ico_daily', 'api_ninjas_coffee', 'ecx_official', 'nbe_fx'] as $slug) {
            $this->enable($slug);
        }
        foreach (MarketSeries::where('enabled', true)->get() as $series) {
            $this->history($series->symbol, 31, (float) $series->minimum_value * 3.123457, 0.731, '2026-11-15');
        }
        // Futures may be at most 30 minutes old; daily sources a few days.
        foreach (MarketSeries::where('enabled', true)->get() as $series) {
            $this->store($series->symbol, (float) $series->minimum_value * 3.2, '2026-11-16 11:50:00');
        }

        $body = $this->getJson('/api/market/watch')->assertOk()->getContent();
        $rows = json_decode($body, true)['data']['rows'];

        $this->assertCount(8, array_filter($rows, fn ($r) => $r['price'] !== null));
        $this->assertLessThan(8192, strlen(gzencode($body, 6)), 'Watch payload must stay under 8 KB gzipped.');
    }
}
