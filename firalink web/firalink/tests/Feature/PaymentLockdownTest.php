<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\EventBooking;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\PaymentGatewaySetting;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Services\Billing\Providers\ChapaPaymentAdapter;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\Concerns\BuildsTradeFixtures;
use Tests\TestCase;

/**
 * Fix slice A (gap analysis 2026-11-19): forged billing webhooks, the Chapa server switch, simulated checkouts,
 * and the removed legacy gateway routes.
 */
class PaymentLockdownTest extends TestCase
{
    use DatabaseTransactions, BuildsTradeFixtures;

    private const WEBHOOK_SECRET = 'whsec-unit-test';

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        PaymentGatewaySetting::updateOrCreate(['identifier' => 'chapa'], [
            'name' => 'Chapa', 'type' => 'online', 'is_enabled' => true,
            'credentials' => ['secret_key' => 'CHASECK_TEST-unit', 'public_key' => 'CHAPUBK_TEST-unit', 'webhook_secret' => self::WEBHOOK_SECRET, 'mode' => 'live'],
        ]);
        Cache::forget('payment_gateway_settings');
    }

    private function invoice(float $amount = 3500, string $currency = 'ETB'): Invoice
    {
        $org = Organization::create(['name' => 'Pay Org ' . Str::random(6), 'slug' => 'pay-org-' . Str::lower(Str::random(8)), 'status' => 'active']);
        $plan = Plan::where('key', 'growth')->firstOrFail();

        return Invoice::create([
            'organization_id' => $org->id, 'invoice_number' => 'INV-' . Str::upper(Str::random(8)), 'amount' => $amount,
            'currency' => $currency, 'exchange_rate' => 1, 'status' => Invoice::STATUS_PENDING, 'provider' => 'chapa',
            'provider_ref' => 'SUB-' . $org->id . '-' . $plan->id . '-' . time(), 'issued_at' => now(), 'due_at' => now()->addDays(2),
        ]);
    }

    private function postWebhook(array $payload, ?string $signature = 'valid', string $provider = 'chapa')
    {
        $headers = [];
        if ($signature === 'valid') {
            $headers['x-chapa-signature'] = hash_hmac('sha256', json_encode($payload), self::WEBHOOK_SECRET);
        } elseif ($signature !== null) {
            $headers['x-chapa-signature'] = $signature;
        }

        return $this->withHeaders($headers)->postJson("/api/billing/webhook/{$provider}", $payload);
    }

    private function fakeChapaVerify(string $status, float $amount, string $currency = 'ETB'): void
    {
        Http::fake(['api.chapa.co/v1/transaction/verify/*' => Http::response([
            'status' => 'success', 'message' => 'Payment details',
            'data' => ['status' => $status, 'amount' => $amount, 'currency' => $currency, 'reference' => 'CH-REF-1'],
        ], 200)]);
    }

    private function assertNotActivated(Invoice $invoice): void
    {
        $this->assertNotSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertFalse(Subscription::where('organization_id', $invoice->organization_id)->where('status', Subscription::STATUS_ACTIVE)->exists());
    }

    public function test_unsigned_webhook_is_refused_and_activates_nothing(): void
    {
        $invoice = $this->invoice();
        $this->fakeChapaVerify('success', 3500);

        $this->postWebhook(['tx_ref' => $invoice->provider_ref, 'status' => 'success', 'amount' => 1], null)->assertStatus(400);

        $this->assertNotActivated($invoice);
    }

    public function test_wrongly_signed_webhook_is_refused(): void
    {
        $invoice = $this->invoice();
        $this->fakeChapaVerify('success', 3500);

        $this->postWebhook(['tx_ref' => $invoice->provider_ref, 'status' => 'success'], 'forged-signature')->assertStatus(400);

        $this->assertNotActivated($invoice);
    }

    public function test_signed_webhook_is_refused_when_chapa_does_not_confirm(): void
    {
        $invoice = $this->invoice();
        $this->fakeChapaVerify('failed', 3500);

        $this->postWebhook(['tx_ref' => $invoice->provider_ref, 'status' => 'success'])->assertStatus(400);

        $this->assertNotActivated($invoice);
    }

    public function test_signed_webhook_is_refused_when_amount_or_currency_do_not_match(): void
    {
        $short = $this->invoice(3500);
        $this->fakeChapaVerify('success', 100);
        $this->postWebhook(['tx_ref' => $short->provider_ref, 'status' => 'success'])->assertStatus(400);
        $this->assertNotActivated($short);

        $otherCurrency = $this->invoice(3500, 'ETB');
        $this->fakeChapaVerify('success', 3500, 'USD');
        $this->postWebhook(['tx_ref' => $otherCurrency->provider_ref, 'status' => 'success'])->assertStatus(400);
        $this->assertNotActivated($otherCurrency);
    }

    public function test_confirmed_webhook_activates_once_and_replays_are_ignored(): void
    {
        $invoice = $this->invoice(3500);
        $this->fakeChapaVerify('success', 3500);
        $payload = ['tx_ref' => $invoice->provider_ref, 'status' => 'success'];

        $this->postWebhook($payload)->assertOk()->assertJson(['success' => true]);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertTrue(Subscription::where('organization_id', $invoice->organization_id)->where('status', Subscription::STATUS_ACTIVE)->exists());

        $this->postWebhook($payload)->assertOk()->assertJson(['duplicate' => true]);
        $this->assertSame(1, \App\Models\SubscriptionPayment::where('invoice_id', $invoice->id)->count());
    }

    public function test_unknown_provider_is_not_treated_as_chapa(): void
    {
        $invoice = $this->invoice();
        $this->fakeChapaVerify('success', 3500);

        $this->postWebhook(['tx_ref' => $invoice->provider_ref, 'status' => 'success'], 'valid', 'stripe')->assertStatus(404);

        $this->assertNotActivated($invoice);
    }

    public function test_switched_off_chapa_refuses_webhooks_checkouts_and_hides_the_gateway(): void
    {
        config(['services.chapa.enabled' => false]);
        $invoice = $this->invoice();
        $this->fakeChapaVerify('success', 3500);

        // Webhook: 503 so Chapa retries later; nothing activated.
        $this->postWebhook(['tx_ref' => $invoice->provider_ref, 'status' => 'success'])->assertStatus(503);
        $this->assertNotActivated($invoice);

        // Gateway hidden everywhere.
        $gateways = app(PaymentGatewayService::class);
        $this->assertFalse($gateways->isEnabled('chapa'));
        $this->assertCount(0, $gateways->onlineGateways());
        $this->getJson('/api/settings')->assertOk()->assertJsonPath('data.payment_methods', []);

        // Adapter never starts a checkout.
        $result = (new ChapaPaymentAdapter())->initialize(['amount' => 10, 'tx_ref' => 'SUB-1-1-1', 'callback_url' => 'https://x.test', 'return_url' => 'https://x.test/r']);
        $this->assertFalse($result['success']);
        $this->assertSame('CHAPA_OFF', $result['code']);
        $this->assertNull($result['checkout_url']);
    }

    public function test_switched_off_chapa_refuses_seller_plan_checkout_without_an_invoice(): void
    {
        config(['services.chapa.enabled' => false]);
        $owner = $this->makeUser();
        $org = $this->makeOrganization($owner);
        $owner->forceFill(['active_organization_id' => $org->id])->save();
        $plan = Plan::where('key', 'growth')->firstOrFail();
        PlanPrice::updateOrCreate(['plan_id' => $plan->id, 'currency' => 'ETB', 'billing_period' => 'monthly'], ['amount' => 3500, 'is_active' => true, 'effective_from' => now()->subDay()]);
        Sanctum::actingAs($owner);

        $this->postJson('/api/marketplace/subscribe', ['plan_key' => 'growth', 'gateway' => 'chapa', 'period' => 'monthly', 'currency' => 'ETB'])
            ->assertStatus(503)
            ->assertJson(['success' => false, 'code' => 'CHAPA_OFF']);
        $this->assertFalse(Invoice::where('organization_id', $org->id)->exists());
    }

    public function test_switched_off_chapa_refuses_event_checkout_and_keeps_no_booking(): void
    {
        config(['services.chapa.enabled' => false]);
        $user = $this->makeUser();
        Sanctum::actingAs($user);
        $event = new Event();
        $event->forceFill([
            'title' => 'Lockdown cupping', 'slug' => 'ev-' . Str::lower(Str::random(10)), 'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(), 'start_time' => '09:00', 'location' => 'Addis', 'total_seats' => 3,
            'price' => 500, 'is_published' => true, 'service_fee' => 25,
        ])->save();

        $this->post("/api/events/{$event->id}/book", [
            'first_name' => 'Abebe', 'last_name' => 'Kebede', 'phone' => '+251911000000', 'seats' => 1, 'payment_method' => 'chapa',
        ], ['Accept' => 'application/json'])->assertStatus(503)->assertJson(['code' => 'CHAPA_OFF']);

        $this->assertFalse(EventBooking::where('event_id', $event->id)->exists());
    }

    public function test_simulated_checkout_is_refused_on_a_server_even_in_sandbox_mode(): void
    {
        PaymentGatewaySetting::where('identifier', 'chapa')->update(['credentials' => ['secret_key' => 'CHASECK_TEST-unit', 'webhook_secret' => self::WEBHOOK_SECRET, 'mode' => 'sandbox']]);
        Http::fake(['api.chapa.co/*' => Http::response(['status' => 'failed', 'message' => 'Invalid API key'], 401)]);
        $this->app['env'] = 'production';

        $result = (new ChapaPaymentAdapter())->initialize(['amount' => 10, 'tx_ref' => 'SUB-1-1-1', 'callback_url' => 'https://x.test', 'return_url' => 'https://x.test/r']);

        $this->app['env'] = 'testing';
        $this->assertFalse($result['success']);
        $this->assertNull($result['checkout_url']);
    }

    public function test_legacy_gateway_verify_routes_are_gone(): void
    {
        Sanctum::actingAs($this->makeUser());
        foreach (['razorpay', 'sslcommerz', 'stripe', 'paystack', 'mollie', 'bkash', 'xpay', 'paypal'] as $gateway) {
            $this->postJson("/api/payments/{$gateway}/verify", [])->assertStatus(404);
        }
    }
}
