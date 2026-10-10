<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionCheckoutRequest;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\View\View;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Yabacon\Paystack;

/**
 * Subscription Controller (Frontend)
 *
 * Handles pricing plan listing, user subscription purchase, real payment processing, and student subscription management.
 * Integrated directly with the platform's unified checkout system.
 *
 * @package App\Http\Controllers\Front
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
     * Display subscription pricing page.
     */
    public function index(): View
    {
        $plans = $this->planService->getActivePlans();
        $userActiveSubscription = Auth::check() ? Auth::user()->activeSubscription() : null;

        return view('pages.subscriptions', compact('plans', 'userActiveSubscription'));
    }

    /**
     * Display subscription checkout page using existing unified checkout view.
     */
    public function checkout(int $planId): View|RedirectResponse
    {
        $user = Auth::user();
        /** @var User $user */

        if (!$user) {
            ToastMagic::warning('Please login to subscribe.');
            return redirect()->route('login');
        }

        if (!$user->isStudent()) {
            ToastMagic::error('Only students can purchase subscription plans.');
            return redirect()->route('home');
        }

        $plan = $this->planService->findPlan($planId);
        if ($plan->status !== 'active') {
            ToastMagic::error('This subscription plan is not available.');
            return redirect()->route('subscriptions.index');
        }

        $price = (float) $plan->effective_price;
        $onlineGateways = $this->paymentGatewayService->onlineGateways();
        $offlineGateway = $this->paymentGatewayService->offlineGateway();
        $offlineInstructions = $offlineGateway && $offlineGateway->is_enabled ? $offlineGateway->offline_instructions : null;

        $gatewayDescriptions = [
            'razorpay'    => 'Pay securely via Razorpay.',
            'stripe'      => 'Pay securely with credit/debit card via Stripe.',
            'paystack'    => 'Pay securely via Paystack.',
            'flutterwave' => 'Pay securely via Flutterwave.',
            'paypal'      => 'Pay securely via PayPal.',
            'sslcommerz'  => 'Pay securely via SSLCommerz.',
            'mollie'      => 'Pay securely via Mollie.',
            'bkash'       => 'Pay securely via bKash.',
            'xpay'        => 'Pay securely via XPay.',
            'chapa'       => 'Pay securely via Chapa (Ethiopia).',
        ];

        $isSubscription = true;
        $isBundle = false;
        $course = (object) [
            'id' => $plan->id,
            'title' => $plan->name,
            'featured_image' => null,
            'instructor' => null,
        ];

        return view('pages.subscription-checkout', compact('plan', 'price', 'onlineGateways', 'offlineGateway', 'offlineInstructions', 'gatewayDescriptions'));
    }

    /**
     * Process subscription checkout using real payment gateway integration.
     */
    public function processCheckout(SubscriptionCheckoutRequest $request, int $planId): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $user = Auth::user();
        /** @var User $user */

        $plan = $this->planService->findPlan($planId);
        if ($plan->status !== 'active') {
            ToastMagic::error('This subscription plan is not available.');
            return redirect()->route('subscriptions.index');
        }

        $paymentMethod = strtolower($request->payment_method);
        $amount = (float) $plan->effective_price;

        DB::beginTransaction();

        try {
            if ($paymentMethod === 'offline') {
                $receiptPath = null;
                if ($request->hasFile('receipt_file')) {
                    $receiptPath = $this->fileUploadService->uploadPublicFile(
                        $request->file('receipt_file'),
                        'receipts/subscriptions'
                    );
                }

                $payment = Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => $paymentMethod,
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'receipt_file' => $receiptPath,
                    'transaction_id' => $request->transaction_id ?? null,
                    'notes' => $request->notes ?? null,
                ]);

                $this->subscriptionService->activateSubscription(
                    $user,
                    $plan,
                    $payment,
                    UserSubscription::STATUS_PENDING_APPROVAL
                );

                DB::commit();

                ToastMagic::success('Subscription request submitted successfully! Pending admin approval.');
                return response()->json([
                    'success' => true,
                    'redirect_url' => route('student.subscription'),
                ]);
            }

            if ($paymentMethod === 'sslcommerz') {
                $sslSession = $this->createSslcommerzSession($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'sslcommerz',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'sslcommerz_tran_id' => $sslSession['tran_id'],
                    'sslcommerz_session_key' => $sslSession['session_key'] ?? null,
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $sslSession['gateway_url'],
                    'tran_id' => $sslSession['tran_id'],
                ]);
            }

            if ($paymentMethod === 'bkash') {
                $bkashPayment = $this->createBkashPayment($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'bkash',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'bkash_payment_id' => $bkashPayment['paymentID'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $bkashPayment['bkashURL'],
                    'payment_id' => $bkashPayment['paymentID'],
                ]);
            }

            if ($paymentMethod === 'stripe') {
                $stripeSession = $this->createStripeSession($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'stripe',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'stripe_payment_intent_id' => $stripeSession['session_id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'session_id' => $stripeSession['session_id'],
                    'checkout_url' => $stripeSession['url'],
                ]);
            }

            if ($paymentMethod === 'razorpay') {
                $razorpayOrder = $this->createRazorpayOrder($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'razorpay',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'razorpay_order_id' => $razorpayOrder['id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'order_id' => $razorpayOrder['id'],
                    'amount' => $razorpayOrder['amount'],
                    'key' => $this->credentials('razorpay')['key'] ?? '',
                    'name' => config('app.name'),
                    'description' => 'Subscription: ' . $plan->name,
                    'prefill' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'contact' => $user->phone ?? '',
                    ],
                    'callback_url' => route('payment.razorpay.callback'),
                ]);
            }

            if ($paymentMethod === 'paystack') {
                $paystackTx = $this->createPaystackTx($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'paystack',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'paystack_reference' => $paystackTx['reference'],
                    'paystack_access_code' => $paystackTx['access_code'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'authorization_url' => $paystackTx['authorization_url'],
                    'reference' => $paystackTx['reference'],
                ]);
            }

            if ($paymentMethod === 'flutterwave') {
                $flwTx = $this->createFlutterwaveTx($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'flutterwave',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'flutterwave_tx_ref' => $flwTx['tx_ref'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $flwTx['link'],
                    'tx_ref' => $flwTx['tx_ref'],
                ]);
            }

            if ($paymentMethod === 'paypal') {
                $paypalOrder = $this->createPaypalOrder($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'paypal',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'paypal_order_id' => $paypalOrder['order_id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $paypalOrder['approve_url'],
                    'order_id' => $paypalOrder['order_id'],
                ]);
            }

            if ($paymentMethod === 'mollie') {
                $molliePayment = $this->createMolliePayment($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'mollie',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'mollie_payment_id' => $molliePayment['payment_id'],
                ]);

                session(['mollie_payment_id' => $molliePayment['payment_id']]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $molliePayment['checkout_url'],
                    'payment_id' => $molliePayment['payment_id'],
                ]);
            }

            if ($paymentMethod === 'xpay') {
                $xpaySession = $this->createXpaySession($amount, $plan, $user);

                Payment::create([
                    'user_id' => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method' => 'xpay',
                    'amount' => $amount,
                    'status' => Payment::STATUS_PENDING,
                    'xpay_order_id' => $xpaySession['order_id'],
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $xpaySession['checkout_url'],
                    'order_id' => $xpaySession['order_id'],
                ]);
            }

            if ($paymentMethod === 'chapa') {
                $chapaSession = $this->createChapaSession($amount, $plan, $user);

                Payment::create([
                    'user_id'              => $user->id,
                    'subscription_plan_id' => $plan->id,
                    'payment_method'       => 'chapa',
                    'amount'               => $amount,
                    'status'               => Payment::STATUS_PENDING,
                    'chapa_tx_ref'         => $chapaSession['tx_ref'],
                ]);

                DB::commit();

                return response()->json([
                    'success'      => true,
                    'checkout_url' => $chapaSession['checkout_url'],
                    'tx_ref'       => $chapaSession['tx_ref'],
                ]);
            }

            throw new \Exception('Invalid payment method selected.');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An error occurred during payment processing.',
                ], 500);
            }

            ToastMagic::error($e->getMessage() ?: 'An error occurred during payment processing.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Display student's subscription management page in student dashboard.
     */
    public function mySubscription(): View|RedirectResponse
    {
        $user = Auth::user();
        /** @var User $user */

        if (!$user) {
            return redirect()->route('login');
        }

        $activeSubscription = $user->activeSubscription();
        $subscriptionHistory = $user->subscriptions()->with('plan', 'payment')->latest('id')->get();

        return view('student.subscription', compact('user', 'activeSubscription', 'subscriptionHistory'));
    }

    /**
     * Enroll in a course included in student's active subscription.
     */
    public function enrollViaSubscription(int $courseId): RedirectResponse
    {
        $user = Auth::user();
        /** @var User $user */

        if (!$user) {
            ToastMagic::warning('Please login to access courses.');
            return redirect()->route('login');
        }

        if (!$user->hasAccessToCourseViaSubscription($courseId)) {
            ToastMagic::error('This course is not included in your active subscription plan.');
            return redirect()->route('subscriptions.index');
        }

        $course = Course::findOrFail($courseId);

        $existingEnrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->first();

        if (!$existingEnrollment) {
            Enrollment::create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'status' => 'approved',
                'enrolled_at' => now(),
            ]);
        }

        ToastMagic::success('Enrolled in course via Subscription!');
        return redirect()->route('student.courses.access', $course->id);
    }

    // ─────────────────────────────────────────────────────────────
    //  Payment Gateway Helper Methods
    // ─────────────────────────────────────────────────────────────

    private function credentials(string $identifier): array
    {
        return $this->paymentGatewayService->credentialsFor($identifier);
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

        $baseUrl = $mode === 'live'
            ? 'https://securepay.sslcommerz.com'
            : 'https://sandbox.sslcommerz.com';

        $tranId = 'sub_' . $plan->id . '_user_' . $user->id . '_' . time();
        $amountInTaka = number_format($amount, 2, '.', '');

        $postData = [
            'store_id' => $storeId,
            'store_passwd' => $storePassword,
            'total_amount' => $amountInTaka,
            'currency' => 'BDT',
            'tran_id' => $tranId,
            'success_url' => route('payment.sslcommerz.callback') . '?status=success',
            'fail_url' => route('payment.sslcommerz.callback') . '?status=fail',
            'cancel_url' => route('payment.sslcommerz.callback') . '?status=cancel',
            'cus_name' => $user->name,
            'cus_email' => $user->email,
            'cus_phone' => $user->phone ?? '01700000000',
            'cus_add1' => 'Dhaka',
            'cus_city' => 'Dhaka',
            'cus_country' => 'Bangladesh',
            'shipping_method' => 'NO',
            'product_name' => 'Subscription: ' . $plan->name,
            'product_category' => 'Education',
            'product_profile' => 'general',
        ];

        $ch = curl_init($baseUrl . '/gwprocess/v4/api.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to create SSLCommerz session.');
        }

        $responseData = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            parse_str($response, $responseData);
        }

        if (!isset($responseData['GatewayPageURL'])) {
            throw new \Exception('Invalid response from SSLCommerz: ' . ($responseData['failedreason'] ?? 'Unknown error'));
        }

        return [
            'tran_id' => $tranId,
            'session_key' => $responseData['sessionkey'] ?? null,
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

        if (!$appKey || !$appSecret || !$username || !$password) {
            throw new \RuntimeException('bKash credentials are not configured.');
        }

        $baseUrl = $mode === 'live'
            ? 'https://tokenized.pay.bka.sh/v1.2.0-beta'
            : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta';

        $ch = curl_init($baseUrl . '/tokenized/checkout/token/grant');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'app_key' => $appKey,
            'app_secret' => $appSecret,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'username: ' . $username,
            'password: ' . $password,
        ]);

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
            'mode' => '0011',
            'payerReference' => $user->phone ?? '01700000000',
            'callbackURL' => route('payment.bkash.callback'),
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'BDT',
            'intent' => 'sale',
            'merchantInvoiceNumber' => 'SUB_' . $plan->id . '_' . time(),
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $idToken,
            'X-APP-Key: ' . $appKey,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);
        $createData = json_decode($response, true);

        if (!isset($createData['bkashURL'])) {
            throw new \Exception('Failed to create bKash payment: ' . ($createData['statusMessage'] ?? 'Unknown error'));
        }

        return $createData;
    }

    private function createStripeSession(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $apiSecret = $this->credentials('stripe')['secret'] ?? null;
        if (!$apiSecret) {
            throw new \RuntimeException('Stripe secret is not configured.');
        }

        Stripe::setApiKey($apiSecret);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Subscription: ' . $plan->name,
                    ],
                    'unit_amount' => (int) ($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('payment.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('payment.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}&canceled=true',
            'metadata' => [
                'subscription_plan_id' => $plan->id,
                'user_id' => $user->id,
            ],
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
        ];
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
            'amount' => (int) ($amount * 100),
            'currency' => 'INR',
            'receipt' => 'sub_' . $plan->id . '_user_' . $user->id . '_' . time(),
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

    private function createPaystackTx(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $secretKey = $this->credentials('paystack')['secret_key'] ?? null;
        if (!$secretKey) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $paystack = new Paystack($secretKey);
        $reference = 'sub_' . $plan->id . '_user_' . $user->id . '_' . time();

        $tx = $paystack->transaction->initialize([
            'email' => $user->email,
            'amount' => (int) ($amount * 100),
            'reference' => $reference,
            'callback_url' => route('payment.paystack.callback'),
        ]);

        return [
            'reference' => $tx->data->reference,
            'access_code' => $tx->data->access_code,
            'authorization_url' => $tx->data->authorization_url,
        ];
    }

    private function createFlutterwaveTx(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $secretKey = $this->credentials('flutterwave')['secret_key'] ?? null;
        if (!$secretKey) {
            throw new \RuntimeException('Flutterwave credentials are not configured.');
        }

        $txRef = 'sub_' . $plan->id . '_user_' . $user->id . '_' . time();

        $ch = curl_init('https://api.flutterwave.com/v3/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'tx_ref' => $txRef,
            'amount' => (float) $amount,
            'currency' => 'NGN',
            'redirect_url' => route('payment.flutterwave.callback'),
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
            ],
            'customizations' => [
                'title' => 'Subscription: ' . $plan->name,
            ],
        ]));

        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);

        return [
            'tx_ref' => $txRef,
            'link' => $responseData['data']['link'] ?? '',
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => 'sub_' . $plan->id . '_user_' . $user->id . '_' . time(),
                'amount' => ['currency_code' => 'USD', 'value' => number_format($amount, 2, '.', '')],
            ]],
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
            'order_id' => $orderData['id'] ?? '',
            'approve_url' => $approveUrl,
        ];
    }

    private function createMolliePayment(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $apiKey = $this->credentials('mollie')['api_key'] ?? null;

        $ch = curl_init('https://api.mollie.com/v2/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount' => [
                'currency' => 'EUR',
                'value' => number_format($amount, 2, '.', ''),
            ],
            'description' => 'Subscription: ' . $plan->name,
            'redirectUrl' => route('payment.mollie.callback'),
        ]));

        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);

        return [
            'payment_id' => $responseData['id'] ?? '',
            'checkout_url' => $responseData['_links']['checkout']['href'] ?? '',
        ];
    }

    private function createXpaySession(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $credentials = $this->credentials('xpay');
        $storeId = $credentials['store_id'] ?? null;
        $apiKey = $credentials['api_key'] ?? null;
        $mode = $credentials['mode'] ?? 'sandbox';

        $baseUrl = $mode === 'live' ? 'https://xpay.tech/api/v1' : 'https://sandbox.xpay.tech/api/v1';
        $orderId = 'sub_' . $plan->id . '_user_' . $user->id . '_' . time();

        $ch = curl_init($baseUrl . '/payments/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'store_id' => $storeId,
            'order_id' => $orderId,
            'amount' => number_format($amount, 2, '.', ''),
            'currency' => 'PKR',
            'redirect_url' => route('payment.xpay.callback'),
        ]));

        $response = curl_exec($ch);
        curl_close($ch);
        $responseData = json_decode($response, true);

        return [
            'order_id' => $orderId,
            'checkout_url' => $responseData['checkout_url'] ?? $responseData['url'] ?? '',
        ];
    }

    private function createChapaSession(float $amount, SubscriptionPlan $plan, User $user): array
    {
        $credentials = $this->credentials('chapa');
        $secretKey   = $credentials['secret_key'] ?? null;

        if (!$secretKey) {
            throw new \RuntimeException('Chapa credentials (secret_key) are not configured.');
        }

        $txRef = 'sub_' . $plan->id . '_user_' . $user->id . '_' . time();

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
            'callback_url' => route('payment.chapa.callback'),
            'return_url'   => route('payment.chapa.callback', ['tx_ref' => $txRef]),
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
