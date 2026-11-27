<?php

namespace Tests\Feature\Market;

use App\Models\MarketPrice;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\ApprovesBusinesses;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Market watch, slice 2 (2026-11-27): server-rendered rows on Home (below the hero) and Market Prices,
 * and the admin form for typing in the NBE rate with a second reviewer.
 */
class MarketWatchWebTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures, ApprovesBusinesses;

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

    private function store(string $symbol, float $value, string $time, string $status = 'published'): MarketPrice
    {
        $series = MarketSeries::where('symbol', $symbol)->firstOrFail();

        return MarketPrice::create([
            'series_id' => $series->id, 'value' => $value, 'currency' => $series->currency, 'unit' => $series->unit,
            'source_time' => $time, 'fetched_at' => now(), 'status' => $status,
        ]);
    }

    private function rowHtml(string $html, string $key): string
    {
        $at = strpos($html, 'data-symbol="' . $key . '"');
        $this->assertNotFalse($at, "Row {$key} is missing.");
        $start = strrpos(substr($html, 0, $at), '<li');

        return substr($html, $start, strpos($html, '</li>', $start) - $start);
    }

    public function test_home_has_no_market_band_until_a_home_row_has_a_price(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COLOMBIAN_MILDS', 339.70, '2026-11-16 00:00:00');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringNotContainsString('fira-mw-band', $html);
        $this->assertStringNotContainsString('339.70', $html);
    }

    public function test_home_band_shows_the_home_rows_server_rendered(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COMPOSITE', 247.25, '2026-11-13 00:00:00');
        $this->store('ICO_COMPOSITE', 249.09, '2026-11-16 00:00:00');
        $this->store('ICO_COLOMBIAN_MILDS', 339.70, '2026-11-16 00:00:00');

        $html = $this->get('/')->assertOk()->getContent();
        $band = substr($html, strpos($html, 'fira-mw-band'), 12000);

        $this->assertStringContainsString('assets/front/css/market-watch.css', $html);
        $this->assertStringContainsString('Coffee prices from named sources', $band);
        $this->assertStringContainsString('See all markets', $band);
        $this->assertStringContainsString('Market information only, not trading advice.', $band);
        $this->assertStringContainsString('Sources: ICO', $band);
        $this->assertStringNotContainsString('ICO_COLOMBIAN_MILDS', $band, 'Home shows only the Home rows.');
        $this->assertLessThan(strpos($band, 'data-symbol="ETB_USD"'), strpos($band, 'data-symbol="ICO_COMPOSITE"'));

        $row = $this->rowHtml($band, 'ICO_COMPOSITE');
        $this->assertStringContainsString('is-up', $row);
        $this->assertStringContainsString('249.09', $row);
        $this->assertStringContainsString('+1.84 (+0.74%)', $row);
        $this->assertStringContainsString('Daily close · <span class="fira-mw__nowrap">2026-11-16</span> · ICO', $row);
        $this->assertStringContainsString('ICO composite, 249.09 US cents per pound, up 1.84, 0.74 percent, Daily close · 2026-11-16.', $row);
        $this->assertStringContainsString('<svg viewBox="0 0 96 34"', $row);
        $this->assertStringContainsString('class="fira-mw__spark-ref"', $row);

        $unavailable = $this->rowHtml($band, 'ETB_USD');
        $this->assertStringContainsString('Unavailable', $unavailable);
        $this->assertStringNotContainsString('<svg viewBox="0 0 96 34"', $unavailable);
        $this->assertDoesNotMatchRegularExpression('/fira-mw__price">\s*[0-9]/', $unavailable);
    }

    public function test_market_prices_lists_every_row_and_details_the_selected_one(): void
    {
        $this->enable('ico_daily');
        for ($i = 0; $i < 25; $i++) {
            $this->store('ICO_COMPOSITE', 230 + $i, CarbonImmutable::parse('2026-11-16')->subDays(24 - $i)->toDateString() . ' 00:00:00');
        }
        $this->store('ICO_ROBUSTAS', 157.29, '2026-11-09 00:00:00');

        $html = $this->get('/market-prices')->assertOk()->getContent();

        $this->assertSame(8, substr_count($html, 'class="fira-mw__row '));
        $this->assertStringContainsString('id="market-rows"', $html);
        $ico = $this->rowHtml($html, 'ICO_COMPOSITE');
        $this->assertStringContainsString('href="' . e(route('market-prices.index', ['market' => 'ICO_COMPOSITE'])) . '"', $ico);
        $this->assertStringContainsString('aria-current="true"', $ico, 'The first row with a price is selected.');
        $this->assertStringNotContainsString('fira-mw__hit', $this->rowHtml($html, 'KC_ACTIVE'), 'An unavailable row is not a link.');
        $this->assertStringContainsString('Stale · last <span class="fira-mw__nowrap">2026-11-09</span>', $this->rowHtml($html, 'ICO_ROBUSTAS'));

        // Detail panel, server-rendered.
        $this->assertStringContainsString('id="market-detail"', $html);
        $this->assertStringContainsString('ICO Composite Indicator Price', $html);
        $this->assertStringContainsString('Source time: 2026-11-16', $html);
        $this->assertStringNotContainsString('data-range="1D"', $html, 'No 1D for a daily source.');
        $this->assertStringContainsString('data-range="5Y"', $html);
        $this->assertStringContainsString('<figcaption class="fira-mw__sr" id="fira-mwd-summary">Price chart for ICO composite, 1M:', $html);
        $this->assertStringContainsString('<summary>Prices as a table</summary>', $html);
        $this->assertStringContainsString('<dt>20-day average</dt><dd>244.50</dd>', $html);
        $this->assertStringContainsString('assets/front/js/market-watch.js', $html);
        $this->assertStringNotContainsString('>Live<', $html);
    }

    public function test_range_and_unit_work_without_javascript_and_as_a_panel_fragment(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COMPOSITE', 247.25, '2026-11-15 00:00:00');
        $this->store('ICO_COMPOSITE', 249.09, '2026-11-16 00:00:00');

        $page = $this->get('/market-prices?market=ICO_COMPOSITE&range=1Y&unit=usd_kg')->assertOk()->getContent();
        $this->assertStringContainsString('<span class="fira-mwd__price">5.4915</span>', $page);
        $this->assertMatchesRegularExpression('#aria-current="true"\s*>1Y</a>#', $page);

        $panel = $this->get('/market-prices/panel?market=ICO_COMPOSITE&range=3M')->assertOk()->getContent();
        $this->assertStringStartsWith('<section class="fira-mwd"', trim($panel));
        $this->assertStringContainsString('data-range="3M"', $panel);
        $this->assertStringNotContainsString('<html', $panel);

        // Unknown or unavailable choices fall back instead of failing.
        $this->get('/market-prices?market=NOPE&range=99Y&unit=eur')->assertOk()->assertSee('249.09');
    }

    public function test_one_day_chip_appears_for_an_intraday_source_that_is_on(): void
    {
        $this->enable('api_ninjas_coffee');
        $this->store('KC_ACTIVE', 372.40, '2026-11-16 11:50:00');

        $this->get('/market-prices?market=KC_ACTIVE')->assertOk()->assertSee('data-range="1D"', false);
    }

    public function test_home_rows_link_to_their_detail(): void
    {
        $this->enable('ico_daily');
        $this->store('ICO_COMPOSITE', 249.09, '2026-11-16 00:00:00');

        $band = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('href="' . e(route('market-prices.index', ['market' => 'ICO_COMPOSITE'])) . '" data-market="ICO_COMPOSITE"', $band);
        $this->assertStringNotContainsString('market-watch.js', $band, 'No chart script on Home.');
    }

    public function test_market_prices_in_amharic(): void
    {
        $this->get('/market-prices?lang=am')->assertOk()
            ->assertSee('የቡና ገበያ')
            ->assertSee('አይገኝም')
            ->assertSee('የገበያ መረጃ ብቻ ነው፤ የንግድ ምክር አይደለም።');
    }

    public function test_market_watch_css_uses_tokens_only(): void
    {
        $css = file_get_contents(public_path('assets/front/css/market-watch.css'));
        $tokens = file_get_contents(public_path('assets/front/css/fira-design-system.css'));

        $this->assertDoesNotMatchRegularExpression('/#[0-9a-fA-F]{3,8}\b/', $css);
        $this->assertDoesNotMatchRegularExpression('/\brgba?\(/', $css);
        preg_match_all('/var\((--fira-[a-z0-9-]+)/', $css, $used);
        foreach (array_unique($used[1]) as $token) {
            $this->assertStringContainsString($token . ':', $tokens, "Token {$token} is not defined.");
        }
        foreach (['components/market/watch', 'components/market/row', 'components/market/sparkline', 'components/market/detail', 'home/market-watch'] as $view) {
            $this->assertStringNotContainsString('style=', file_get_contents(resource_path("views/{$view}.blade.php")), "{$view} has inline styles.");
        }
    }

    // ── Admin: NBE rate typed in by hand ────────────────────────────────────

    public function test_a_reviewer_types_in_the_rate_and_another_reviewer_approves_it(): void
    {
        $this->enable('nbe_fx');
        $fx = MarketSeries::where('symbol', 'ETB_USD')->firstOrFail();
        $alice = $this->makeUser('admin');
        $bob = $this->makeUser('admin');

        $this->actingAsStaff($alice)->get(route('admin.market-data.index'))->assertOk()->assertSee('Enter a published price');
        $this->actingAsStaff($alice)->post(route('admin.market-data.enter'), [
            'series_id' => $fx->id, 'value' => '155.4321', 'date' => '2026-11-16', 'source_url' => 'https://nbe.gov.et/rates',
        ])->assertRedirect()->assertSessionHas('success');

        $price = $fx->prices()->firstOrFail();
        $this->assertSame('needs_review', $price->status);
        $this->actingAsStaff($alice)->get(route('admin.market-data.index'))->assertSee('Typed in by ' . $alice->name)->assertSee('https://nbe.gov.et/rates', false);

        $this->actingAsStaff($alice)->post(route('admin.market-data.approve', $price), ['reason' => 'I checked it myself'])->assertForbidden();
        $this->actingAsStaff($bob)->post(route('admin.market-data.approve', $price), ['reason' => 'Matches the NBE page'])->assertRedirect();

        $this->assertSame('published', $price->fresh()->status);
        $this->assertStringContainsString('155.4321', $this->rowHtml($this->get('/market-prices')->getContent(), 'ETB_USD'));
    }

    public function test_the_entry_form_checks_its_input_and_is_staff_only(): void
    {
        $fx = MarketSeries::where('symbol', 'ETB_USD')->firstOrFail();
        $admin = $this->makeUser('admin');

        $this->actingAs($this->makeUser())->post(route('admin.market-data.enter'), [
            'series_id' => $fx->id, 'value' => '155', 'date' => '2026-11-16', 'source_url' => 'https://nbe.gov.et/',
        ])->assertForbidden();
        $this->actingAsStaff($admin)->post(route('admin.market-data.enter'), [
            'series_id' => $fx->id, 'value' => '155', 'date' => '2026-11-16', 'source_url' => 'http://nbe.gov.et/',
        ])->assertSessionHasErrors('source_url');
        $this->actingAsStaff($admin)->post(route('admin.market-data.enter'), [
            'series_id' => MarketSeries::where('symbol', 'ICO_COMPOSITE')->value('id'), 'value' => '249', 'date' => '2026-11-16', 'source_url' => 'https://ico.org/',
        ])->assertSessionHasErrors('symbol');

        $this->assertSame(0, MarketPrice::count());
    }
}
