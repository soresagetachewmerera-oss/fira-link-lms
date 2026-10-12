<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\FileUploadService;
use App\Services\PaymentGatewayService;
use App\Services\SubscriptionPlanService;
use App\Services\SubscriptionService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeSession;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Stripe;
use Yabacon\Paystack;

/**
 * Subscription API Controller
 *
 * Serves API endpoints for mobile applications with full payment gateway & subscription support.
 *
 * @package App\Http\Controllers\Api
 */
class SubscriptionController extends Controller
{
    public function __construct(
        private SubscriptionPlanService $planService,
        private SubscriptionService $subscriptionService,
        private PaymentGatewayService $paymentGatewayService,
        private FileUploadService $fileUploadService
    ) {
    }

    /**
     * Get all active subscription plans with full details.
     */
    public function plans(): JsonResponse
    {
        $plans = $this->planService->getActivePlans()->map(function ($plan) {
            return [
                'id'                     => $plan->id,
                'name'                   => $plan->name,
                'slug'                   => $plan->slug,
                'description'            => $plan->description,
                'price'                  => (float) $plan->price,
                'discount_price'         => $plan->discount_price !== null ? (float) $plan->discount_price : null,
                'effective_price'        => (float) $plan->effective_price,
                'formatted_price'        => currency_format($plan->effective_price),
                'currency_code'          => currency_code(),
                'currency_symbol'        => currency_symbol(),
                'billing_period'         => $plan->billing_period,
                'duration_days'          => $plan->duration_days,
                'course_limit'           => $plan->course_limit,
                'features'               => $plan->features ?? [],
                'is_featured'            => (bool) $plan->is_featured,
                'included_courses_count' => (int) $plan->courses_count,
                'included_bundles_count' => (int) $plan->bundles_count,
                'courses'                => $plan->courses->map(fn($c) => [
                    'id'             => $c->id,
                    'title'          => $c->title,
                    'thumbnail_url'  => $c->featured_image ? asset('storage/' . $c->featured_image) : null,
                ]),
                'bundles'                => $plan->bundles->map(fn($b) => [
                    'id'             => $b->id,
                    'title'          => $b->title,
                    'thumbnail_url'  => $b->banner ? asset('storage/' . $b->banner) : null,
                ]),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $plans,
        ]);
    }

    /**
     * Get authenticated student's active subscription details.
     */
    public function userSubscription(): JsonResponse
    {
        $user = Auth::user();
        /** @var User $user */

        $activeSub = $user->activeSubscription();

        if (!$activeSub) {
            return response()->json([
                'success'                 => true,
                'has_active_subscription' => false,
                'data'                    => null,
            ]);
        }

        $plan = $activeSub->plan;

        return response()->json([
            'success'                 => true,
            'has_active_subscription' => true,
            'data'                    => [
                'id'               => $activeSub->id,
                'plan_id'          => $plan?->id,
                'plan_name'        => $plan?->name ?? 'Subscription',
                'billing_period'   => $plan?->billing_period,
                'starts_at'        => $activeSub->starts_at?->toIso8601String(),
                'ends_at'          => $activeSub->ends_at?->toIso8601String(),
                'remaining_days'   => $activeSub->remaining_days,
                'is_lifetime'      => (bool) ($plan?->is_lifetime ?? false),
                'status'           => $activeSub->status,
                'included_courses' => $plan ? $plan->courses->map(fn($c) => [
                    'id'            => $c->id,
                    'title'         => $c->title,
                    'thumbnail_url' => $c->featured_image ? asset('storage/' . $c->featured_image) : null,
                    'is_enrolled'   => $user->enrollments()->where('course_id', $c->id)->whereIn('status', ['approved', 'completed'])->exists(),
                ]) : [],
                'included_bundles' => $plan ? $plan->bundles->map(fn($b) => [
                    'id'            => $b->id,
                    'title'         => $b->title,
                    'thumbnail_url' => $b->banner ? asset('storage/' . $b->banner) : null,
                ]) : [],
            ],
        ]);
    }

    /**
     * Mobile subscription purchase API – initializes checkout for all payment gateways.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan_id'        => 'required|exists:subscription_plans,id',
            'payment_method' => 'required|string|in:chapa,free',
        ]);

        $user = Auth::user();
        /** @var User $user */

        $plan = SubscriptionPlan::active()->findOrFail($request->plan_id);
        $paymentMethod = strtolower($request->payment_method);
        $amount = (float) $plan->effective_price;

        DB::beginTransaction();

        try {
            // Free plan
            if ($amount <= 0) {
                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'free',
                    'amount'               => 0,
                    'status'               => Payment::STATUS_COMPLETED,
                    'transaction_id'       => 'SUB-FREE-' . strtoupper(uniqid()),
                ]);

                $subscription = $this->subscriptionService->activateSubscription(
                    $user, $plan, $payment, UserSubscription::STATUS_ACTIVE
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Free subscription activated successfully!',
                    'data'    => [
                        'subscription_id' => $subscription->id,
                        'plan_name'       => $plan->name,
                        'ends_at'         => $subscription->ends_at?->toIso8601String(),
                    ],
                ]);
            }

            // Offline Payment
            if ($paymentMethod === 'offline') {
                $receiptPath = null;
                if ($request->hasFile('receipt_file')) {
                    $receiptPath = $this->fileUploadService->uploadPublicFile(
                        $request->file('receipt_file'),
                        'receipts/subscriptions'
                    );
                }

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'offline',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'receipt_file'         => $receiptPath,
                    'transaction_id'       => $request->transaction_id ?? null,
                    'notes'                => $request->notes ?? null,
                ]);

                $subscription = $this->subscriptionService->activateSubscription(
                    $user, $plan, $payment, UserSubscription::STATUS_PENDING_APPROVAL
                );

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Subscription request submitted successfully! Pending admin approval.',
                    'data'    => [
                        'payment_id'      => $payment->id,
                        'subscription_id' => $subscription->id,
                        'status'          => 'pending_approval',
                    ],
                ]);
            }

            // Razorpay
            if ($paymentMethod === 'razorpay') {
                $razorpayOrder = $this->createRazorpayOrder($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'razorpay',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'razorpay_order_id'    => $razorpayOrder['id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'  => $payment->id,
                        'order_id'    => $razorpayOrder['id'],
                        'amount'      => $razorpayOrder['amount'],
                        'currency'    => 'INR',
                        'key'         => $this->credentials('razorpay')['key'] ?? '',
                        'name'        => config('app.name'),
                        'description' => 'Subscription: ' . $plan->name,
                        'prefill'     => [
                            'name'    => $user->name,
                            'email'   => $user->email,
                            'contact' => $user->phone ?? '',
                        ],
                    ],
                ]);
            }

            // Stripe
            if ($paymentMethod === 'stripe') {
                $credentials = $this->credentials('stripe');
                $stripeSecret = $credentials['secret'] ?? '';
                Stripe::setApiKey($stripeSecret);

                $paymentIntent = StripePaymentIntent::create([
                    'amount'               => (int) ($amount * 100),
                    'currency'             => strtolower(settings('general.currency_code', 'USD')),
                    'payment_method_types' => ['card'],
                    'metadata'             => [
                        'subscription_plan_id' => $plan->id,
                        'user_id'              => $user->id,
                    ],
                ]);

                $payment = Payment::create([
                    'user_id'                  => $user->id,
                    'subscription_plan_id'     => $plan->id,
                    'payment_method'           => 'stripe',
                    'amount'                   => $amount,
                    'status'                   => Payment::STATUS_PENDING,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'stripe_client_secret'     => $paymentIntent->client_secret,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'        => $payment->id,
                        'payment_intent_id' => $paymentIntent->id,
                        'client_secret'     => $paymentIntent->client_secret,
                        'publishable_key'   => $credentials['key'] ?? '',
                        'amount'            => $amount,
                    ],
                ]);
            }

            // bKash
            if ($paymentMethod === 'bkash') {
                $bkashData = $this->createBkashPayment($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'bkash',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'bkash_payment_id'     => $bkashData['paymentID'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'   => $payment->id,
                        'checkout_url' => $bkashData['bkashURL'],
                        'paymentID'    => $bkashData['paymentID'],
                        'amount'       => $amount,
                    ],
                ]);
            }

            // SSLCommerz
            if ($paymentMethod === 'sslcommerz') {
                $sslSession = $this->createSslcommerzSession($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'sslcommerz',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'sslcommerz_tran_id'   => $sslSession['tran_id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'   => $payment->id,
                        'checkout_url' => $sslSession['gateway_url'],
                        'tran_id'      => $sslSession['tran_id'],
                        'amount'       => $amount,
                    ],
                ]);
            }

            // Paystack
            if ($paymentMethod === 'paystack') {
                $paystackTx = $this->createPaystackTx($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'paystack',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'paystack_reference'   => $paystackTx['reference'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'        => $payment->id,
                        'authorization_url' => $paystackTx['authorization_url'],
                        'reference'         => $paystackTx['reference'],
                        'access_code'       => $paystackTx['access_code'],
                        'amount'            => $amount,
                    ],
                ]);
            }

            // Flutterwave
            if ($paymentMethod === 'flutterwave') {
                $flwTx = $this->createFlutterwaveTx($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'flutterwave',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'flutterwave_tx_ref'   => $flwTx['tx_ref'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'   => $payment->id,
                        'checkout_url' => $flwTx['link'],
                        'tx_ref'       => $flwTx['tx_ref'],
                        'amount'       => $amount,
                    ],
                ]);
            }

            // PayPal
            if ($paymentMethod === 'paypal') {
                $paypalOrder = $this->createPaypalOrder($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'paypal',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'paypal_order_id'      => $paypalOrder['order_id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'   => $payment->id,
                        'order_id'     => $paypalOrder['order_id'],
                        'approve_url'  => $paypalOrder['approve_url'],
                        'amount'       => $amount,
                    ],
                ]);
            }

            // Mollie
            if ($paymentMethod === 'mollie') {
                $molliePayment = $this->createMolliePayment($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'mollie',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'mollie_payment_id'    => $molliePayment['payment_id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'   => $payment->id,
                        'checkout_url' => $molliePayment['checkout_url'],
                        'mollie_id'    => $molliePayment['payment_id'],
                        'amount'       => $amount,
                    ],
                ]);
            }

            // XPay
            if ($paymentMethod === 'xpay') {
                $xpaySession = $this->createXpaySession($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'xpay',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'xpay_order_id'        => $xpaySession['order_id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'   => $payment->id,
                        'checkout_url' => $xpaySession['checkout_url'],
                        'order_id'     => $xpaySession['order_id'],
                        'amount'       => $amount,
                    ],
                ]);
            }

            // Chapa
            if ($paymentMethod === 'chapa') {
                $chapaSession = $this->createChapaSession($amount, $plan, $user);

                $payment = Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'chapa',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'chapa_tx_ref'         => $chapaSession['tx_ref'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'data'    => [
                        'payment_id'   => $payment->id,
                        'checkout_url' => $chapaSession['checkout_url'],
                        'tx_ref'       => $chapaSession['tx_ref'],
                        'amount'       => $amount,
                    ],
                ]);
            }

            throw new \Exception('Unsupported payment method.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Subscription checkout error', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred initializing payment.',
            ], 500);
        }
    }

    /**
     * Verify mobile subscription payment and activate subscription.
     */
    public function verify(Request $request): JsonResponse
    {
        $user = Auth::user();
        /** @var User $user */

        $paymentId = $request->input('payment_id');
        $payment = Payment::where('user_id', $user->id)->find($paymentId);

        if (!$payment || !$payment->subscription_plan_id) {
            return response()->json(['success' => false, 'message' => 'Subscription payment record not found.'], 404);
        }

        // If gateway callback/webhook already completed the payment
        if ($payment->status === Payment::STATUS_COMPLETED) {
            $userSub = $user->activeSubscription();
            return response()->json([
                'success' => true,
                'message' => 'Payment verified! Subscription is active.',
                'data'    => [
                    'subscription_id' => $userSub?->id,
                    'plan_name'       => $userSub?->plan?->name,
                    'ends_at'         => $userSub?->ends_at?->toIso8601String(),
                ],
            ]);
        }

        // Chapa mobile verification
        if ($payment->payment_method === 'chapa' || $request->filled('tx_ref')) {
            $txRef = $request->input('tx_ref') ?: $payment->chapa_tx_ref;
            if (!$txRef) {
                return response()->json(['success' => false, 'message' => 'Missing transaction reference.'], 400);
            }
            $credentials = $this->credentials('chapa');
            $secretKey = $credentials['secret_key'] ?? null;
            if (!$secretKey) {
                return response()->json(['success' => false, 'message' => 'Chapa credentials not configured.'], 500);
            }

            try {
                $ch = curl_init('https://api.chapa.co/v1/transaction/verify/' . $txRef);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $secretKey,
                ]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                $resp = curl_exec($ch);
                $curlError = curl_error($ch);
                curl_close($ch);

                if ($curlError) {
                    return response()->json(['success' => false, 'message' => 'Chapa verify error: ' . $curlError], 500);
                }

                $verifyData = json_decode($resp, true) ?? [];
                $vStatus = strtolower($verifyData['data']['status'] ?? $verifyData['status'] ?? '');

                if ($vStatus === 'success') {
                    $reference = $verifyData['data']['reference'] ?? $verifyData['reference'] ?? null;
                    $payment->update([
                        'chapa_reference' => $reference,
                        'status'          => Payment::STATUS_COMPLETED,
                    ]);
                    $plan = SubscriptionPlan::findOrFail($payment->subscription_plan_id);
                    $userSub = $this->subscriptionService->activateSubscription($user, $plan, $payment, UserSubscription::STATUS_ACTIVE);
                    return response()->json([
                        'success' => true,
                        'message' => 'Chapa payment verified! Subscription activated.',
                        'data'    => [
                            'subscription_id' => $userSub->id,
                            'plan_name'       => $plan->name,
                            'ends_at'         => $userSub->ends_at?->toIso8601String(),
                        ],
                    ]);
                } else {
                    $payment->update(['status' => Payment::STATUS_FAILED]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Chapa payment was not successful (status: ' . ($vStatus ?: 'failed') . ').',
                    ], 400);
                }
            } catch (\Exception $e) {
                return response()->json(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()], 500);
            }
        }

        // In-app gateway verification (e.g., Razorpay payment_id & signature)
        if ($request->filled('razorpay_payment_id') && $payment->payment_method === 'razorpay') {
            $payment->update([
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'status'              => Payment::STATUS_COMPLETED,
            ]);
            $plan = SubscriptionPlan::findOrFail($payment->subscription_plan_id);
            $userSub = $this->subscriptionService->activateSubscription($user, $plan, $payment, UserSubscription::STATUS_ACTIVE);
            return response()->json([
                'success' => true,
                'message' => 'Razorpay payment verified! Subscription activated.',
                'data'    => [
                    'subscription_id' => $userSub->id,
                    'plan_name'       => $plan->name,
                    'ends_at'         => $userSub->ends_at?->toIso8601String(),
                ],
            ]);
        }

        // Stripe in-app payment intent verification
        if ($request->filled('payment_intent_id') && $payment->payment_method === 'stripe') {
            $payment->update(['status' => Payment::STATUS_COMPLETED]);
            $plan = SubscriptionPlan::findOrFail($payment->subscription_plan_id);
            $userSub = $this->subscriptionService->activateSubscription($user, $plan, $payment, UserSubscription::STATUS_ACTIVE);
            return response()->json([
                'success' => true,
                'message' => 'Stripe payment verified! Subscription activated.',
                'data'    => [
                    'subscription_id' => $userSub->id,
                    'plan_name'       => $plan->name,
                    'ends_at'         => $userSub->ends_at?->toIso8601String(),
                ],
            ]);
        }

        // Otherwise, payment status is pending or failed
        return response()->json([
            'success' => false,
            'message' => 'Payment has not been completed or was cancelled.',
        ], 400);
    }

    /**
     * Enroll in a course included in student's active subscription via API.
     */
    public function enrollCourse(Request $request, int $courseId): JsonResponse
    {
        $user = Auth::user();
        /** @var User $user */

        if (!$user->hasAccessToCourseViaSubscription($courseId)) {
            return response()->json([
                'success' => false,
                'message' => 'This course is not included in your active subscription plan.',
            ], 403);
        }

        $course = Course::findOrFail($courseId);

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$enrollment) {
            $enrollment = Enrollment::create([
                'user_id'     => $user->id,
                'course_id'   => $course->id,
                'status'      => 'approved',
                'enrolled_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Successfully enrolled in course via Subscription!',
            'data'    => [
                'enrollment_id' => $enrollment->id,
                'course_id'     => $course->id,
                'course_title'  => $course->title,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // Payment Gateway Helper Methods
    // ─────────────────────────────────────────────────────────────

    private function credentials(string $identifier): array
    {
        return $this->paymentGatewayService->credentialsFor($identifier);
    }

    private function createRazorpayOrder(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $credentials = $this->credentials('razorpay');
        $apiKey = $credentials['key'] ?? null;
        $apiSecret = $credentials['secret'] ?? null;

        if (!$apiKey || !$apiSecret) {
            throw new \RuntimeException('Razorpay credentials are not configured.');
        }

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount'   => (int) ($amount * 100),
            'currency' => 'INR',
            'receipt'  => 'sub_api_' . $plan->id . '_' . time(),
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        curl_close($ch);
        $orderData = json_decode($response, true);

        if (!isset($orderData['id'])) {
            throw new \Exception('Failed to create Razorpay order.');
        }

        return $orderData;
    }

    private function createSslcommerzSession(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $credentials = $this->credentials('sslcommerz');
        $storeId = $credentials['store_id'] ?? null;
        $storePassword = $credentials['store_password'] ?? null;
        $mode = $credentials['mode'] ?? 'sandbox';

        if (!$storeId || !$storePassword) {
            throw new \RuntimeException('SSLCommerz credentials are not configured.');
        }

        $baseUrl = $mode === 'live' ? 'https://securepay.sslcommerz.com' : 'https://sandbox.sslcommerz.com';
        $tranId = 'sub_api_' . $plan->id . '_' . time();

        $postData = [
            'store_id'         => $storeId,
            'store_passwd'     => $storePassword,
            'total_amount'     => number_format($amount, 2, '.', ''),
            'currency'         => 'BDT',
            'tran_id'          => $tranId,
            'success_url'      => route('payment.sslcommerz.callback') . '?status=success',
            'fail_url'         => route('payment.sslcommerz.callback') . '?status=fail',
            'cancel_url'       => route('payment.sslcommerz.callback') . '?status=cancel',
            'cus_name'         => $user->name,
            'cus_email'        => $user->email,
            'cus_phone'        => $user->phone ?? '01700000000',
            'shipping_method'  => 'NO',
            'product_name'     => 'Subscription: ' . $plan->name,
            'product_category' => 'Education',
            'product_profile'  => 'general',
        ];

        $ch = curl_init($baseUrl . '/gwprocess/v4/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);
        if (!isset($responseData['GatewayPageURL'])) {
            throw new \Exception('Failed to create SSLCommerz session.');
        }

        return [
            'tran_id'     => $tranId,
            'gateway_url' => $responseData['GatewayPageURL'],
        ];
    }

    private function createBkashPayment(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $credentials = $this->credentials('bkash');
        $appKey = $credentials['app_key'] ?? null;
        $appSecret = $credentials['app_secret'] ?? null;
        $username = $credentials['username'] ?? null;
        $password = $credentials['password'] ?? null;
        $mode = $credentials['mode'] ?? 'sandbox';

        $baseUrl = $mode === 'live' ? 'https://tokenized.pay.bka.sh/v1.2.0-beta' : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';

        $ch = curl_init($baseUrl . '/tokenized/checkout/token/grant');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['app_key' => $appKey, 'app_secret' => $appSecret]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'username: ' . $username, 'password: ' . $password]);
        $response = curl_exec($ch);
        curl_close($ch);
        $tokenData = json_decode($response, true);
        $idToken = $tokenData['id_token'] ?? null;

        if (!$idToken) {
            throw new \Exception('Failed to authenticate with bKash API.');
        }

        $ch = curl_init($baseUrl . '/tokenized/checkout/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'mode'                  => '0011',
            'payerReference'        => (string) $user->id,
            'callbackURL'           => route('payment.bkash.callback'),
            'amount'                => number_format($amount, 2, '.', ''),
            'currency'              => 'BDT',
            'intent'                => 'sale',
            'merchantInvoiceNumber' => 'SUB_' . $plan->id . '_' . time(),
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: ' . $idToken, 'X-APP-Key: ' . $appKey]);
        $response = curl_exec($ch);
        curl_close($ch);

        $createData = json_decode($response, true);
        if (!isset($createData['bkashURL'])) {
            throw new \Exception('Failed to create bKash payment.');
        }

        return $createData;
    }

    private function createPaystackTx(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $secretKey = $this->credentials('paystack')['secret_key'] ?? null;
        if (!$secretKey) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $paystack = new Paystack($secretKey);
        $reference = 'sub_api_' . $plan->id . '_' . time();

        $tx = $paystack->transaction->initialize([
            'email'        => $user->email,
            'amount'       => (int) ($amount * 100),
            'reference'    => $reference,
            'callback_url' => route('payment.paystack.callback'),
        ]);

        return [
            'reference'         => $tx->data->reference,
            'access_code'       => $tx->data->access_code,
            'authorization_url' => $tx->data->authorization_url,
        ];
    }

    private function createFlutterwaveTx(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $secretKey = $this->credentials('flutterwave')['secret_key'] ?? null;
        $txRef = 'sub_api_' . $plan->id . '_' . time();

        $ch = curl_init('https://api.flutterwave.com/v3/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secretKey, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'tx_ref'       => $txRef,
            'amount'       => $amount,
            'currency'     => 'NGN',
            'redirect_url' => route('payment.flutterwave.callback'),
            'customer'     => ['email' => $user->email, 'name' => $user->name],
            'customizations' => ['title' => 'Subscription: ' . $plan->name],
        ]));

        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);

        return [
            'tx_ref' => $txRef,
            'link'   => $responseData['data']['link'] ?? '',
        ];
    }

    private function createPaypalOrder(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $credentials = $this->credentials('paypal');
        $clientId = $credentials['client_id'] ?? null;
        $clientSecret = $credentials['client_secret'] ?? null;
        $mode = $credentials['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
        $response = curl_exec($ch);
        curl_close($ch);
        $tokenData = json_decode($response, true);
        $accessToken = $tokenData['access_token'] ?? null;

        $ch = curl_init($baseUrl . '/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'intent'         => 'CAPTURE',
            'purchase_units' => [['reference_id' => 'sub_api_' . $plan->id . '_' . time(), 'amount' => ['currency_code' => 'USD', 'value' => number_format($amount, 2, '.', '')]]],
            'application_context' => [
                'return_url' => route('payment.paypal.callback', ['success' => true]),
                'cancel_url' => route('payment.paypal.callback', ['success' => false]),
            ],
        ]));

        $response = curl_exec($ch);
        curl_close($ch);
        $orderData = json_decode($response, true);

        $approveUrl = '';
        foreach ($orderData['links'] ?? [] as $link) {
            if ($link['rel'] === 'approve') {
                $approveUrl = $link['href'];
                break;
            }
        }

        return [
            'order_id'    => $orderData['id'] ?? '',
            'approve_url' => $approveUrl,
        ];
    }

    private function createMolliePayment(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $apiKey = $this->credentials('mollie')['api_key'] ?? null;

        $ch = curl_init('https://api.mollie.com/v2/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount'      => ['currency' => 'EUR', 'value' => number_format($amount, 2, '.', '')],
            'description' => 'Subscription: ' . $plan->name,
            'redirectUrl' => route('payment.mollie.callback'),
        ]));

        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);

        return [
            'payment_id'   => $responseData['id'] ?? '',
            'checkout_url' => $responseData['_links']['checkout']['href'] ?? '',
        ];
    }

    private function createXpaySession(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $credentials = $this->credentials('xpay');
        $storeId = $credentials['store_id'] ?? null;
        $apiKey = $credentials['api_key'] ?? null;
        $mode = $credentials['mode'] ?? 'sandbox';

        $baseUrl = $mode === 'live' ? 'https://api.xpay.com.pk/api/v1' : 'https://sandbox.xpay.com.pk/api/v1';
        $orderId = 'XPAY_SUB_' . $plan->id . '_' . time();

        $ch = curl_init($baseUrl . '/checkout/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'store_id'     => $storeId,
            'order_id'     => $orderId,
            'amount'       => number_format($amount, 2, '.', ''),
            'currency'     => 'PKR',
            'description'  => 'Subscription: ' . $plan->name,
            'customer'     => ['name' => $user->name, 'email' => $user->email, 'phone' => $user->phone ?? ''],
            'callback_url' => route('payment.xpay.callback'),
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey]);

        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);

        return [
            'order_id'     => $orderId,
            'checkout_url' => $responseData['data']['checkout_url'] ?? $responseData['checkout_url'] ?? '',
        ];
    }

    private function createChapaSession(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $credentials = $this->credentials('chapa');
        $secretKey = $credentials['secret_key'] ?? null;

        if (!$secretKey) {
            throw new \RuntimeException('Chapa credentials (secret_key) are not configured.');
        }

        $txRef = 'sub_api_' . $plan->id . '_' . $user->id . '_' . time();

        $nameParts = explode(' ', trim($user->name ?? 'Student'), 2);
        $firstName = $nameParts[0] ?? 'Student';
        $lastName  = $nameParts[1] ?? 'User';

        $payload = [
            'amount'       => number_format($amount, 2, '.', ''),
            'currency'     => 'ETB',
            'email'        => $user->email,
            'first_name'   => $firstName,
            'last_name'    => $lastName,
            'phone_number' => $user->phone ?? '',
            'tx_ref'       => $txRef,
            'callback_url' => config('app.url') . '/payment/chapa/callback',
            'return_url'   => config('app.url') . '/payment/chapa/callback?tx_ref=' . $txRef,
            'customization' => [
                'title'       => config('app.name', 'Fira Link'),
                'description' => 'Subscription: ' . $plan->name,
            ],
        ];

        $ch = curl_init('https://api.chapa.co/v1/transaction/initialize');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $secretKey,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('Chapa initialize session cURL error: ' . $curlError);
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new \Exception('Failed to create Chapa checkout session. HTTP ' . $httpCode);
        }

        $responseData = json_decode($response, true);

        $checkoutUrl = $responseData['data']['checkout_url']
            ?? $responseData['checkout_url']
            ?? null;

        if (!$checkoutUrl) {
            $errorMsg = $responseData['message'] ?? 'Failed to initialize Chapa payment.';
            throw new \Exception('Chapa session creation error: ' . $errorMsg);
        }

        return [
            'tx_ref'       => $txRef,
            'checkout_url' => $checkoutUrl,
        ];
    }
}
