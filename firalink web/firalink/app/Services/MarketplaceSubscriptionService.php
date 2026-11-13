<?php

namespace App\Services;

use App\Models\MarketplaceSubscriptionPlan;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserMarketplaceSubscription;
use Exception;
use Illuminate\Support\Facades\Log;

class MarketplaceSubscriptionService
{
    protected PaymentGatewayService $paymentGatewayService;
    protected EntitlementService $entitlementService;

    public function __construct(
        PaymentGatewayService $paymentGatewayService,
        EntitlementService $entitlementService
    ) {
        $this->paymentGatewayService = $paymentGatewayService;
        $this->entitlementService = $entitlementService;
    }

    /**
     * Check if a user can create another active coffee listing.
     */
    public function canPostListing(User $user): array
    {
        if ($user->isAdmin()) {
            return ['allowed' => true, 'limit' => -1, 'active_count' => $user->coffeeListings()->where('status', 'active')->count()];
        }

        $org = $user->primaryOrganization();
        if ($org) {
            $allowed = $this->entitlementService->can($org, 'active_lots');
            $limit = $this->entitlementService->limit($org, 'active_lots');
            $activeCount = $this->entitlementService->used($org, 'active_lots');
            $resp = $this->entitlementService->limitReachedResponse($org, 'active_lots');

            return [
                'allowed' => $allowed,
                'limit' => $limit,
                'active_count' => $activeCount,
                'message' => $allowed ? null : $resp['message'],
            ];
        }

        $activeSub = $user->activeMarketplaceSubscription;
        $activeCount = $user->coffeeListings()->where('status', 'active')->count();

        if (!$activeSub || !$activeSub->plan) {
            $allowed = $activeCount < 3;
            return [
                'allowed' => $allowed,
                'limit' => 3,
                'active_count' => $activeCount,
                'message' => $allowed ? null : 'Starter tier active lot limit (3 lots) reached. Please upgrade to Growth.',
            ];
        }

        $limit = $activeSub->plan->active_listings_limit;
        if ($limit === 0) {
            return ['allowed' => true, 'limit' => -1, 'active_count' => $activeCount];
        }

        $allowed = $activeCount < $limit;
        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'active_count' => $activeCount,
            'message' => $allowed ? null : "Your {$activeSub->plan->name} allows a maximum of {$limit} active listings. Please upgrade to list more lots.",
        ];
    }

    /**
     * Check if a seller can submit a quotation to an RFQ.
     */
    public function canSubmitQuote(User $user): array
    {
        if ($user->isAdmin()) {
            return ['allowed' => true, 'limit' => -1, 'used' => 0];
        }

        $org = $user->primaryOrganization();
        if ($org) {
            $allowed = $this->entitlementService->can($org, 'rfq_responses_per_month');
            $limit = $this->entitlementService->limit($org, 'rfq_responses_per_month');
            $used = $this->entitlementService->used($org, 'rfq_responses_per_month');
            $resp = $this->entitlementService->limitReachedResponse($org, 'rfq_responses_per_month');

            return [
                'allowed' => $allowed,
                'limit' => $limit,
                'used' => $used,
                'message' => $allowed ? null : $resp['message'],
            ];
        }

        $activeSub = $user->activeMarketplaceSubscription;
        $thisMonthQuotes = $user->marketplaceQuotations()->where('created_at', '>=', now()->startOfMonth())->count();

        if (!$activeSub || !$activeSub->plan) {
            $allowed = $thisMonthQuotes < 5;
            return [
                'allowed' => $allowed,
                'limit' => 5,
                'used' => $thisMonthQuotes,
                'message' => $allowed ? null : 'Starter tier monthly RFQ quote limit (5/month) reached. Upgrade to Growth for 30 quotes/month.',
            ];
        }

        $limit = $activeSub->plan->rfq_response_limit;
        if ($limit === 0) {
            return ['allowed' => true, 'limit' => -1, 'used' => $thisMonthQuotes];
        }

        $allowed = $thisMonthQuotes < $limit;
        return [
            'allowed' => $allowed,
            'limit' => $limit,
            'used' => $thisMonthQuotes,
            'message' => $allowed ? null : "Monthly RFQ response limit reached ({$limit}/month). Upgrade for more quotes.",
        ];
    }

    /**
     * Initialize subscription checkout (Chapa online or offline bank wire).
     */
    public function initializeSubscription(User $user, MarketplaceSubscriptionPlan $plan, string $gateway, array $data = []): array
    {
        $amount = (float) $plan->price_etb;

        // Free plan instant activation
        if ($amount <= 0) {
            $sub = UserMarketplaceSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'expires_at' => now()->addYear(),
                'auto_renew' => false,
                'notes' => 'Free tier activated.',
            ]);

            return [
                'success' => true,
                'status' => 'activated',
                'subscription' => $sub,
                'message' => 'Subscription activated successfully.',
            ];
        }

        if ($gateway === 'chapa') {
            $txRef = 'MKT-SUB-' . $user->id . '-' . time();

            // Create initial payment record
            $payment = Payment::create([
                'user_id' => $user->id,
                'payment_method' => 'chapa',
                'amount' => $amount,
                'status' => 'pending',
                'chapa_tx_ref' => $txRef,
            ]);

            // Create pending subscription
            $subscription = UserMarketplaceSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'status' => 'pending',
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
                'auto_renew' => true,
            ]);

            $payment->update(['marketplace_subscription_id' => $subscription->id]);

            // Initialize Chapa checkout
            $checkoutResult = $this->paymentGatewayService->initializeChapa([
                'amount' => $amount,
                'currency' => 'ETB',
                'email' => $user->email,
                'first_name' => explode(' ', $user->name)[0] ?? 'User',
                'last_name' => explode(' ', $user->name)[1] ?? 'Customer',
                'tx_ref' => $txRef,
                'callback_url' => route('api.marketplace.chapa.callback', ['tx_ref' => $txRef]),
                'return_url' => url('/marketplace/subscriptions/success'),
                'customization' => [
                    'title' => "Fira Link Coffee: {$plan->name}",
                    'description' => "Monthly subscription for {$plan->name}",
                ],
            ]);

            if (!$checkoutResult['success']) {
                $payment->update(['status' => 'failed']);
                $subscription->update(['status' => 'cancelled']);
                throw new Exception($checkoutResult['message'] ?? 'Unable to connect to Chapa payment gateway.');
            }

            return [
                'success' => true,
                'status' => 'checkout_required',
                'payment_url' => $checkoutResult['data']['checkout_url'] ?? null,
                'tx_ref' => $txRef,
                'payment_id' => $payment->id,
                'subscription_id' => $subscription->id,
            ];
        }

        if ($gateway === 'offline') {
            $receiptPath = $data['receipt_file'] ?? null;
            $transactionId = $data['transaction_id'] ?? null;

            $payment = Payment::create([
                'user_id' => $user->id,
                'payment_method' => 'offline',
                'amount' => $amount,
                'status' => 'pending',
                'receipt_file' => $receiptPath,
                'transaction_id' => $transactionId,
                'notes' => $data['notes'] ?? 'Manual bank transfer for marketplace subscription.',
            ]);

            $subscription = UserMarketplaceSubscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'payment_id' => $payment->id,
                'status' => 'pending',
                'starts_at' => now(),
                'expires_at' => now()->addMonth(),
                'auto_renew' => false,
                'notes' => 'Awaiting admin verification of bank slip.',
            ]);

            $payment->update(['marketplace_subscription_id' => $subscription->id]);

            return [
                'success' => true,
                'status' => 'pending_verification',
                'subscription_id' => $subscription->id,
                'message' => 'Deposit receipt received. Our administration team will verify your payment within 2-4 hours.',
            ];
        }

        throw new Exception("Unsupported payment gateway: {$gateway}");
    }

    /**
     * Activate subscription after payment confirmation.
     */
    public function activateSubscription(UserMarketplaceSubscription $subscription): void
    {
        $durationDays = 30;
        if ($subscription->plan && $subscription->plan->billing_cycle === 'yearly') {
            $durationDays = 365;
        }

        $subscription->update([
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addDays($durationDays),
        ]);

        // HARD CONSTRAINT #4: TRUST IS NEVER FOR SALE.
        // No plan, price, or entitlement may influence verification tier or outcome.
        // Verification is strictly an independent institutional audit process.

        Log::info("Activated marketplace subscription #{$subscription->id} for user #{$subscription->user_id}");
    }
}

