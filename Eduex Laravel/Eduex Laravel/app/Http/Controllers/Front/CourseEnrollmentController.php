<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\Payment;
use App\Services\FileUploadService;
use App\Services\PaymentGatewayService;
use App\Services\NotificationPreferenceService;
use App\Services\RevenueShareService;
use App\Services\SettingsRepository;
use App\Services\CouponService;
use App\Models\CouponUsage;
use App\Mail\StudentCourseEnrollmentMail;
use App\Mail\InstructorCourseEnrollmentMail;
use Illuminate\Support\Facades\Mail;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Yabacon\Paystack;

/**
 * Course Enrollment Controller
 *
 * This controller handles the course enrollment functionality.
 *
 * @package App\Http\Controllers\Front
 */
class CourseEnrollmentController extends Controller
{
    protected $fileUploadService;
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(
        FileUploadService $fileUploadService,
        PaymentGatewayService $paymentGatewayService,
        private NotificationPreferenceService $notificationPreferenceService,
        private RevenueShareService $revenueShareService,
        private SettingsRepository $settingsRepository,
        private CouponService $couponService
    ) {
        $this->fileUploadService = $fileUploadService;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Display checkout page
     */
    public function checkoutBundle($id)
    {
        request()->merge(['type' => 'bundle']);
        return $this->checkout($id);
    }
    
    public function processCheckoutBundle(CheckoutRequest $request, $id)
    {
        $request->merge(['type' => 'bundle']);
        return $this->processCheckout($request, $id);
    }

    public function checkout($id)
    {
        $user = Auth::user();
        /** @var User $user */

        if (!$user) {
            ToastMagic::warning('Please login to enroll.');
            return redirect()->route('login');
        }

        if (!$user->isStudent()) {
            ToastMagic::error('Only students can checkout.');
            return redirect()->route('home');
        }

        $isBundle = request()->input('type') === 'bundle';

        if ($isBundle) {
            $course = \App\Models\Bundle::with('vendor', 'courses')->findOrFail($id);
            if ($course->status !== 'active' || $course->approval_status !== 'approved') {
                ToastMagic::error('This bundle is not available.');
                return redirect()->route('home');
            }

            $ownedCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed'])
                ->pluck('course_id')
                ->toArray();

            $bundleCourseIds = $course->courses->pluck('id')->toArray();
            if (count(array_intersect($ownedCourseIds, $bundleCourseIds)) > 0) {
                ToastMagic::info('You already own some courses in this bundle. Proceeding will enroll you in the remaining courses and charge the full bundle price.');
            }
            $price = (float) $course->price;
        } else {
            $course = Course::with('instructor')->findOrFail($id);

            if ($course->status !== 'published' || $course->visibility !== 'public') {
                ToastMagic::error('This course is not available for enrollment.');
                return redirect()->route('courses.show', $course->id);
            }

            $existingEnrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingEnrollment) {
                ToastMagic::info('You are already enrolled in this course.');
                return redirect()->route('courses.show', $course->id);
            }

            $price = $this->calculatePrice($course);
        }

        $onlineGateways = $this->paymentGatewayService->onlineGateways();
        $offlineGateway = $this->paymentGatewayService->offlineGateway();
        $offlineInstructions = $offlineGateway && $offlineGateway->is_enabled ? $offlineGateway->offline_instructions : null;

        $gatewayDescriptions = [
            'razorpay'    => 'Pay securely with credit/debit card, UPI, or net banking via Razorpay.',
            'stripe'      => 'Pay securely with credit/debit card via Stripe.',
            'paystack'    => 'Pay securely with credit/debit card or bank transfer via Paystack.',
            'flutterwave' => 'Pay securely with multiple methods via Flutterwave.',
            'paypal'      => 'Pay securely with PayPal account or credit/debit card.',
            'sslcommerz'  => 'Pay with cards or mobile banking via SSLCommerz.',
            'mollie'      => 'Pay securely with multiple payment methods via Mollie.',
            'bkash'       => 'Pay securely with bKash mobile banking.',
            'xpay'        => 'Pay securely with credit/debit card via XPay (Pakistan).',
            'chapa'       => 'Pay securely with Telebirr, CBE Birr, and cards via Chapa (Ethiopia).',
        ];

        return view('checkout', compact('course', 'price', 'onlineGateways', 'offlineGateway', 'offlineInstructions', 'gatewayDescriptions', 'isBundle'));
    }

    /**
     * Process checkout
     */
    public function processCheckout(CheckoutRequest $request, $id)
    {
        $user = Auth::user();
        /** @var User $user */

        $isBundle = request()->input('type') === 'bundle';
        
        if ($isBundle) {
            $course = \App\Models\Bundle::with('courses')->findOrFail($id);
            if ($course->status !== 'active' || $course->approval_status !== 'approved') {
                ToastMagic::error('This bundle is not available.');
                return redirect()->route('home');
            }

            $ownedCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed'])
                ->pluck('course_id')
                ->toArray();

            $bundleCourseIds = $course->courses->pluck('id')->toArray();
            if (count(array_intersect($ownedCourseIds, $bundleCourseIds)) > 0) {
                // Wait for the duplicate flag in realistic scenarios, but let's just proceed
            }
            
            $unownedCourses = $course->courses->whereNotIn('id', $ownedCourseIds);
            if ($unownedCourses->isEmpty()) {
                ToastMagic::error('You already own all the courses in this bundle.');
                return redirect()->route('home');
            }

            $price = (float) $course->price;
            $courseIdToUse = $unownedCourses->first()->id;
        } else {
            $course = Course::findOrFail($id);

            if ($course->status !== 'published' || $course->visibility !== 'public') {
                ToastMagic::error('This course is not available for enrollment.');
                return redirect()->route('courses.show', $course->id);
            }

            $existingEnrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingEnrollment) {
                if (
                    $existingEnrollment->status === Enrollment::STATUS_APPROVED ||
                    $existingEnrollment->status === Enrollment::STATUS_COMPLETED
                ) {
                    ToastMagic::info('You are already enrolled in this course.');
                    return redirect()->route('courses.show', $course->id);
                }

                $payment = $existingEnrollment->payment;
                if ($payment && $payment->payment_method === Payment::PAYMENT_METHOD_OFFLINE) {
                    ToastMagic::warning('You have a pending offline payment request. Please wait for admin approval.');
                    return redirect()->route('courses.show', $course->id);
                }

                $existingEnrollment->delete();
            }

            $price = $this->calculatePrice($course);
            $courseIdToUse = $course->id;
        }
        $paymentMethod = strtolower($request->payment_method);

        // Apply coupon if provided (only for single-course enrollment, not bundles)
        $appliedCoupon = null;
        $discountAmount = 0.0;
        if (!$isBundle && $request->filled('coupon_code') && $price > 0) {
            try {
                $appliedCoupon = $this->couponService->validate($request->input('coupon_code'), $course, $user);
                $discountAmount = $this->couponService->calculateDiscount($appliedCoupon, $price);
                $price = max(0, $price - $discountAmount);
            } catch (\Exception $e) {
                ToastMagic::error($e->getMessage());
                return redirect()->back()->withInput();
            }
        }

        if ($price > 0 && !$this->paymentGatewayService->isEnabled($paymentMethod)) {
            ToastMagic::error('Selected payment method is not available.');
            return redirect()->back()->withInput();
        }

        DB::beginTransaction();

