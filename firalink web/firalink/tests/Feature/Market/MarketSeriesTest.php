<?php

namespace Tests\Feature\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/** Market watch slice 4: instrument detail (points, ranges, stats, units with the NBE rate). */
class MarketSeriesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
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

    private function enable(string $slug): void
    {
        MarketSource::where('slug', $slug)->firstOrFail()->forceFill(['enabled' => true])->save();
    }

    private function store(string $symbol, float $value, string $time): void
    {
        $s = MarketSeries::where('symbol', $symbol)->firstOrFail();
        MarketPrice::create(['series_id' => $s->id, 'value' => $value, 'currency' => $s->currency, 'unit' => $s->unit, 'source_time' => $time, 'fetched_at' => now(), 'status' => 'published']);
    }

    /** $days daily closes ending today: 200, 201, ... */
    private function history(int $days): void
    {
        for ($i = 0; $i < $days; $i++) {
            $this->store('ICO_COMPOSITE', 200 + $i, CarbonImmutable::parse('2026-11-16')->subDays($days - 1 - $i)->toDateString() . ' 00:00:00');
        }
    }

    private function detail(string $query = ''): array
    {
        Cache::flush();

        return $this->getJson('/api/market/instruments/ICO_COMPOSITE/series' . $query)->assertOk()->json('data');
    }

    public function test_points_ranges_and_statistics_from_stored_closes(): void
    {
        $this->enable('ico_daily');
        $this->history(400);

        $month = $this->detail('?range=1M');
        $this->assertSame(1, $month['schema']);
        $this->assertSame(['1W', '1M', '3M', '1Y', '5Y'], $month['ranges'], 'No 1D for a daily source.');
        $this->assertCount(31, $month['points']);
        $this->assertSame(['t' => '2026-11-16T00:00:00+00:00', 'v' => 599.0], end($month['points']));
        $this->assertSame(599.0, $month['price']);
        $this->assertSame(598.0, $month['stats']['prev_close']);
        $this->assertSame(599.0, $month['stats']['high_52w']);
        $this->assertSame(234.0, $month['stats']['low_52w'], 'Only the last 365 days count.');
        $this->assertSame(589.5, $month['stats']['avg_20d']);
        $this->assertSame('usc_lb', $month['unit']['key']);
        $this->assertNull($month['fx']);

        $this->assertCount(400, $this->detail('?range=5Y')['points']);
        $this->assertCount(7, $this->detail('?range=1W')['points']);
    }

    public function test_units_convert_and_birr_needs_a_current_nbe_rate(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COMPOSITE', 249.09, '2026-11-16 00:00:00');

        $usdKg = $this->detail('?unit=usd_kg');
        $this->assertSame(5.4915, $usdKg['price'], '249.09 US cents/lb = 5.4915 USD/kg');
        $this->assertSame(['usc_lb', 'usd_lb', 'usd_kg'], array_column($usdKg['units'], 'key'), 'No ETB without an NBE rate.');

        $noRate = $this->detail('?unit=etb_kg');
        $this->assertSame('usc_lb', $noRate['unit']['key'], 'Asked for birr without a rate: stays in the stored unit.');
        $this->assertSame(249.09, $noRate['price']);

        $this->enable('nbe_fx');
        $this->store('ETB_USD', 150.0, '2026-11-16 00:00:00');
        $birr = $this->detail('?unit=etb_kg');
        $this->assertSame('etb_kg', $birr['unit']['key']);
        $this->assertSame(823.72, $birr['price'], '5.491494 USD/kg x 150');
        $this->assertSame(['rate' => 150.0, 'as_of' => '2026-11-16T00:00:00+00:00', 'source' => 'National Bank of Ethiopia (NBE)'], $birr['fx']);
        $this->assertContains('etb_kg', $this->getJson('/api/market/watch')->json('data.rows.0.units'));

        // An old rate is never used.
        MarketPrice::query()->whereHas('series', fn ($q) => $q->where('symbol', 'ETB_USD'))->update(['source_time' => '2026-11-01 00:00:00']);
        $this->assertSame('usc_lb', $this->detail('?unit=etb_kg')['unit']['key']);
    }

    public function test_one_day_only_for_an_intraday_source_and_bad_input_is_refused(): void
    {
        $this->getJson('/api/market/instruments/ICO_COMPOSITE/series?range=1D')->assertStatus(422)->assertJsonPath('code', 'RANGE_UNAVAILABLE');
        $this->getJson('/api/market/instruments/ICO_COMPOSITE/series?range=10Y')->assertStatus(422);
        $this->getJson('/api/market/instruments/ICO_COMPOSITE/series?unit=eur_kg')->assertStatus(422);
        $this->getJson('/api/market/instruments/NOPE/series')->assertNotFound();

        $this->enable('api_ninjas_coffee');
        $this->store('KC_ACTIVE', 372.4, '2026-11-16 11:50:00');
        $kc = $this->getJson('/api/market/instruments/KC_ACTIVE/series?range=1D')->assertOk()->json('data');
        $this->assertSame('1D', $kc['range']);
        $this->assertCount(1, $kc['points']);
    }

    public function test_unavailable_instrument_has_no_points_or_numbers(): void
    {
        $this->history(30); // stored, but the source is off

        $data = $this->detail();
        $this->assertSame('unavailable', $data['status']);
        $this->assertSame([], $data['points']);
        $this->assertNull($data['price']);
        $this->assertSame([null, null, null, null], array_values($data['stats']));
    }

    public function test_detail_is_public_cached_and_answers_304(): void
    {
        $first = $this->get('/api/market/instruments/ICO_COMPOSITE/series')->assertOk();
        $this->assertStringContainsString('max-age=300', $first->headers->get('Cache-Control'));
        $this->get('/api/market/instruments/ICO_COMPOSITE/series', ['If-None-Match' => $first->headers->get('ETag')])->assertStatus(304);
    }
}
