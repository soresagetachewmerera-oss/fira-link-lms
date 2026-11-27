<?php

namespace Tests\Feature\Market;

use App\Models\MarketPrice;
use App\Models\MarketPriceAlert;
use App\Models\MarketSeries;
use App\Models\MarketSource;
use App\Models\User;
use App\Notifications\Catalog\NotificationType;
use App\Notifications\FiraNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Market watch slice 5: watchlist, price alerts, CSV export (signed-in only), web actions. */
class MarketAccountTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        CarbonImmutable::setTestNow('2026-11-16 12:00:00');
        \Illuminate\Support\Carbon::setTestNow('2026-11-16 12:00:00');
        MarketSource::where('slug', 'ico_daily')->firstOrFail()->forceFill(['enabled' => true])->save();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        \Illuminate\Support\Carbon::setTestNow();
        parent::tearDown();
    }

    private function publish(float $value, string $time, string $symbol = 'ICO_COMPOSITE'): MarketPrice
    {
        $s = MarketSeries::where('symbol', $symbol)->firstOrFail();

        return MarketPrice::create(['series_id' => $s->id, 'value' => $value, 'currency' => $s->currency, 'unit' => $s->unit, 'source_time' => $time, 'fetched_at' => now(), 'status' => 'published']);
    }

    public function test_watchlist_add_list_and_remove(): void
    {
        Sanctum::actingAs($user = User::factory()->create());

        $this->putJson('/api/market/watchlist/ICO_COMPOSITE')->assertOk()->assertJsonPath('data.keys', ['ICO_COMPOSITE']);
        $this->putJson('/api/market/watchlist/ICO_COMPOSITE')->assertOk()->assertJsonPath('data.keys', ['ICO_COMPOSITE']);
        $this->putJson('/api/market/watchlist/KC_ACTIVE')->assertOk()->assertJsonPath('data.keys', ['ICO_COMPOSITE', 'KC_ACTIVE']);
        $this->deleteJson('/api/market/watchlist/ICO_COMPOSITE')->assertOk()->assertJsonPath('data.keys', ['KC_ACTIVE']);
        $this->putJson('/api/market/watchlist/NOPE')->assertNotFound();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/market/watchlist')->assertOk()->assertJsonPath('data.keys', []);
    }

    public function test_guests_cannot_use_signed_in_features(): void
    {
        $this->getJson('/api/market/watchlist')->assertUnauthorized();
        $this->postJson('/api/market/alerts', [])->assertUnauthorized();
        $this->getJson('/api/market/instruments/ICO_COMPOSITE/export.csv')->assertUnauthorized();
        $this->get('/market-prices/ICO_COMPOSITE/export.csv')->assertRedirect(route('login'));
        $this->post('/market-prices/ICO_COMPOSITE/watchlist')->assertRedirect(route('login'));
    }

    public function test_alert_is_checked_and_owned(): void
    {
        $owner = User::factory()->create();
        Sanctum::actingAs($owner);

        $this->postJson('/api/market/alerts', ['key' => 'ICO_COMPOSITE', 'direction' => 'sideways', 'threshold' => 250, 'channels' => ['push']])->assertJsonValidationErrors('direction');
        $this->postJson('/api/market/alerts', ['key' => 'ICO_COMPOSITE', 'direction' => 'above', 'threshold' => -1, 'channels' => ['push']])->assertJsonValidationErrors('threshold');
        $this->postJson('/api/market/alerts', ['key' => 'ICO_COMPOSITE', 'direction' => 'above', 'threshold' => 250, 'channels' => ['sms']])->assertJsonValidationErrors('channels.0');
        $id = $this->postJson('/api/market/alerts', ['key' => 'ICO_COMPOSITE', 'direction' => 'above', 'threshold' => 250, 'channels' => ['push']])
            ->assertCreated()->assertJsonPath('data.unit_label', 'US¢/lb')->json('data.id');
        $this->getJson('/api/market/alerts')->assertOk()->assertJsonCount(1, 'data.alerts');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/market/alerts')->assertJsonCount(0, 'data.alerts');
        $this->deleteJson("/api/market/alerts/{$id}")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/market/alerts/{$id}")->assertNoContent();
    }

    public function test_alert_limit_per_person(): void
    {
        $user = User::factory()->create();
        $series = MarketSeries::where('symbol', 'ICO_COMPOSITE')->firstOrFail();
        for ($i = 0; $i < MarketPriceAlert::MAX_PER_USER; $i++) {
            app(\App\Services\Market\MarketAlertService::class)->create($user, $series, 'above', 300 + $i, ['push']);
        }
        Sanctum::actingAs($user);
        $this->postJson('/api/market/alerts', ['key' => 'ICO_COMPOSITE', 'direction' => 'above', 'threshold' => 999, 'channels' => ['push']])->assertJsonValidationErrors('threshold');
    }

    public function test_alert_fires_once_on_crossing_and_rearms(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->publish(249.00, '2026-11-13 00:00:00');
        Sanctum::actingAs($user);
        $this->postJson('/api/market/alerts', ['key' => 'ICO_COMPOSITE', 'direction' => 'above', 'threshold' => 250, 'channels' => ['push', 'mail']])->assertCreated();

        $this->publish(251.20, '2026-11-14 00:00:00');
        $this->publish(252.00, '2026-11-15 00:00:00'); // still above: no second alert
        Notification::assertSentToTimes($user, FiraNotification::class, 1);
        Notification::assertSentTo($user, FiraNotification::class, function (FiraNotification $n, array $channels) {
            return $n->type === NotificationType::MarketPriceAlert && in_array(\App\Notifications\Catalog\Channel::driver('mail'), $channels, true);
        });

        $this->publish(248.00, '2026-11-15 06:00:00'); // back below: re-armed
        $this->publish(253.00, '2026-11-16 00:00:00');
        Notification::assertSentToTimes($user, FiraNotification::class, 2);
        $this->assertNotNull(MarketPriceAlert::first()->triggered_at);
    }

    public function test_an_alert_never_fires_for_the_price_it_was_made_at_or_for_an_old_price(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->publish(260.00, '2026-11-16 00:00:00');
        Sanctum::actingAs($user);
        $this->postJson('/api/market/alerts', ['key' => 'ICO_COMPOSITE', 'direction' => 'above', 'threshold' => 250, 'channels' => ['push']])->assertCreated();
        $this->postJson('/api/market/alerts', ['key' => 'ICO_COMPOSITE', 'direction' => 'below', 'threshold' => 100, 'channels' => ['push']])->assertCreated();

        $this->publish(50.00, '2026-10-01 00:00:00'); // old (backfilled) value: never triggers
        Notification::assertNothingSent();
    }

    public function test_csv_export_has_only_published_closes_and_the_disclaimer(): void
    {
        $this->publish(247.25, '2026-11-15 00:00:00');
        $this->publish(249.09, '2026-11-16 00:00:00');
        $held = $this->publish(999.00, '2026-11-14 00:00:00');
        $held->forceFill(['status' => 'needs_review'])->save();
        Sanctum::actingAs(User::factory()->create());

        $response = $this->get('/api/market/instruments/ICO_COMPOSITE/export.csv?range=1M')->assertOk();
        $csv = $response->streamedContent();

        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('ico_composite-1m-20261116.csv', $response->headers->get('Content-Disposition'));
        $lines = array_values(array_filter(explode("\n", trim($csv))));
        $this->assertSame('date,value,currency,unit,source,note', $lines[0]);
        $this->assertSame('2026-11-15,247.25,USX,lb,"International Coffee Organization (ICO)",', $lines[1]);
        $this->assertSame('2026-11-16,249.09,USX,lb,"International Coffee Organization (ICO)",', $lines[2]);
        $this->assertStringNotContainsString('999', $csv);
        $this->assertStringContainsString('Market information only, not trading advice.', end($lines));
    }

    public function test_web_panel_actions_for_signed_in_people_and_guests(): void
    {
        $this->publish(249.09, '2026-11-16 00:00:00');

        $guest = $this->get('/market-prices?market=ICO_COMPOSITE')->assertOk()->getContent();
        $this->assertStringContainsString(route('market-prices.sign-in', ['market' => 'ICO_COMPOSITE']), $guest);
        $this->assertStringContainsString('data-fira-ai-prompt="What does the latest ICO composite price on Fira Link show?"', $guest);
        $this->assertStringNotContainsString('name="threshold"', $guest);
        $this->get(route('market-prices.sign-in', ['market' => 'ICO_COMPOSITE']))->assertRedirect(route('login'));

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('market-prices.sign-in', ['market' => 'ICO_COMPOSITE']))->assertRedirect(route('market-prices.index', ['market' => 'ICO_COMPOSITE']));
        $this->actingAs($user)->post('/market-prices/ICO_COMPOSITE/watchlist')->assertRedirect(route('market-prices.index', ['market' => 'ICO_COMPOSITE']));
        $this->actingAs($user)->post('/market-prices/ICO_COMPOSITE/alerts', ['direction' => 'below', 'threshold' => 240, 'channels' => ['mail']])->assertSessionHas('success');

        $page = $this->actingAs($user)->get('/market-prices?market=ICO_COMPOSITE')->assertOk()->getContent();
        $this->assertStringContainsString('aria-pressed="true"', $page);
        $this->assertStringContainsString('Remove from watchlist', $page);
        $this->assertStringContainsString('When below 240.00 US¢/lb', $page);
        $this->assertStringContainsString(route('market-prices.export', ['ICO_COMPOSITE', 'range' => '1M']), $page);

        $alert = MarketPriceAlert::firstOrFail();
        $this->actingAs(User::factory()->create())->delete("/market-prices/alerts/{$alert->id}")->assertForbidden();
        $this->actingAs($user)->delete("/market-prices/alerts/{$alert->id}")->assertSessionHas('success');
        $this->assertSame(0, MarketPriceAlert::count());
    }
}
