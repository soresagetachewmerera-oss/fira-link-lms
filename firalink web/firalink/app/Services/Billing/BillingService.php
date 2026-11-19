<?php

namespace App\Services\Billing;

use App\Contracts\PaymentProviderInterface;
use App\Models\AuditLog;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Models\SubscriptionPayment;
use App\Models\User;
use App\Models\WebhookCall;
use App\Services\Billing\Providers\ChapaPaymentAdapter;
use App\Services\EntitlementService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BillingService
{
    protected EntitlementService $entitlementService;
    protected array $providers = [];

    public function __construct(EntitlementService $entitlementService)
    {
        $this->entitlementService = $entitlementService;
        // Register default payment adapters
        $this->providers['chapa'] = new ChapaPaymentAdapter();
    }

    public function getProvider(string $name): PaymentProviderInterface
    {
        $key = strtolower($name);
        if (!isset($this->providers[$key])) {
            // Default fallback to Chapa
            return $this->providers['chapa'];
        }
        return $this->providers[$key];
    }

    /**
     * Subscribe an organization to a plan.
     */
    public function subscribe(
        Organization $org,
        Plan $plan,
        string $billingPeriod = 'monthly',
        string $currency = 'ETB',
        string $providerName = 'chapa',
        ?User $actor = null
    ): array {
        $currency = strtoupper($currency);
        $billingPeriod = strtolower($billingPeriod);

        // Fetch price
        $priceObj = $plan->getPrice($currency, $billingPeriod);
        $amount = $priceObj ? (float) $priceObj->amount : 0.00;

        // 1. Free plan activation (e.g. Starter)
        if ($amount <= 0 || $plan->key === Plan::KEY_STARTER) {
            return DB::transaction(function () use ($org, $plan, $billingPeriod, $actor) {
                $sub = $org->activeSubscription ?? new Subscription(['organization_id' => $org->id]);
                $fromPlanId = $sub->plan_id;

                $sub->plan_id = $plan->id;
                $sub->status = Subscription::STATUS_ACTIVE;
                $sub->billing_period = $billingPeriod;
                $sub->current_period_start = now();
                $sub->current_period_end = now()->addYears(5); // effectively perpetual until changed
                $sub->grace_until = null;
                $sub->cancel_at_period_end = false;
                $sub->provider = 'free';
                $sub->save();

                SubscriptionEvent::create([
                    'subscription_id' => $sub->id,
                    'organization_id' => $org->id,
                    'event_type' => $fromPlanId ? SubscriptionEvent::EVENT_DOWNGRADED : SubscriptionEvent::EVENT_CREATED,
                    'from_plan_id' => $fromPlanId,
                    'to_plan_id' => $plan->id,
                    'metadata' => ['billing_period' => $billingPeriod, 'free' => true],
                    'created_by' => $actor?->id,
                    'created_at' => now(),
                ]);

                AuditLog::log('plan_changed', $org, ['from_plan_id' => $fromPlanId], ['to_plan_id' => $plan->id]);

                $this->entitlementService->invalidate($org);

                return [
                    'success' => true,
                    'status' => 'activated',
                    'subscription' => $sub,
                    'checkout_url' => null,
                    'message' => "{$plan->name} plan activated successfully.",
                ];
            });
        }

        // 2. Paid Plan: only a registered provider (an unknown name such as "offline" is not sent to Chapa).
        if (!isset($this->providers[strtolower($providerName)])) {
            return [
                'success' => false,
                'code' => 'PAYMENT_METHOD_UNAVAILABLE',
                'status' => 'unavailable',
                'checkout_url' => null,
                'message' => __('bookings.errors.payment_method'),
            ];
        }
        if (strtolower($providerName) === 'chapa' && !\App\Support\ChapaSwitch::enabled()) {
            return [
                'success' => false,
                'code' => 'CHAPA_OFF',
                'status' => 'unavailable',
                'checkout_url' => null,
                'message' => \App\Support\ChapaSwitch::offMessage(),
            ];
        }

        $invoiceNumber = 'INV-' . strtoupper(Str::random(4)) . '-' . date('YmdHis');
        $txRef = 'SUB-' . $org->id . '-' . $plan->id . '-' . time();

        $invoice = Invoice::create([
            'organization_id' => $org->id,
            'subscription_id' => $org->activeSubscription?->id,
            'invoice_number' => $invoiceNumber,
            'amount' => $amount,
            'currency' => $currency,
            'exchange_rate' => $currency === 'USD' ? 120.00 : 1.00, // Configurable benchmark rate (UNVERIFIED)
            'status' => Invoice::STATUS_PENDING,
            'provider' => $providerName,
            'provider_ref' => $txRef,
            'issued_at' => now(),
            'due_at' => now()->addDays(2),
        ]);

        $provider = $this->getProvider($providerName);
        $user = $actor ?? $org->owner() ?? auth()->user();

        $checkout = $provider->initialize([
            'amount' => $amount,
            'currency' => $currency,
            'email' => $org->billing_email ?? $user?->email ?? 'billing@firalink.et',
            'name' => $org->name,
            'phone' => $user?->phone,
            'tx_ref' => $txRef,
            'callback_url' => route('billing.webhook', ['provider' => $provider->getName()]),
            'return_url' => route('marketplace.dashboard', ['subscribed' => $plan->key, 'tx_ref' => $txRef]),
            'title' => "Fira Link B2B: {$plan->name} ({$billingPeriod})",
            'description' => "Ethiopian Coffee Export Platform - {$plan->name} Membership",
        ]);

        return [
            'success' => $checkout['success'],
            'status' => 'checkout_required',
            'invoice_id' => $invoice->id,
            'checkout_url' => $checkout['checkout_url'],
            'provider_ref' => $txRef,
            'message' => $checkout['message'] ?? 'Proceed to secure checkout.',
        ];
    }

    /**
     * Process an incoming payment webhook idempotently and securely.
     */
    public function handleWebhook(string $providerName, Request $request): array
    {
        // Only registered providers: an unknown name is not silently treated as Chapa.
        if (!isset($this->providers[strtolower($providerName)])) {
            return ['success' => false, 'http_status' => 404, 'message' => 'Unknown payment provider.'];
        }
        if (strtolower($providerName) === 'chapa' && !\App\Support\ChapaSwitch::enabled()) {
            // 503 so Chapa retries the delivery once payments are switched back on.
            return ['success' => false, 'http_status' => 503, 'message' => 'Chapa payments are switched off.'];
        }

        $provider = $this->getProvider($providerName);
        $verification = $provider->verifyWebhook($request);

        $providerRef = $verification['provider_ref'] ?? $request->input('tx_ref');
        $idempotencyKey = $providerName . ':' . ($providerRef ?? Str::random(16));

        // 1. Idempotency Check
        $existingCall = WebhookCall::where('idempotency_key', $idempotencyKey)->first();
        if ($existingCall && $existingCall->status === 'processed') {
            Log::info("Webhook {$idempotencyKey} already processed. Skipping idempotently.");
            return ['success' => true, 'duplicate' => true, 'idempotent' => true, 'message' => 'Duplicate webhook ignored'];
        }

        // Record incoming call
        $call = WebhookCall::create([
            'provider' => $providerName,
            'provider_ref' => $providerRef,
            'idempotency_key' => $idempotencyKey,
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'status' => 'received',
        ]);

        if (!$verification['valid']) {
            $call->update(['status' => 'failed', 'error_message' => 'Invalid webhook signature']);
            return ['success' => false, 'message' => 'Signature verification failed.'];
        }

        // 2. Locate Invoice
        $invoice = Invoice::where('provider_ref', $providerRef)->first();
        if (!$invoice) {
            $call->update(['status' => 'ignored', 'error_message' => 'Invoice not found for reference']);
            return ['success' => false, 'message' => 'Invoice reference not found.'];
        }

        if ($invoice->status === Invoice::STATUS_PAID) {
            $call->update(['status' => 'processed', 'error_message' => 'Invoice already paid']);

            return ['success' => true, 'duplicate' => true, 'idempotent' => true, 'message' => 'Invoice already paid.'];
        }

        if ($verification['status'] === 'paid') {
            // Never trust the webhook body alone: ask the provider, server to server, and match the invoice.
            $check = $provider->verify((string) $providerRef);
            $amountOk = isset($check['amount']) && (float) $check['amount'] + 0.005 >= (float) $invoice->amount;
            $currencyOk = strtoupper((string) ($check['currency'] ?? '')) === strtoupper((string) $invoice->currency);
            if (!($check['success'] ?? false) || ($check['status'] ?? null) !== 'paid' || !$amountOk || !$currencyOk) {
                $call->update(['status' => 'failed', 'error_message' => 'Provider did not confirm the payment for this invoice']);
                Log::warning('Billing webhook refused: provider verify did not match the invoice', [
                    'invoice_id' => $invoice->id, 'status' => $check['status'] ?? null,
                    'amount_ok' => $amountOk, 'currency_ok' => $currencyOk,
                ]);

                return ['success' => false, 'message' => 'Payment could not be confirmed with the provider.'];
            }
            $verification['raw'] = $check['raw'] ?? $verification['raw'] ?? [];

            DB::transaction(function () use ($invoice, $providerName, $providerRef, $idempotencyKey, $verification, $call) {
                // Mark invoice paid
                $invoice->update([
                    'status' => Invoice::STATUS_PAID,
                    'paid_at' => now(),
                ]);

                // Create payment record
                SubscriptionPayment::firstOrCreate(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'invoice_id' => $invoice->id,
                        'organization_id' => $invoice->organization_id,
                        'amount' => $invoice->amount,
                        'currency' => $invoice->currency,
                        'status' => 'successful',
                        'provider' => $providerName,
                        'provider_ref' => $providerRef,
                        'raw_payload' => $verification['raw'] ?? [],
                        'paid_at' => now(),
                    ]
                );

                // Activate / Renew Subscription
                $org = $invoice->organization;
                // Parse target plan from provider_ref or subscription
                $targetPlan = null;
                $parts = explode('-', $providerRef);
                // SUB-{org}-{plan}-{time}: use the plan only when the reference belongs to this invoice's business.
                if (count($parts) >= 3 && is_numeric($parts[2]) && (int) $parts[1] === (int) $invoice->organization_id) {
                    $targetPlan = Plan::find($parts[2]);
                }

                if (!$targetPlan) {
                    $targetPlan = Plan::where('key', Plan::KEY_GROWTH)->first();
                }

                $sub = $invoice->subscription ?? $org->activeSubscription ?? new Subscription(['organization_id' => $org->id]);
                $fromPlanId = $sub->plan_id;
                $periodDays = str_contains($providerRef, 'annual') ? 365 : 30;

                $sub->plan_id = $targetPlan->id;
                $sub->status = Subscription::STATUS_ACTIVE;
                $sub->current_period_start = now();
                $sub->current_period_end = now()->addDays($periodDays);
                $sub->grace_until = null;
                $sub->provider = $providerName;
                $sub->provider_ref = $providerRef;
                $sub->save();

                $invoice->update(['subscription_id' => $sub->id]);

                // Record event
                SubscriptionEvent::create([
                    'subscription_id' => $sub->id,
                    'organization_id' => $org->id,
                    'event_type' => $fromPlanId ? SubscriptionEvent::EVENT_UPGRADED : SubscriptionEvent::EVENT_RENEWED,
                    'from_plan_id' => $fromPlanId,
                    'to_plan_id' => $targetPlan->id,
                    'metadata' => ['invoice_id' => $invoice->id, 'tx_ref' => $providerRef],
                    'created_at' => now(),
                ]);

                AuditLog::log('subscription_paid', $sub, [], ['invoice_id' => $invoice->id, 'plan_id' => $targetPlan->id], $org->id);

                $this->entitlementService->invalidate($org);

                $call->update(['status' => 'processed']);
            });

            return ['success' => true, 'message' => 'Subscription activated.'];
        }

        $invoice->update(['status' => Invoice::STATUS_FAILED]);
        $call->update(['status' => 'processed', 'error_message' => 'Payment status failed from provider']);

        return ['success' => false, 'message' => 'Payment status is not paid.'];
    }

    /**
     * Compute proration credit/charge when changing plans.
     */
    public function calculateProration(Subscription $sub, Plan $newPlan, string $newCurrency = 'ETB'): array
    {
        if (!$sub->current_period_end || $sub->current_period_end->isPast()) {
            $newPrice = $newPlan->getPrice($newCurrency, $sub->billing_period)?->amount ?? 0.00;
            return [
                'has_proration' => false,
                'charge_amount' => (float) $newPrice,
                'credit_amount' => 0.00,
                'net_amount' => (float) $newPrice,
            ];
        }

        $now = now();
        $totalDays = max(1, $sub->current_period_start->diffInDays($sub->current_period_end));
        $remainingDays = max(0, $now->diffInDays($sub->current_period_end));
        $remainingRatio = $remainingDays / $totalDays;

        $currentPrice = $sub->plan->getPrice($newCurrency, $sub->billing_period)?->amount ?? 0.00;
        $newPrice = $newPlan->getPrice($newCurrency, $sub->billing_period)?->amount ?? 0.00;

        $unusedCredit = round((float) $currentPrice * $remainingRatio, 2);
        $newCost = round((float) $newPrice * $remainingRatio, 2);
        $netAmount = max(0, round($newCost - $unusedCredit, 2));

        return [
            'has_proration' => true,
            'remaining_days' => $remainingDays,
            'unused_credit' => $unusedCredit,
            'new_charge' => $newCost,
            'net_amount' => $netAmount,
            'currency' => $newCurrency,
        ];
    }
}