        try {
            if ($isBundle) {
                // Ensure BundleEnrollment exists
                \App\Models\BundleEnrollment::updateOrCreate(
                    ['user_id' => $user->id, 'bundle_id' => $course->id],
                    ['status' => 'pending']
                );
                // Pre-generate pending Course Enrollments for ALL UNOWNED courses
                // Already approved/completed enrollments are skipped entirely.
                foreach ($course->courses as $bundleCourseItem) {
                    if ($bundleCourseItem->id !== $courseIdToUse && !in_array($bundleCourseItem->id, $ownedCourseIds)) {
                        Enrollment::firstOrCreate(
                            ['user_id' => $user->id, 'course_id' => $bundleCourseItem->id],
                            ['status' => Enrollment::STATUS_PENDING]
                        );
                    }
                }
            }
            if ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_RAZORPAY) {
                $razorpayOrder = $this->createRazorpayOrder($price, $courseIdToUse, $user->id);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_RAZORPAY,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'razorpay_order_id' => $razorpayOrder['id'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'order_id' => $razorpayOrder['id'],
                    'amount' => $razorpayOrder['amount'],
                    'key' => $this->credentials('razorpay')['key'] ?? '',
                    'name' => config('app.name'),
                    'description' => 'Course Enrollment: ' . $course->title,
                    'prefill' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'contact' => $user->phone ?? '',
                    ],
                    'callback_url' => route('payment.razorpay.callback'),
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_STRIPE) {
                $stripeCheckoutSession = $this->createStripeCheckoutSession($price, $course, $user->id);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_STRIPE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'stripe_payment_intent_id' => $stripeCheckoutSession['session_id'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'session_id' => $stripeCheckoutSession['session_id'],
                    'checkout_url' => $stripeCheckoutSession['url'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_PAYSTACK) {
                $paystackTransaction = $this->createPaystackTransaction($price, $course, $user);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_PAYSTACK,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'paystack_reference' => $paystackTransaction['reference'],
                    'paystack_access_code' => $paystackTransaction['access_code'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'authorization_url' => $paystackTransaction['authorization_url'],
                    'reference' => $paystackTransaction['reference'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_FLUTTERWAVE) {
                $flutterwaveTransaction = $this->createFlutterwaveTransaction($price, $course, $user);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_FLUTTERWAVE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'flutterwave_tx_ref' => $flutterwaveTransaction['tx_ref'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $flutterwaveTransaction['link'],
                    'tx_ref' => $flutterwaveTransaction['tx_ref'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_PAYPAL) {
                $paypalOrder = $this->createPaypalOrder($price, $course, $user);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_PAYPAL,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'paypal_order_id' => $paypalOrder['order_id'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $paypalOrder['approve_url'],
                    'order_id' => $paypalOrder['order_id'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_SSLCOMMERZ) {
                // Pass $courseIdToUse so the tran_id encodes the real enrollment course_id
                $sslcommerzSession = $this->createSslcommerzSession($price, $course, $user, $courseIdToUse);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_SSLCOMMERZ,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'sslcommerz_tran_id' => $sslcommerzSession['tran_id'],
                    'sslcommerz_session_key' => $sslcommerzSession['session_key'] ?? null,
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'checkout_url' => $sslcommerzSession['gateway_url'],
                    'tran_id' => $sslcommerzSession['tran_id'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_MOLLIE) {
                $molliePayment = $this->createMolliePayment($price, $course, $user);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_MOLLIE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'mollie_payment_id' => $molliePayment['payment_id'],
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                session(['mollie_payment_id' => $molliePayment['payment_id']]);

                return response()->json([
                    'success' => true,
                    'checkout_url' => $molliePayment['checkout_url'],
                    'payment_id' => $molliePayment['payment_id'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_BKASH) {
                $bkashPayment = $this->createBkashPayment($price, $course, $user, $courseIdToUse);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id'   => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id'  => $enrollment->id,
                    'bundle_id'      => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_BKASH,
                    'amount'         => $price,
                    'status'         => Payment::STATUS_PENDING,
                    'bkash_payment_id' => $bkashPayment['paymentID'],
                    'coupon_id'      => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success'      => true,
                    'checkout_url' => $bkashPayment['bkashURL'],
                    'payment_id'   => $bkashPayment['paymentID'],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_XPAY) {
                $xpaySession = $this->createXpaySession($price, $course, $user, $courseIdToUse);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id'   => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id'  => $enrollment->id,
                    'bundle_id'      => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_XPAY,
                    'amount'         => $price,
                    'status'         => Payment::STATUS_PENDING,
                    'xpay_order_id'  => $xpaySession['order_id'],
                    'coupon_id'      => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success'       => true,
                    'checkout_url'  => $xpaySession['checkout_url'],
                    'order_id'      => $xpaySession['order_id'],
                    'enrollment_id' => $enrollment->id,
                    'bundle_id'     => $isBundle ? $course->id : null,
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_CHAPA) {
                $chapaSession = $this->createChapaSession($price, $course, $user, $courseIdToUse);

                $enrollment = Enrollment::firstOrCreate([
                    'user_id'   => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id'  => $enrollment->id,
                    'bundle_id'      => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_CHAPA,
                    'amount'         => $price,
                    'status'         => Payment::STATUS_PENDING,
                    'chapa_tx_ref'   => $chapaSession['tx_ref'],
                    'coupon_id'      => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success'       => true,
                    'checkout_url'  => $chapaSession['checkout_url'],
                    'tx_ref'        => $chapaSession['tx_ref'],
                    'enrollment_id' => $enrollment->id,
                    'bundle_id'     => $isBundle ? $course->id : null,
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_OFFLINE) {
                $receiptPath = null;
                if ($request->hasFile('receipt_file')) {
                    $receiptPath = $this->fileUploadService->uploadReceipt($request->file('receipt_file'));
                }

                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_PENDING,
                ]);

                $payment = Payment::create([
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'payment_method' => Payment::PAYMENT_METHOD_OFFLINE,
                    'amount' => $price,
                    'status' => Payment::STATUS_PENDING,
                    'receipt_file' => $receiptPath,
                    'transaction_id' => $request->transaction_id,
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                ToastMagic::success('Your enrollment request has been submitted. Please wait for admin approval.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } elseif ($price == 0) {
                $enrollment = Enrollment::create([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                if ($appliedCoupon) {
                    CouponUsage::create([
                        'coupon_id' => $appliedCoupon->id,
                        'user_id' => $user->id,
                        'enrollment_id' => $enrollment->id,
                        'discount_amount' => $discountAmount,
                    ]);
                    $appliedCoupon->increment('used_count');
                }

                if ($isBundle) {
                    // Also approve all other courses in the bundle
                    foreach ($course->courses as $bundleCourseItem) {
                        if ($bundleCourseItem->id == $courseIdToUse) continue;
                        
                        Enrollment::updateOrCreate(
                            ['user_id' => $user->id, 'course_id' => $bundleCourseItem->id],
                            [
                                'status' => Enrollment::STATUS_APPROVED,
                                'enrolled_at' => now(),
                            ]
                        );
                    }

                    // Create BundleEnrollment record
                    \App\Models\BundleEnrollment::updateOrCreate(
                        ['user_id' => $user->id, 'bundle_id' => $course->id],
                        [
                            'status' => 'approved',
                            'enrolled_at' => now(),
                        ]
                    );
                }

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment, $course, $user);

                ToastMagic::success('You have successfully enrolled in this course!');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                throw new \Exception('Invalid payment method selected for paid course.');
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage() ?: 'An error occurred during enrollment. Please try again.',
                ], 500);
            }

            ToastMagic::error('An error occurred during enrollment. Please try again.');
            return redirect()->back()->withInput();
        }
    }

    /**
     * Handle Razorpay payment callback
     */
    public function razorpayCallback(Request $request)
    {
        $request->validate([
            'razorpay_order_id'  => 'required|string',
            'razorpay_payment_id'=> 'required|string',
            'razorpay_signature' => 'required|string',
            // enrollment_id is optional – not present for subscription payments
            'enrollment_id'      => 'nullable|exists:enrollments,id',
        ]);

        // ── SUBSCRIPTION PAYMENT PATH ──────────────────────────────────────────
        // Detect by looking up the razorpay_order_id in the payments table
        $subscriptionPayment = Payment::where('razorpay_order_id', $request->razorpay_order_id)
            ->whereNotNull('subscription_plan_id')
            ->with(['subscriptionPlan', 'user'])
            ->first();

        if ($subscriptionPayment) {
            $isValid = $this->verifyRazorpaySignature(
                $request->razorpay_order_id,
                $request->razorpay_payment_id,
                $request->razorpay_signature
            );

            DB::beginTransaction();
            try {
                if ($isValid) {
                    $subscriptionPayment->update([
                        'razorpay_payment_id' => $request->razorpay_payment_id,
                        'razorpay_signature'  => $request->razorpay_signature,
                        'status'              => Payment::STATUS_COMPLETED,
                    ]);

                    $user = $subscriptionPayment->user;
                    $plan = $subscriptionPayment->subscriptionPlan;

                    // Re-login in case cross-site POST stripped the session cookie
                    if ($user && !Auth::check()) {
                        Auth::login($user, true);
                    }

                    // Activate the subscription (deactivates any previous active subscription)
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user,
                        $plan,
                        $subscriptionPayment,
                        \App\Models\UserSubscription::STATUS_ACTIVE
                    );

                    DB::commit();

                    ToastMagic::success('Payment successful! Your subscription to "' . $plan->name . '" is now active.');
                    return redirect()->route('student.subscription');
                } else {
                    $subscriptionPayment->update(['status' => Payment::STATUS_FAILED]);
                    DB::commit();

                    ToastMagic::error('Payment verification failed. Please contact support.');
                    return redirect()->route('subscriptions.index');
                }
            } catch (\Exception $e) {
                DB::rollBack();
                \Illuminate\Support\Facades\Log::error('Razorpay subscription callback error', [
                    'error'            => $e->getMessage(),
                    'razorpay_order_id'=> $request->razorpay_order_id,
                ]);
                ToastMagic::error('An error occurred processing your subscription payment. Please contact support.');
                return redirect()->route('subscriptions.index');
            }
        }

        // ── COURSE ENROLLMENT PAYMENT PATH (existing logic) ───────────────────
        if (!$request->enrollment_id) {
            ToastMagic::error('Invalid payment callback. No enrollment found.');
            return redirect()->route('courses');
        }

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->razorpay_order_id !== $request->razorpay_order_id) {
            ToastMagic::error('Invalid payment details.');
            return redirect()->route('courses.show', $enrollment->course_id);
        }

        $isValid = $this->verifyRazorpaySignature(
            $request->razorpay_order_id,
            $request->razorpay_payment_id,
            $request->razorpay_signature
        );

        DB::beginTransaction();

        try {
            if ($isValid) {
                $enrollment->payment->update([
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'razorpay_signature'  => $request->razorpay_signature,
                    'status'              => Payment::STATUS_COMPLETED,
                ]);

                $enrollment->update([
                    'status'      => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment);

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment verification failed. Please contact support.');
                return redirect()->route('courses.show', $enrollment->course_id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses.show', $enrollment->course_id);
        }
    }


    /**
     * Display success page
     */
    public function success($id)
    {
        $enrollment = Enrollment::with(['course.instructor', 'payment.bundle.courses.instructor'])->findOrFail($id);

        if ($enrollment->user_id !== Auth::id()) {
            ToastMagic::error('Unauthorized access.');
            return redirect()->route('home');
        }

        // Determine if this was a bundle purchase and load the bundle's courses
        $bundle = null;
        $bundleCourses = collect();
        if ($enrollment->payment && $enrollment->payment->bundle_id) {
            $bundle = $enrollment->payment->bundle;
            $bundleCourses = $bundle ? $bundle->courses : collect();
        }

        return view('enrollment-success', compact('enrollment', 'bundle', 'bundleCourses'));
    }

    /**
     * Calculate course price
     */
    private function calculatePrice(Course $course): float
    {
        if ($course->pricing_model === 'free') {
            return 0;
        }

        return $course->sale_price ? (float) $course->sale_price : (float) $course->regular_price;
    }

    /**
     * Create Razorpay order
     */
    private function createRazorpayOrder(float $amount, int $courseId, int $userId): array
    {
        $credentials = $this->credentials('razorpay');
        $apiKey = $credentials['key'] ?? null;
        $apiSecret = $credentials['secret'] ?? null;

        if (!$apiKey || !$apiSecret) {
            throw new \RuntimeException('Razorpay credentials are not configured.');
        }

        $amountInPaise = (int) ($amount * 100);

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'receipt' => 'course_' . $courseId . '_user_' . $userId . '_' . time(),
            'notes' => [
                'course_id' => $courseId,
                'user_id' => $userId,
            ],
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to create Razorpay order.');
        }

        $orderData = json_decode($response, true);

        if (!isset($orderData['id'])) {
            throw new \Exception('Invalid response from Razorpay.');
        }

        return $orderData;
    }

    /**
     * Verify Razorpay signature
     */
    private function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $apiSecret = $this->credentials('razorpay')['secret'] ?? null;

        if (!$apiSecret) {
            return false;
        }

        $payload = $orderId . '|' . $paymentId;
        $expectedSignature = hash_hmac('sha256', $payload, $apiSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Create Stripe checkout session
     */
    private function createStripeCheckoutSession(float $amount, $course, int $userId): array
    {
        $courseIdToUse = $course->id;
        $apiSecret = $this->credentials('stripe')['secret'] ?? null;

        if (!$apiSecret) {
            throw new \RuntimeException('Stripe secret is not configured.');
        }

        Stripe::setApiKey($apiSecret);

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => $course->title,
                                'description' => 'Course Enrollment',
                            ],
                            'unit_amount' => (int) ($amount * 100),
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'success_url' => route('payment.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.stripe.callback') . '?session_id={CHECKOUT_SESSION_ID}&canceled=true',
                'metadata' => [
                    'course_id' => $courseIdToUse,
                    'user_id' => $userId,
                ],
            ]);

            return [
                'session_id' => $session->id,
                'url' => $session->url,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception('Failed to create Stripe checkout session: ' . $e->getMessage());
        }
    }

    /**
     * Handle Stripe payment callback
     */
    public function stripeCallback(Request $request)
    {
        $request->validate([
            'session_id' => 'required|string',
        ]);

        $apiSecret = $this->credentials('stripe')['secret'] ?? null;
        if (!$apiSecret) {
            ToastMagic::error('Stripe configuration error.');
            return redirect()->route('courses');
        }

        Stripe::setApiKey($apiSecret);

        try {
            $session = Session::retrieve($request->session_id);

            if (!$session->metadata) {
                ToastMagic::error('Invalid payment session.');
                return redirect()->route('courses');
            }

            // Check if subscription payment
            $payment = Payment::where('stripe_payment_intent_id', $session->id)->first();
            if ($payment && $payment->subscription_plan_id) {
                DB::beginTransaction();
                if ($session->payment_status === 'paid') {
                    $payment->update(['status' => Payment::STATUS_COMPLETED]);
                    $user = User::find($payment->user_id);
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    if ($user && (!Auth::check() || Auth::id() !== $user->id)) {
                        Auth::login($user, true);
                    }
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                    );
                    DB::commit();
                    ToastMagic::success('Payment successful! Your subscription to "' . ($plan->name ?? 'Plan') . '" is now active.');
                    return redirect()->route('student.subscription');
                } else {
                    $payment->update(['status' => Payment::STATUS_FAILED]);
                    DB::commit();
                    ToastMagic::error('Payment was not successful. Please try again.');
                    return redirect()->route('subscriptions.index');
                }
            }

            $courseId = $session->metadata['course_id'] ?? ($session->metadata->course_id ?? null);
            $userId = $session->metadata['user_id'] ?? ($session->metadata->user_id ?? null);

            if (!$courseId || !$userId) {
                ToastMagic::error('Invalid payment session metadata.');
                return redirect()->route('courses');
            }

            $enrollment = Enrollment::with('payment')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereHas('payment', function ($query) use ($session) {
                    $query->where('stripe_payment_intent_id', $session->id);
                })
                ->first();

            if (!$enrollment || !$enrollment->payment) {
                ToastMagic::error('Enrollment not found.');
                return redirect()->route('courses.show', $courseId);
            }

            DB::beginTransaction();

            if ($request->has('canceled') && $request->canceled === 'true') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was canceled.');
                return redirect()->route('courses.show', $courseId);
            }

            if ($session->payment_status === 'paid') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                ]);

                $enrollment->update([
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment);

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }
        } catch (ApiErrorException $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Create Paystack transaction
     */
    private function createPaystackTransaction(float $amount, $course, $user): array
    {
        $courseIdToUse = $course->id;
        $secretKey = $this->credentials('paystack')['secret_key'] ?? null;

        if (!$secretKey) {
            throw new \RuntimeException('Paystack secret key is not configured.');
        }

        $paystack = new Paystack($secretKey);

        $reference = 'course_' . $courseIdToUse . '_user_' . $user->id . '_' . time();
        $amountInKobo = (int) ($amount * 100);

        try {
            $transaction = $paystack->transaction->initialize([
                'email' => $user->email,
                'amount' => $amountInKobo,
                'reference' => $reference,
                'callback_url' => route('payment.paystack.callback'),
                'metadata' => [
                    'course_id' => $courseIdToUse,
                    'user_id' => $user->id,
                    'course_title' => $course->title,
                ],
            ]);

            if (!$transaction->status || !$transaction->data) {
                throw new \Exception('Failed to initialize Paystack transaction.');
            }

            return [
                'reference' => $transaction->data->reference,
                'access_code' => $transaction->data->access_code,
                'authorization_url' => $transaction->data->authorization_url,
            ];
        } catch (\Exception $e) {
            throw new \Exception('Failed to create Paystack transaction: ' . $e->getMessage());
        }
    }

    /**
     * Handle Paystack payment callback
     */
    public function paystackCallback(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
        ]);

        $secretKey = $this->credentials('paystack')['secret_key'] ?? null;
        if (!$secretKey) {
            ToastMagic::error('Paystack configuration error.');
            return redirect()->route('courses');
        }

        $paystack = new Paystack($secretKey);

        try {
            $transaction = $paystack->transaction->verify([
                'reference' => $request->reference,
            ]);

            if (!$transaction->status || !$transaction->data) {
                ToastMagic::error('Invalid payment transaction.');
                return redirect()->route('courses');
            }

            $paymentData = $transaction->data;

            // Check if subscription payment
            $payment = Payment::where('paystack_reference', $request->reference)->first();
            if ($payment && $payment->subscription_plan_id) {
                DB::beginTransaction();
                if ($paymentData->status === 'success') {
                    $payment->update(['status' => Payment::STATUS_COMPLETED]);
                    $user = User::find($payment->user_id);
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    if ($user && (!Auth::check() || Auth::id() !== $user->id)) {
                        Auth::login($user, true);
                    }
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                    );
                    DB::commit();
                    ToastMagic::success('Payment successful! Your subscription to "' . ($plan->name ?? 'Plan') . '" is now active.');
                    return redirect()->route('student.subscription');
                } else {
                    $payment->update(['status' => Payment::STATUS_FAILED]);
                    DB::commit();
                    ToastMagic::error('Payment was not successful.');
                    return redirect()->route('subscriptions.index');
                }
            }

            $metadata = $paymentData->metadata ?? null;

            if (!$metadata || !isset($metadata->course_id) || !isset($metadata->user_id)) {
                ToastMagic::error('Invalid payment transaction metadata.');
                return redirect()->route('courses');
            }

            $courseId = $metadata->course_id;
            $userId = $metadata->user_id;

            $enrollment = Enrollment::with('payment')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereHas('payment', function ($query) use ($request) {
                    $query->where('paystack_reference', $request->reference);
                })
                ->first();

            if (!$enrollment || !$enrollment->payment) {
                ToastMagic::error('Enrollment not found.');
                return redirect()->route('courses.show', $courseId);
            }

            DB::beginTransaction();

            if ($paymentData->status === 'success' && $paymentData->gateway_response === 'Successful') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                ]);

                $enrollment->update([
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment);

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Create Flutterwave transaction
     */
    private function createFlutterwaveTransaction(float $amount, $course, $user): array
    {
        $courseIdToUse = $course->id;
        $credentials = $this->credentials('flutterwave');
        $publicKey = $credentials['public_key'] ?? null;
        $secretKey = $credentials['secret_key'] ?? null;

        if (!$publicKey || !$secretKey) {
            throw new \RuntimeException('Flutterwave credentials are not configured.');
        }

        $txRef = 'course_' . $courseIdToUse . '_user_' . $user->id . '_' . time();
        $amountInNaira = (float) $amount;

        $data = [
            'tx_ref' => $txRef,
            'amount' => $amountInNaira,
            'currency' => 'NGN',
            'redirect_url' => route('payment.flutterwave.callback'),
            'payment_options' => 'card,account,ussd,transfer',
            'customer' => [
                'email' => $user->email,
                'name' => $user->name,
                'phone_number' => $user->phone ?? '',
            ],
            'customizations' => [
                'title' => 'Course Enrollment',
                'description' => 'Course Enrollment: ' . $course->title,
            ],
            'meta' => [
                'course_id' => $courseIdToUse,
                'user_id' => $user->id,
            ],
        ];

        $ch = curl_init('https://api.flutterwave.com/v3/payments');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to create Flutterwave transaction.');
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['status']) || $responseData['status'] !== 'success' || !isset($responseData['data']['link'])) {
            throw new \Exception('Invalid response from Flutterwave.');
        }

        return [
            'tx_ref' => $txRef,
            'link' => $responseData['data']['link'],
        ];
    }

    /**
     * Handle Flutterwave payment callback
     */
    public function flutterwaveCallback(Request $request)
    {
        $request->validate([
            'transaction_id' => 'nullable|string',
            'tx_ref' => 'nullable|string',
            'status' => 'nullable|string',
        ]);

        $secretKey = $this->credentials('flutterwave')['secret_key'] ?? null;
        if (!$secretKey) {
            ToastMagic::error('Flutterwave configuration error.');
            return redirect()->route('courses');
        }

        try {
            $transactionId = $request->transaction_id;
            $txRef = $request->tx_ref;

            if (!$transactionId && !$txRef) {
                ToastMagic::error('Invalid payment callback.');
                return redirect()->route('courses');
            }

            $verifyUrl = $transactionId
                ? 'https://api.flutterwave.com/v3/transactions/' . $transactionId . '/verify'
                : 'https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref=' . $txRef;

            $ch = curl_init($verifyUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception('Failed to verify Flutterwave transaction.');
            }

            $responseData = json_decode($response, true);

            if (!isset($responseData['status']) || $responseData['status'] !== 'success' || !isset($responseData['data'])) {
                ToastMagic::error('Invalid payment transaction.');
                return redirect()->route('courses');
            }

            $transactionData = $responseData['data'];
            $txRefFromResponse = $transactionData['tx_ref'] ?? $txRef;

            // Check if subscription payment
            $payment = Payment::where('flutterwave_tx_ref', $txRefFromResponse)->first();
            if ($payment && $payment->subscription_plan_id) {
                DB::beginTransaction();
                $transactionAmount = (float) ($transactionData['amount'] ?? 0);
                if ($transactionData['status'] === 'successful' && abs($transactionAmount - $payment->amount) < 0.01) {
                    $payment->update([
                        'flutterwave_transaction_id' => $transactionData['id'] ?? null,
                        'status' => Payment::STATUS_COMPLETED,
                    ]);
                    $user = User::find($payment->user_id);
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    if ($user && (!Auth::check() || Auth::id() !== $user->id)) {
                        Auth::login($user, true);
                    }
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                    );
                    DB::commit();
                    ToastMagic::success('Payment successful! Your subscription to "' . ($plan->name ?? 'Plan') . '" is now active.');
                    return redirect()->route('student.subscription');
                } else {
                    $payment->update([
                        'flutterwave_transaction_id' => $transactionData['id'] ?? null,
                        'status' => Payment::STATUS_FAILED,
                    ]);
                    DB::commit();
                    ToastMagic::error('Payment was not successful.');
                    return redirect()->route('subscriptions.index');
                }
            }

            $meta = $transactionData['meta'] ?? null;

            if (!$meta || !isset($meta['course_id']) || !isset($meta['user_id'])) {
                ToastMagic::error('Invalid payment transaction metadata.');
                return redirect()->route('courses');
            }

            $courseId = $meta['course_id'];
            $userId = $meta['user_id'];
            $txRefFromResponse = $transactionData['tx_ref'] ?? $txRef;

            $enrollment = Enrollment::with('payment')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereHas('payment', function ($query) use ($txRefFromResponse) {
                    $query->where('flutterwave_tx_ref', $txRefFromResponse);
                })
                ->first();

            if (!$enrollment || !$enrollment->payment) {
                ToastMagic::error('Enrollment not found.');
                return redirect()->route('courses.show', $courseId);
            }

            DB::beginTransaction();

            $transactionAmount = (float) ($transactionData['amount'] ?? 0);

            if ($transactionData['status'] === 'successful' && abs($transactionAmount - $enrollment->payment->amount) < 0.01) {
                $enrollment->payment->update([
                    'flutterwave_transaction_id' => $transactionData['id'] ?? null,
                    'status' => Payment::STATUS_COMPLETED,
                ]);

                $enrollment->update([
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment);

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $enrollment->payment->update([
                    'flutterwave_transaction_id' => $transactionData['id'] ?? null,
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Get PayPal OAuth access token
     */
    private function getPaypalAccessToken(): string
    {
        $credentials = $this->credentials('paypal');
        $clientId = $credentials['client_id'] ?? null;
        $clientSecret = $credentials['client_secret'] ?? null;
        $mode = $credentials['mode'] ?? 'sandbox';

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException('PayPal credentials are not configured.');
        }

        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $ch = curl_init($baseUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $clientId . ':' . $clientSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to get PayPal access token.');
        }

        $tokenData = json_decode($response, true);

        if (!isset($tokenData['access_token'])) {
            throw new \Exception('Invalid response from PayPal token endpoint.');
        }

        return $tokenData['access_token'];
    }

    /**
     * Create PayPal order
     */
    private function createPaypalOrder(float $amount, $course, $user): array
    {
        $courseIdToUse = $course->id;
        $accessToken = $this->getPaypalAccessToken();
        $mode = $this->credentials('paypal')['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $orderData = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'course_' . $courseIdToUse . '_user_' . $user->id . '_' . time(),
                    'description' => 'Course Enrollment: ' . $course->title,
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amount, 2, '.', ''),
                    ],
                ]
            ],
            'application_context' => [
                'brand_name' => config('app.name'),
                'landing_page' => 'BILLING',
                'user_action' => 'PAY_NOW',
                'return_url' => route('payment.paypal.callback', ['success' => true]),
                'cancel_url' => route('payment.paypal.callback', ['success' => false]),
            ],
        ];

        $ch = curl_init($baseUrl . '/v2/checkout/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'PayPal-Request-Id: ' . uniqid(),
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($orderData));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 201) {
            throw new \Exception('Failed to create PayPal order.');
        }

        $orderResponse = json_decode($response, true);

        if (!isset($orderResponse['id']) || !isset($orderResponse['links'])) {
            throw new \Exception('Invalid response from PayPal.');
        }

        $approveUrl = null;
        foreach ($orderResponse['links'] as $link) {
            if ($link['rel'] === 'approve') {
                $approveUrl = $link['href'];
                break;
            }
        }

        if (!$approveUrl) {
            throw new \Exception('Approve URL not found in PayPal response.');
        }

        return [
            'order_id' => $orderResponse['id'],
            'approve_url' => $approveUrl,
        ];
    }

    /**
     * Handle PayPal payment callback
     */
    public function paypalCallback(Request $request)
    {
        $request->validate([
            'token' => 'nullable|string',
            'PayerID' => 'nullable|string',
        ]);

        if ($request->has('success') && $request->success === 'false') {
            ToastMagic::error('Payment was canceled.');
            return redirect()->route('courses');
        }

        $accessToken = $this->getPaypalAccessToken();
        $mode = $this->credentials('paypal')['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $orderId = $request->token;

        if (!$orderId) {
            ToastMagic::error('Invalid payment callback.');
            return redirect()->route('courses');
        }

        try {
            $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $orderId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception('Failed to get PayPal order details.');
            }

            $orderData = json_decode($response, true);

            if (!isset($orderData['id']) || !isset($orderData['purchase_units'])) {
                ToastMagic::error('Invalid PayPal order.');
                return redirect()->route('courses');
            }

            // Check if subscription payment
            $payment = Payment::where('paypal_order_id', $orderId)->first();
            if ($payment && $payment->subscription_plan_id) {
                DB::beginTransaction();
                $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $orderId . '/capture');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $accessToken,
                ]);

                $captureResponse = curl_exec($ch);
                $captureHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $captureData = json_decode($captureResponse, true);
                if (($captureHttpCode === 201 || $captureHttpCode === 200) && isset($captureData['status']) && $captureData['status'] === 'COMPLETED') {
                    $payment->update(['status' => Payment::STATUS_COMPLETED]);
                    $user = User::find($payment->user_id);
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    if ($user && (!Auth::check() || Auth::id() !== $user->id)) {
                        Auth::login($user, true);
                    }
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                    );
                    DB::commit();
                    ToastMagic::success('Payment successful! Your subscription to "' . ($plan->name ?? 'Plan') . '" is now active.');
                    return redirect()->route('student.subscription');
                } else {
                    $payment->update(['status' => Payment::STATUS_FAILED]);
                    DB::commit();
                    ToastMagic::error('PayPal payment capture failed.');
                    return redirect()->route('subscriptions.index');
                }
            }

            $referenceId = $orderData['purchase_units'][0]['reference_id'] ?? '';
            preg_match('/course_(\d+)_user_(\d+)_/', $referenceId, $matches);

            if (count($matches) !== 3) {
                ToastMagic::error('Invalid order reference.');
                return redirect()->route('courses');
            }

            $courseId = (int) $matches[1];
            $userId = (int) $matches[2];

            $enrollment = Enrollment::with('payment')
                ->where('user_id', $userId)
                ->where('course_id', $courseId)
                ->whereHas('payment', function ($query) use ($orderId) {
                    $query->where('paypal_order_id', $orderId);
                })
                ->first();

            if (!$enrollment || !$enrollment->payment) {
                ToastMagic::error('Enrollment not found.');
                return redirect()->route('courses.show', $courseId);
            }

            DB::beginTransaction();

            $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $orderId . '/capture');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 201) {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment capture failed. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }

            $captureData = json_decode($response, true);

            if (!isset($captureData['status']) || $captureData['status'] !== 'COMPLETED') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }

            $payerId = $captureData['payer']['payer_id'] ?? null;
            $paymentId = $captureData['purchase_units'][0]['payments']['captures'][0]['id'] ?? null;

            $enrollment->payment->update([
                'paypal_payer_id' => $payerId,
                'paypal_payment_id' => $paymentId,
                'status' => Payment::STATUS_COMPLETED,
            ]);

            $enrollment->update([
                'status' => Enrollment::STATUS_APPROVED,
                'enrolled_at' => now(),
            ]);

            $this->processRevenueShare($enrollment);

            DB::commit();

            $this->sendEnrollmentNotifications($enrollment);

            ToastMagic::success('Payment successful! You have been enrolled in the course.');
            return redirect()->route('enrollment.success', $enrollment->id);
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Create SSLCommerz transaction session
     */
    private function createSslcommerzSession(float $amount, $course, $user, ?int $courseIdOverride = null): array
    {
        // Use the actual course_id for the enrollment (not bundle ID) so the callback can find the enrollment
        $courseIdToUse = $courseIdOverride ?? $course->id;
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

        $tranId = 'course_' . $courseIdToUse . '_user_' . $user->id . '_' . time();
        $amountInTaka = number_format($amount, 2, '.', '');

        $phone = $user->phone;
        if (empty($phone) || trim($phone) === '') {
            $phone = '01700000000';
        }

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
            'cus_phone' => $phone,
            'cus_add1' => '',
            'cus_city' => '',
            'cus_country' => 'Bangladesh',
            'shipping_method' => 'NO',
            'product_name' => 'Course Enrollment: ' . $course->title,
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
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('SSLCommerz cURL error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new \Exception('Failed to create SSLCommerz session. HTTP Code: ' . $httpCode);
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            parse_str($response, $responseData);
        }

        if (!isset($responseData['status']) || $responseData['status'] !== 'SUCCESS') {
            $errorMsg = $responseData['failedreason'] ?? $responseData['error'] ?? 'Unknown error';
            throw new \Exception('SSLCommerz error: ' . $errorMsg . ' (Response: ' . substr($response, 0, 200) . ')');
        }

        if (!isset($responseData['GatewayPageURL'])) {
            throw new \Exception('SSLCommerz did not return GatewayPageURL. Response: ' . substr($response, 0, 200));
        }

        return [
            'tran_id' => $tranId,
            'session_key' => $responseData['sessionkey'] ?? null,
            'gateway_url' => $responseData['GatewayPageURL'],
        ];
    }

    /**
     * Validate SSLCommerz transaction
     */
    private function validateSslcommerzTransaction(?string $valId, string $tranId): array
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

        if ($valId) {
            $postData = [
                'val_id' => $valId,
                'store_id' => $storeId,
                'store_passwd' => $storePassword,
                'format' => 'json',
            ];
        } else {
            $postData = [
                'tran_id' => $tranId,
                'store_id' => $storeId,
                'store_passwd' => $storePassword,
                'format' => 'json',
            ];
        }

        $ch = curl_init($baseUrl . '/validator/api/validationserverAPI.php');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('SSLCommerz validation cURL error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new \Exception('Failed to validate SSLCommerz transaction. HTTP Code: ' . $httpCode);
        }

        $validationData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from SSLCommerz validation API.');
        }

        if (!isset($validationData['status']) || $validationData['status'] !== 'VALID') {
            $errorMsg = $validationData['error'] ?? $validationData['failedreason'] ?? 'Unknown error';
            throw new \Exception('SSLCommerz validation failed: ' . $errorMsg);
        }

        if (!isset($validationData['tran_id']) || $validationData['tran_id'] !== $tranId) {
            throw new \Exception('Transaction ID mismatch in validation response.');
        }

        return $validationData;
    }

    /**
     * Handle SSLCommerz payment callback
     */
    public function sslcommerzCallback(Request $request)
    {
        $status = $request->query('status') ?? $request->input('status');
        $tranId = $request->input('tran_id') ?? $request->query('tran_id');
        $valId = $request->input('val_id') ?? $request->query('val_id');

        if (!$status || !in_array($status, ['success', 'fail', 'cancel'])) {

            ToastMagic::error('Invalid payment callback.');
            return redirect()->route('courses');
        }

        try {
            if ($status === 'cancel') {
                ToastMagic::error('Payment was canceled.');
                return redirect()->route('courses');
            }

            if (!$tranId) {
                ToastMagic::error('Invalid payment callback - missing transaction ID.');
                return redirect()->route('courses');
            }

            // Look up payment directly by tran_id
            $payment = Payment::where('sslcommerz_tran_id', $tranId)->first();

            if (!$payment) {
                ToastMagic::error('Payment record not found.');
                return redirect()->route('home');
            }

            if ($payment->subscription_plan_id) {
                if ($status === 'success') {
                    $payment->update([
                        'sslcommerz_val_id' => $valId,
                        'status' => Payment::STATUS_COMPLETED,
                    ]);
                    $plan = \App\Models\SubscriptionPlan::findOrFail($payment->subscription_plan_id);
                    $user = \App\Models\User::findOrFail($payment->user_id);
                    if (!Auth::check() || Auth::id() !== $user->id) {
                        Auth::login($user, true);
                    }
                    app(\App\Services\SubscriptionService::class)->activateSubscription($user, $plan, $payment);
                    ToastMagic::success('Payment successful! Your subscription is active now.');
                    return redirect()->route('student.subscription');
                } else {
                    $payment->update(['status' => Payment::STATUS_FAILED]);
                    ToastMagic::error('Payment was not successful. Please try again.');
                    return redirect()->route('subscriptions.index');
                }
            }

            $enrollment = $payment->enrollment ? $payment->enrollment->load('payment') : null;
            if (!$enrollment || !$enrollment->payment) {
                ToastMagic::error('Enrollment not found.');
                return redirect()->route('courses');
            }
            $courseId = $enrollment->course_id;
            $userId = $enrollment->user_id;

            DB::beginTransaction();

            if ($status === 'fail') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }

            if ($status === 'success') {
                try {
                    $postStatus = $request->input('status');
                    $paidAmount = (float) ($request->input('amount') ?? 0);

                    if ($postStatus !== 'VALID') {

                        throw new \Exception('Transaction status is not VALID: ' . $postStatus);
                    }

                    if (abs($paidAmount - $enrollment->payment->amount) > 0.01) {

                        throw new \Exception('Amount mismatch.');
                    }

                    $finalValId = $valId;
                    if ($valId) {
                        try {
                            $validationData = $this->validateSslcommerzTransaction($valId, $tranId);
                            $finalValId = $validationData['val_id'] ?? $valId;
                        } catch (\Exception $validationError) {
                        }
                    }

                    $enrollment->payment->update([
                        'sslcommerz_val_id' => $finalValId,
                        'status' => Payment::STATUS_COMPLETED,
                    ]);

                    $enrollment->update([
                        'status' => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);

                    $this->processRevenueShare($enrollment);

                    DB::commit();

                    $this->sendEnrollmentNotifications($enrollment);

                    $user = User::find($userId);
                    if (!$user) {

                        throw new \Exception('User not found.');
                    }

                    if (!Auth::check() || Auth::id() !== $userId) {
                        Auth::login($user, true);
                    }

                    ToastMagic::success('Payment successful! You have been enrolled in the course.');
                    return redirect()->route('enrollment.success', $enrollment->id);
                } catch (\Exception $e) {
                    DB::rollBack();

                    if ($enrollment && $enrollment->payment) {
                        DB::beginTransaction();
                        $enrollment->payment->update([
                            'status' => Payment::STATUS_FAILED,
                        ]);
                        DB::commit();
                    }

                    ToastMagic::error('Payment validation failed. Please contact support.');
                    return redirect()->route('courses.show', $courseId);
                }
            } else {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $courseId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Create Mollie payment
     */
    private function createMolliePayment(float $amount, $course, $user): array
    {
        $courseIdToUse = $course->id;
        $apiKey = $this->credentials('mollie')['api_key'] ?? null;

        if (!$apiKey) {
            throw new \RuntimeException('Mollie API key is not configured.');
        }

        $baseUrl = 'https://api.mollie.com/v2/payments';
        $amountValue = number_format($amount, 2, '.', '');

        // Mollie requires currency. Default to EUR if not set.
        $currency = strtoupper($this->settingsRepository->get('platform.general.default_currency', 'EUR'));

        // If currency is not USD, EUR, or GBP, fallback to EUR
        if (!in_array($currency, ['USD', 'EUR', 'GBP'])) {
            $currency = 'EUR';
        }

        $paymentData = [
            'amount' => [
                'currency' => $currency,
                'value' => $amountValue,
            ],
            'description' => 'Course Enrollment: ' . $course->title,
            'redirectUrl' => route('payment.mollie.callback'),
            'metadata' => [
                'course_id' => $courseIdToUse,
                'user_id' => $user->id,
                'course_title' => $course->title,
            ],
        ];

        $ch = curl_init($baseUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('Mollie cURL error: ' . $curlError);
        }

        if ($httpCode !== 201) {
            throw new \Exception('Failed to create Mollie payment. HTTP Code: ' . $httpCode);
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from Mollie API.');
        }

        if (!isset($responseData['id']) || !isset($responseData['_links']['checkout']['href'])) {
            $errorMsg = $responseData['detail'] ?? $responseData['title'] ?? 'Unknown error';
            throw new \Exception('Mollie error: ' . $errorMsg);
        }

        return [
            'payment_id' => $responseData['id'],
            'checkout_url' => $responseData['_links']['checkout']['href'],
        ];
    }

    /**
     * Handle Mollie payment callback
     */
    public function mollieCallback(Request $request)
    {
        $paymentId = $request->query('id')
            ?? $request->input('id')
            ?? session('mollie_payment_id');

        if (!$paymentId) {
            $userId = Auth::id();
            if ($userId) {
                $enrollment = Enrollment::with('payment')
                    ->where('user_id', $userId)
                    ->whereHas('payment', function ($query) {
                        $query->where('payment_method', Payment::PAYMENT_METHOD_MOLLIE)
                            ->where('status', Payment::STATUS_PENDING);
                    })
                    ->latest()
                    ->first();

                if ($enrollment && $enrollment->payment && $enrollment->payment->mollie_payment_id) {
                    $paymentId = $enrollment->payment->mollie_payment_id;
                }
            }
        }

        if (!$paymentId) {
            ToastMagic::error('Invalid payment callback.');
            return redirect()->route('courses');
        }

        session()->forget('mollie_payment_id');

        try {
            $apiKey = $this->credentials('mollie')['api_key'] ?? null;
            if (!$apiKey) {
                ToastMagic::error('Mollie configuration error.');
                return redirect()->route('courses');
            }

            $payment = Payment::where('mollie_payment_id', $paymentId)->first();
            $enrollment = $payment ? $payment->enrollment : null;

            if (!$payment) {
                ToastMagic::error('Payment record not found.');
                return redirect()->route('courses');
            }

            $baseUrl = 'https://api.mollie.com/v2/payments/' . $paymentId;

            $ch = curl_init($baseUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Exception('Mollie verification cURL error: ' . $curlError);
            }

            if ($httpCode !== 200) {
                throw new \Exception('Failed to verify Mollie payment. HTTP Code: ' . $httpCode);
            }

            $paymentData = json_decode($response, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON response from Mollie API.');
            }

            DB::beginTransaction();

            if ($paymentData['status'] === 'paid') {
                $paidAmount = (float) ($paymentData['amount']['value'] ?? 0);
                if (abs($paidAmount - $payment->amount) > 0.01) {
                    throw new \Exception('Amount mismatch.');
                }

                if ($payment->subscription_plan_id) {
                    $payment->update(['status' => Payment::STATUS_COMPLETED]);
                    $user = User::find($payment->user_id);
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    if ($user && (!Auth::check() || Auth::id() !== $user->id)) {
                        Auth::login($user, true);
                    }
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                    );
                    DB::commit();
                    ToastMagic::success('Payment successful! Your subscription to "' . ($plan->name ?? 'Plan') . '" is now active.');
                    return redirect()->route('student.subscription');
                }

                if (!$enrollment) {
                    throw new \Exception('Enrollment record not found.');
                }

                $payment->update([
                    'status' => Payment::STATUS_COMPLETED,
                ]);

                $enrollment->update([
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment);

                $userId = $enrollment->user_id;
                $user = User::find($userId);
                if (!$user) {
                    throw new \Exception('User not found.');
                }

                if (!Auth::check() || Auth::id() !== $userId) {
                    Auth::login($user, true);
                }

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } elseif ($paymentData['status'] === 'failed' || $paymentData['status'] === 'canceled' || $paymentData['status'] === 'expired') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                ToastMagic::error('Payment was not successful. Please try again.');
                return redirect()->route('courses.show', $enrollment->course_id);
            } else {
                DB::rollBack();
                ToastMagic::error('Payment is still pending.');
                return redirect()->route('courses.show', $enrollment->course_id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    private function credentials(string $identifier): array
    {
        return $this->paymentGatewayService->credentialsFor($identifier);
    }

    // ─────────────────────────────────────────────────────────────
    //  XPay helpers
    // ─────────────────────────────────────────────────────────────

    /**
     * Create an XPay checkout session and return order_id + checkout_url.
     *
     * XPay API base URLs:
     *   Sandbox : https://sandbox.xpay.com.pk/api/v1
     *   Live    : https://api.xpay.com.pk/api/v1
     */
    private function createXpaySession(float $amount, $course, $user, ?int $courseIdOverride = null): array
    {
        $courseIdToUse = $courseIdOverride ?? $course->id;
        $credentials   = $this->credentials('xpay');
        $storeId       = $credentials['store_id'] ?? null;
        $apiKey        = $credentials['api_key']  ?? null;
        $mode          = $credentials['mode']      ?? 'sandbox';

        if (!$storeId || !$apiKey) {
            throw new \RuntimeException('XPay credentials (store_id / api_key) are not configured.');
        }

        $baseUrl = $mode === 'live'
            ? 'https://api.xpay.com.pk/api/v1'
            : 'https://sandbox.xpay.com.pk/api/v1';

        $orderId = 'XPAY_' . $courseIdToUse . '_' . $user->id . '_' . time();

        $payload = [
            'store_id'    => $storeId,
            'order_id'    => $orderId,
            'amount'      => number_format($amount, 2, '.', ''),
            'currency'    => 'PKR',
            'description' => 'Course Enrollment: ' . $course->title,
            'customer'    => [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
            ],
            'callback_url' => route('payment.xpay.callback'),
            'metadata'    => [
                'course_id' => $courseIdToUse,
                'user_id'   => $user->id,
            ],
        ];

        $ch = curl_init($baseUrl . '/checkout/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('XPay create session cURL error: ' . $curlError);
        }

        if ($httpCode !== 200 && $httpCode !== 201) {
            throw new \Exception('Failed to create XPay checkout session. HTTP ' . $httpCode . '. Response: ' . substr($response, 0, 300));
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from XPay API.');
        }

        // XPay returns { success: true, data: { checkout_url: '...', order_id: '...' } }
        // Adjust field names if XPay's actual response differs.
        $checkoutUrl = $responseData['data']['checkout_url']
            ?? $responseData['checkout_url']
            ?? $responseData['url']
            ?? null;

        $returnedOrderId = $responseData['data']['order_id']
            ?? $responseData['order_id']
            ?? $orderId;

        if (!$checkoutUrl) {
            $errorMsg = $responseData['message'] ?? $responseData['error'] ?? 'Unknown error';
            throw new \Exception('XPay session creation error: ' . $errorMsg);
        }

        return [
            'order_id'     => $returnedOrderId,
            'checkout_url' => $checkoutUrl,
        ];
    }

    /**
     * Handle XPay payment callback (redirect from XPay checkout page).
     *
     * XPay will redirect to this URL with query params including:
     *   order_id, status, transaction_id
     */
    public function xpayCallback(\Illuminate\Http\Request $request)
    {
        $orderId       = $request->query('order_id') ?? $request->input('order_id');
        $status        = strtolower($request->query('status') ?? $request->input('status') ?? '');
        $transactionId = $request->query('transaction_id') ?? $request->input('transaction_id');

        if (!$orderId) {
            ToastMagic::error('Invalid XPay callback — missing order_id.');
            return redirect()->route('courses');
        }

        if ($status === 'cancel' || $status === 'cancelled') {
            ToastMagic::error('XPay payment was cancelled.');
            return redirect()->route('courses');
        }

        if ($status === 'failed' || $status === 'failure') {
            ToastMagic::error('XPay payment failed. Please try again.');
            return redirect()->route('courses');
        }

        $payment = Payment::where('xpay_order_id', $orderId)->first();

        if (!$payment) {
            ToastMagic::error('XPay payment record not found.');
            return redirect()->route('courses');
        }

        $enrollment = $payment->enrollment;
        $courseId   = $enrollment?->course_id;
        $userId     = $payment->user_id;

        // Verify payment with XPay API
        DB::beginTransaction();

        try {
            $credentials = $this->credentials('xpay');
            $apiKey      = $credentials['api_key'] ?? null;
            $mode        = $credentials['mode']     ?? 'sandbox';

            $baseUrl = $mode === 'live'
                ? 'https://api.xpay.com.pk/api/v1'
                : 'https://sandbox.xpay.com.pk/api/v1';

            $ch = curl_init($baseUrl . '/checkout/verify/' . $orderId);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response  = curl_exec($ch);
            $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                throw new \Exception('XPay verify cURL error: ' . $curlError);
            }

            $verifyData = json_decode($response, true) ?? [];

            $verifiedStatus = strtolower(
                $verifyData['data']['status']
                ?? $verifyData['status']
                ?? $status
            );

            $verifiedTxId = $verifyData['data']['transaction_id']
                ?? $verifyData['transaction_id']
                ?? $transactionId;

            if ($verifiedStatus === 'success' || $verifiedStatus === 'paid' || $verifiedStatus === 'completed') {
                $payment->update([
                    'xpay_transaction_id' => $verifiedTxId,
                    'status'              => Payment::STATUS_COMPLETED,
                ]);

                DB::commit();

                $user = User::find($userId);
                if ($user && (!Auth::check() || Auth::id() !== $userId)) {
                    Auth::login($user, true);
                }

                if ($payment->subscription_plan_id) {
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                    );
                    ToastMagic::success('Payment successful! Your subscription to "' . ($plan->name ?? 'Plan') . '" is now active.');
                    return redirect()->route('student.subscription');
                }

                if (!$enrollment) {
                    ToastMagic::error('Enrollment not found.');
                    return redirect()->route('courses');
                }

                $enrollment->update([
                    'status'      => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                $this->sendEnrollmentNotifications($enrollment);

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $payment->update(['status' => Payment::STATUS_FAILED]);

                DB::commit();

                ToastMagic::error('XPay payment was not completed. Status: ' . $verifiedStatus);
                return redirect()->route('courses.show', $courseId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your XPay payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Chapa Gateway Methods
    // ─────────────────────────────────────────────────────────────

    /**
     * Create a Chapa checkout session and return tx_ref + checkout_url.
     * Chapa API base URL: https://api.chapa.co/v1
     */
    private function createChapaSession(float $amount, $course, $user, ?int $courseIdOverride = null): array
    {
        $courseIdToUse = $courseIdOverride ?? $course->id;
        $credentials   = $this->credentials('chapa');
        $secretKey     = $credentials['secret_key'] ?? null;

        if (!$secretKey) {
            throw new \RuntimeException('Chapa credentials (secret_key) are not configured.');
        }

        $txRef = 'CHAPA_' . $courseIdToUse . '_' . $user->id . '_' . time();

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
                'description' => 'Course Enrollment: ' . ($course->title ?? 'Course'),
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
            throw new \Exception('Failed to create Chapa checkout session. HTTP ' . $httpCode . '. Response: ' . substr($response, 0, 300));
        }

        $responseData = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON response from Chapa API.');
        }

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

    /**
     * Handle Chapa payment callback.
     */
    public function chapaCallback(\Illuminate\Http\Request $request)
    {
        $txRef  = $request->query('tx_ref') ?? $request->input('tx_ref');
        $status = strtolower($request->query('status') ?? $request->input('status') ?? '');

        if (!$txRef) {
            ToastMagic::error('Invalid Chapa callback — missing tx_ref.');
            return redirect()->route('courses');
        }

        if ($status === 'cancel' || $status === 'cancelled') {
            ToastMagic::error('Chapa payment was cancelled.');
            return redirect()->route('courses');
        }

        if ($status === 'failed' || $status === 'failure') {
            ToastMagic::error('Chapa payment failed. Please try again.');
            return redirect()->route('courses');
        }

        $payment = Payment::where('chapa_tx_ref', $txRef)->first();

        if (!$payment) {
            ToastMagic::error('Chapa payment record not found.');
            return redirect()->route('courses');
        }

        $enrollment = $payment->enrollment;
        $courseId   = $enrollment?->course_id;
        $userId     = $payment->user_id;

        // Idempotency: Handle duplicate callback if already completed
        if ($payment->status === Payment::STATUS_COMPLETED) {
            $user = User::find($userId);
            if ($user && (!Auth::check() || Auth::id() !== $userId)) {
                Auth::login($user, true);
            }
            ToastMagic::info('Payment already completed.');
            if ($payment->subscription_plan_id) {
                return redirect()->route('student.subscription');
            }
            if ($enrollment) {
                return redirect()->route('enrollment.success', $enrollment->id);
            }
            return redirect()->route('courses');
        }

        DB::beginTransaction();

        try {
            $credentials = $this->credentials('chapa');
            $secretKey   = $credentials['secret_key'] ?? null;

            $ch = curl_init('https://api.chapa.co/v1/transaction/verify/' . $txRef);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
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
                throw new \Exception('Chapa verify cURL error: ' . $curlError);
            }

            $verifyData = json_decode($response, true) ?? [];

            $verifiedStatus = strtolower(
                $verifyData['data']['status']
                ?? $verifyData['status']
                ?? ''
            );

            $reference = $verifyData['data']['reference'] ?? $verifyData['reference'] ?? null;

            if ($verifiedStatus === 'success' || $status === 'success') {
                $payment->update([
                    'chapa_reference' => $reference,
                    'status'          => Payment::STATUS_COMPLETED,
                ]);

                DB::commit();

                $user = User::find($userId);
                if ($user && (!Auth::check() || Auth::id() !== $userId)) {
                    Auth::login($user, true);
                }

                if ($payment->subscription_plan_id) {
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                    );
                    ToastMagic::success('Payment successful! Your subscription to "' . ($plan->name ?? 'Plan') . '" is now active.');
                    return redirect()->route('student.subscription');
                }

                if (!$enrollment) {
                    ToastMagic::error('Enrollment not found.');
                    return redirect()->route('courses');
                }

                $enrollment->update([
                    'status'      => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                $this->sendEnrollmentNotifications($enrollment);

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $payment->update(['status' => Payment::STATUS_FAILED]);

                DB::commit();

                ToastMagic::error('Chapa payment was not completed. Status: ' . $verifiedStatus);
                return redirect()->route('courses.show', $courseId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your Chapa payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    /**
     * Handle Chapa server-to-server Webhook notification.
     */
    public function chapaWebhook(\Illuminate\Http\Request $request)
    {
        $rawContent = $request->getContent();
        $signature = $request->header('Chapa-Signature') ?? $request->header('x-chapa-signature');

        $credentials = $this->credentials('chapa');
        $secretKey = $credentials['secret_key'] ?? null;
        $webhookSecret = $credentials['webhook_secret'] ?? $secretKey;

        // Verify signature if webhook secret is configured
        if (!empty($webhookSecret) && !empty($signature)) {
            $expectedSignature = hash_hmac('sha256', $rawContent, $webhookSecret);
            if (!hash_equals($expectedSignature, $signature)) {
                \Illuminate\Support\Facades\Log::warning('Chapa webhook signature mismatch.', [
                    'received' => $signature,
                ]);
                return response()->json(['success' => false, 'message' => 'Invalid signature.'], 403);
            }
        }

        $payload = json_decode($rawContent, true) ?? $request->all();
        $txRef = $payload['tx_ref'] ?? $payload['trx_ref'] ?? null;

        if (!$txRef) {
            return response()->json(['success' => false, 'message' => 'Missing transaction reference.'], 400);
        }

        $payment = Payment::where('chapa_tx_ref', $txRef)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment record not found.'], 404);
        }

        // Idempotency: already processed
        if ($payment->status === Payment::STATUS_COMPLETED) {
            return response()->json(['success' => true, 'message' => 'Payment already completed.'], 200);
        }

        $status = strtolower($payload['status'] ?? '');
        $reference = $payload['reference'] ?? null;

        // Verify transaction server-side if not already verified
        if ($status !== 'success' && !empty($secretKey)) {
            try {
                $ch = curl_init('https://api.chapa.co/v1/transaction/verify/' . $txRef);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $secretKey]);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                $resp = curl_exec($ch);
                curl_close($ch);
                $verifyData = json_decode($resp, true) ?? [];
                $vStatus = strtolower($verifyData['data']['status'] ?? $verifyData['status'] ?? '');
                if ($vStatus === 'success') {
                    $status = 'success';
                    $reference = $verifyData['data']['reference'] ?? $reference;
                }
            } catch (\Exception $e) {
                // fall through
            }
        }

        if ($status === 'success') {
            DB::beginTransaction();
            try {
                $payment->update([
                    'chapa_reference' => $reference,
                    'status'          => Payment::STATUS_COMPLETED,
                ]);

                if ($payment->subscription_plan_id && $payment->user) {
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    if ($plan) {
                        app(\App\Services\SubscriptionService::class)->activateSubscription(
                            $payment->user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                        );
                    }
                } elseif ($payment->enrollment) {
                    $payment->enrollment->update([
                        'status'      => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);
                    $this->processRevenueShare($payment->enrollment);
                    $this->sendEnrollmentNotifications($payment->enrollment);
                }

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Payment settled and enrollment approved.'], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                \Illuminate\Support\Facades\Log::error('Chapa webhook settlement error: ' . $e->getMessage());
                return response()->json(['success' => false, 'message' => 'Processing error.'], 500);
            }
        } elseif (in_array($status, ['failed', 'failure', 'cancelled', 'cancel'])) {
            $payment->update(['status' => Payment::STATUS_FAILED]);
            return response()->json(['success' => true, 'message' => 'Payment marked failed.'], 200);
        }

        return response()->json(['success' => true, 'message' => 'Webhook received.'], 200);
    }
    // ─────────────────────────────────────────────────────────────


    // ─────────────────────────────────────────────────────────────

    /**
     * Obtain a short-lived bKash token.
     */
    private function getBkashToken(): array
    {
        $credentials = $this->credentials('bkash');
        $appKey      = $credentials['app_key']    ?? null;
        $appSecret   = $credentials['app_secret'] ?? null;
        $username    = $credentials['username']   ?? null;
        $password    = $credentials['password']   ?? null;
        $mode        = $credentials['mode']       ?? 'sandbox';

        if (!$appKey || !$appSecret || !$username || !$password) {
            throw new \RuntimeException('bKash credentials are not configured.');
        }

        $baseUrl = $mode === 'live'
            ? 'https://tokenized.pay.bka.sh/v1.2.0-beta/tokenized/checkout'
            : 'https://tokenized.sandbox.bka.sh/v1.2.0-beta/tokenized/checkout';

        $ch = curl_init($baseUrl . '/token/grant');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'app_key'    => $appKey,
            'app_secret' => $appSecret,
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'username: ' . $username,
            'password: ' . $password,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('bKash token cURL error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new \Exception('Failed to obtain bKash token. HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);

        if (!isset($data['id_token'])) {
            throw new \Exception('Invalid bKash token response: ' . substr($response, 0, 200));
        }

        $data['base_url'] = $baseUrl;
        $data['app_key']  = $appKey;

        return $data;
    }

    /**
     * Create a bKash payment and return paymentID + bkashURL.
     */
    private function createBkashPayment(float $amount, $course, $user, ?int $courseIdOverride = null): array
    {
        $courseIdToUse = $courseIdOverride ?? $course->id;
        $tokenData     = $this->getBkashToken();
        $idToken       = $tokenData['id_token'];
        $baseUrl       = $tokenData['base_url'];
        $appKey        = $tokenData['app_key'];

        $invoiceNumber = 'BKASH_' . $courseIdToUse . '_' . $user->id . '_' . time();

        $paymentData = [
            'mode'            => '0011',
            'payerReference'  => (string) $user->id,
            'callbackURL'     => route('payment.bkash.callback'),
            'amount'          => number_format($amount, 2, '.', ''),
            'currency'        => 'BDT',
            'intent'          => 'sale',
            'merchantInvoiceNumber' => $invoiceNumber,
        ];

        $ch = curl_init($baseUrl . '/create');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paymentData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $idToken,
            'X-APP-Key: ' . $appKey,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('bKash create payment cURL error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new \Exception('Failed to create bKash payment. HTTP ' . $httpCode);
        }

        $responseData = json_decode($response, true);

        if (!isset($responseData['paymentID']) || !isset($responseData['bkashURL'])) {
            $errorMsg = $responseData['statusMessage'] ?? $responseData['errorMessage'] ?? 'Unknown error';
            throw new \Exception('bKash payment creation error: ' . $errorMsg);
        }

        return [
            'paymentID' => $responseData['paymentID'],
            'bkashURL'  => $responseData['bkashURL'],
        ];
    }

    /**
     * Execute a bKash payment after user approval.
     */
    private function executeBkashPayment(string $paymentId): array
    {
        $tokenData = $this->getBkashToken();
        $idToken   = $tokenData['id_token'];
        $baseUrl   = $tokenData['base_url'];
        $appKey    = $tokenData['app_key'];

        $ch = curl_init($baseUrl . '/execute');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['paymentID' => $paymentId]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $idToken,
            'X-APP-Key: ' . $appKey,
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new \Exception('bKash execute payment cURL error: ' . $curlError);
        }

        if ($httpCode !== 200) {
            throw new \Exception('Failed to execute bKash payment. HTTP ' . $httpCode);
        }

        return json_decode($response, true) ?? [];
    }

    /**
     * Handle bKash payment callback (redirect from bKash checkout).
     */
    public function bkashCallback(\Illuminate\Http\Request $request)
    {
        $paymentId = $request->query('paymentID');
        $status    = $request->query('status');

        if (!$paymentId) {
            ToastMagic::error('Invalid bKash callback — missing paymentID.');
            return redirect()->route('courses');
        }

        if ($status === 'cancel') {
            ToastMagic::error('bKash payment was cancelled.');
            return redirect()->route('courses');
        }

        if ($status === 'failure') {
            ToastMagic::error('bKash payment failed. Please try again.');
            return redirect()->route('courses');
        }

        $payment = Payment::where('bkash_payment_id', $paymentId)->first();

        if (!$payment) {
            ToastMagic::error('bKash payment record not found.');
            return redirect()->route('courses');
        }

        $enrollment = $payment->enrollment;
        $courseId   = $enrollment?->course_id;
        $userId     = $payment->user_id;

        DB::beginTransaction();

        try {
            $executeData = $this->executeBkashPayment($paymentId);

            $transactionStatus = $executeData['transactionStatus'] ?? $executeData['statusMessage'] ?? '';
            $trxId             = $executeData['trxID'] ?? null;

            if (strtolower($transactionStatus) === 'completed') {
                $payment->update([
                    'bkash_trx_id' => $trxId,
                    'status'       => Payment::STATUS_COMPLETED,
                ]);

                DB::commit();

                $user = User::find($userId);
                if ($user && (!Auth::check() || Auth::id() !== $userId)) {
                    Auth::login($user, true);
                }

                if ($payment->subscription_plan_id) {
                    $plan = \App\Models\SubscriptionPlan::find($payment->subscription_plan_id);
                    app(\App\Services\SubscriptionService::class)->activateSubscription(
                        $user, $plan, $payment, \App\Models\UserSubscription::STATUS_ACTIVE
                    );
                    ToastMagic::success('Payment successful! Your subscription to "' . ($plan->name ?? 'Plan') . '" is now active.');
                    return redirect()->route('student.subscription');
                }

                if (!$enrollment) {
                    ToastMagic::error('Enrollment not found.');
                    return redirect()->route('courses');
                }

                $enrollment->update([
                    'status'      => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                $this->sendEnrollmentNotifications($enrollment);

                ToastMagic::success('Payment successful! You have been enrolled in the course.');
                return redirect()->route('enrollment.success', $enrollment->id);
            } else {
                $payment->update(['status' => Payment::STATUS_FAILED]);

                DB::commit();

                ToastMagic::error('bKash payment was not completed. Status: ' . $transactionStatus);
                return redirect()->route('subscriptions.index');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            ToastMagic::error('An error occurred processing your bKash payment. Please contact support.');
            return redirect()->route('courses');
        }
    }

    private function sendEnrollmentNotifications(Enrollment $enrollment, ?Course $course = null, ?User $student = null): void
    {
        try {
            $enrollment->loadMissing('course.instructor.notificationSettings', 'user.notificationSettings');

            $course = $course ?? $enrollment->course;
            $student = $student ?? $enrollment->user;
            $instructor = $course?->instructor;
            $enrolledAt = $enrollment->enrolled_at ?? now();

            if ($student && $student->email && $this->notificationPreferenceService->prefers($student, 'email_notifications')) {
                Mail::to($student->email)->send(new StudentCourseEnrollmentMail($course, $student, $enrolledAt));
            }

            if ($instructor && $instructor->email && $student && $this->notificationPreferenceService->prefers($instructor, 'course_updates')) {
                Mail::to($instructor->email)->send(new InstructorCourseEnrollmentMail($course, $student, $enrolledAt));
            }

            if ($student && !empty($student->phone)) {
                try {
                    app(\App\Services\SmsEthiopiaService::class)->sendEnrollmentNotification($enrollment);
                } catch (\Exception $smsEx) {
                    \Log::error('Enrollment SMS failed: ' . $smsEx->getMessage());
                }
            }
        } catch (\Exception $e) {
            \Log::error('Enrollment email failed: ' . $e->getMessage());
        }
    }

    private function processRevenueShare(Enrollment $enrollment): void
    {
        if (!$enrollment->payment_id) {
            return;
        }

        $payment = Payment::with('enrollment.course.instructor')->find($enrollment->payment_id);

        if (!$payment || $payment->status !== Payment::STATUS_COMPLETED) {
            return;
        }

        $this->revenueShareService->process($payment);
    }

    /**
     * Validate a coupon code for checkout (Web).
     */
    public function validateCoupon(Request $request)
    {
        try {
            $code = $request->input('code');
            $courseId = $request->input('course_id');
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please log in to apply a coupon.',
                ], 401);
            }

            $course = Course::findOrFail($courseId);
            $price = $this->calculatePrice($course);

            $coupon = $this->couponService->validate($code, $course, $user);
            $discount = $this->couponService->calculateDiscount($coupon, $price);
            $finalPrice = max(0, $price - $discount);

            return response()->json([
                'success' => true,
                'coupon' => [
                    'id' => $coupon->id,
                    'code' => $coupon->code,
                    'type' => $coupon->type,
                    'value' => (float) $coupon->value,
                ],
                'discount_amount' => $discount,
                'discount_amount_formatted' => currency_format($discount),
                'final_price' => $finalPrice,
                'final_price_formatted' => $finalPrice > 0 ? currency_format($finalPrice) : __('frontend.free'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Invalid coupon code.',
            ], 400);
        }
    }
}
