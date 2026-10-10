<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\SettingsRepository;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Services\FileUploadService;
use App\Services\PaymentGatewayService;
use App\Services\NotificationPreferenceService;
use App\Services\RevenueShareService;
use App\Services\CouponService;
use App\Models\CouponUsage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Yabacon\Paystack;
use App\Mail\StudentCourseEnrollmentMail;
use App\Mail\InstructorCourseEnrollmentMail;
use Illuminate\Support\Facades\Mail;

class CourseEnrollmentController extends Controller
{
    protected $fileUploadService;
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(
        FileUploadService $fileUploadService,
        PaymentGatewayService $paymentGatewayService,
        protected SettingsRepository $settingsRepository,
        private NotificationPreferenceService $notificationPreferenceService,
        private RevenueShareService $revenueShareService,
        private CouponService $couponService
    ) {
        $this->fileUploadService = $fileUploadService;
        $this->paymentGatewayService = $paymentGatewayService;
        $this->settingsRepository = $settingsRepository;
    }

    /**
     * Process checkout
     */
    public function enrollBundle(CheckoutRequest $request, $id)
    {
        $request->merge(['type' => 'bundle']);
        return $this->enroll($request, $id);
    }

    public function enroll(CheckoutRequest $request, $id)
    {
        $user = Auth::user();
        $isBundle = $request->input('type') === 'bundle';
        
        if ($isBundle) {
            $course = \App\Models\Bundle::with('courses')->findOrFail($id);
            if ($course->status !== 'active' || $course->approval_status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'This bundle is not available.'
                ], 400);
            }

            $ownedCourseIds = Enrollment::where('user_id', $user->id)
                ->whereIn('status', ['approved', 'completed'])
                ->pluck('course_id')
                ->toArray();

            if (!$request->input('confirm_duplicate')) {
                $bundleCourseIds = $course->courses->pluck('id')->toArray();
                if (count(array_intersect($ownedCourseIds, $bundleCourseIds)) > 0) {
                    return response()->json([
                        'success' => false,
                        'is_duplicate_warning' => true,
                        'message' => 'You already own some courses in this bundle. Proceeding will enroll you in the remaining courses and charge the full bundle price.'
                    ], 400);
                }
            }

            $unownedCourses = $course->courses->whereNotIn('id', $ownedCourseIds);
            if ($unownedCourses->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'You already own all courses in this bundle.'], 400);
            }

            $price = (float) $course->price;
            $courseIdToUse = $unownedCourses->first()->id;
        } else {
            $course = Course::findOrFail($id);
            if ($course->status !== 'published' || $course->visibility !== 'public') {
                return response()->json([
                    'success' => false,
                    'message' => 'This course is not available for enrollment.'
                ], 400);
            }

            $existingEnrollment = Enrollment::where('user_id', $user->id)
                ->where('course_id', $course->id)
                ->first();

            if ($existingEnrollment) {
                if (in_array($existingEnrollment->status, [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])) {
                    return response()->json(['success' => false, 'message' => 'You are already enrolled.'], 400);
                }
                $payment = $existingEnrollment->payment;
                if ($payment && $payment->payment_method === Payment::PAYMENT_METHOD_OFFLINE) {
                    return response()->json(['success' => false, 'message' => 'Pending offline payment request exists.'], 400);
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
                return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
            }
        }

        if ($price > 0 && !$this->paymentGatewayService->isEnabled($paymentMethod)) {
            return response()->json([
                'success' => false,
                'message' => 'Selected payment method is not available.'
            ], 400);
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
                    'currency' => $razorpayOrder['currency'],
                    'key' => $this->credentials('razorpay')['key'] ?? '',
                    'name' => config('app.name'),
                    'description' => 'Course Enrollment: ' . $course->title,
                    'prefill' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'contact' => $user->phone ?? '',
                    ],
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_SSLCOMMERZ) {
                $credentials = $this->credentials('sslcommerz');
                $tranId = 'SSLCZ_' . uniqid() . '_' . $user->id;

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
                    'sslcommerz_tran_id' => $tranId,
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'tran_id' => $tranId,
                    'amount' => $price,
                    'store_id' => $credentials['store_id'] ?? '',
                    'store_password' => $credentials['store_password'] ?? '',
                    'mode' => $credentials['mode'] ?? 'sandbox',
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'prefill' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'contact' => $user->phone ?? '',
                    ],
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_STRIPE) {
                $credentials = $this->credentials('stripe');
                $stripeSecret = $credentials['secret'] ?? '';

                if (empty($stripeSecret)) {
                    throw new \Exception('Stripe credentials are not configured.');
                }

                Stripe::setApiKey($stripeSecret);

                $paymentIntent = PaymentIntent::create([
                    'amount' => (int) ($price * 100), // convert to cents
                    'currency' => strtolower($this->settingsRepository->get('platform.general.default_currency', 'INR')),
                    'payment_method_types' => ['card'],
                    'metadata' => [
                        'course_id' => $courseIdToUse,
                        'user_id' => $user->id,
                    ],
                ]);

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
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'stripe_client_secret' => $paymentIntent->client_secret,
                    'coupon_id' => $appliedCoupon?->id,
                    'discount_amount' => $discountAmount,
                ]);

                $enrollment->update(['payment_id' => $payment->id]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'payment_intent_id' => $paymentIntent->id,
                    'client_secret' => $paymentIntent->client_secret,
                    'publishable_key' => $credentials['key'] ?? '',
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                    'amount' => $price,
                ]);
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_PAYSTACK) {
                $credentials = $this->credentials('paystack');
                $paystackSecret = $credentials['secret_key'] ?? '';
                $paystackPublic = $credentials['public_key'] ?? '';

                if (empty($paystackSecret)) {
                    throw new \Exception('Paystack credentials are not configured.');
                }

                $paystack = new Paystack($paystackSecret);

                // Generate unique reference
                $reference = 'PAY_' . time() . '_' . $user->id;
                $amountInKobo = (int) ($price * 100);

                try {
                    $transaction = $paystack->transaction->initialize([
                        'email' => $user->email,
                        'amount' => $amountInKobo,
                        'reference' => $reference,
                        'callback_url' => config('app.url') . '/payment/paystack/callback',
                        'metadata' => [
                            'course_id' => $courseIdToUse,
                            'user_id' => $user->id,
                        ],
                    ]);

                    if (!$transaction->status || !$transaction->data) {
                        throw new \Exception('Failed to initialize Paystack transaction.');
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
                        'payment_method' => Payment::PAYMENT_METHOD_PAYSTACK,
                        'amount' => $price,
                        'status' => Payment::STATUS_PENDING,
                        'paystack_reference' => $reference,
                        'paystack_access_code' => $transaction->data->access_code,
                        'coupon_id' => $appliedCoupon?->id,
                        'discount_amount' => $discountAmount,
                    ]);

                    $enrollment->update(['payment_id' => $payment->id]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'authorization_url' => $transaction->data->authorization_url,
                        'access_code' => $transaction->data->access_code,
                        'reference' => $reference,
                        'public_key' => $paystackPublic,
                        'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                        'amount' => $price,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create Paystack transaction: ' . $e->getMessage());
                }
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_MOLLIE) {
                try {
                    $mollieData = $this->createMolliePayment($price, $course, $user);

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
                        'mollie_payment_id' => $mollieData['payment_id'],
                        'coupon_id' => $appliedCoupon?->id,
                        'discount_amount' => $discountAmount,
                    ]);

                    $enrollment->update(['payment_id' => $payment->id]);

                    DB::commit();

                    return response()->json([
                        'success' => true,
                        'checkout_url' => $mollieData['checkout_url'],
                        'payment_id' => $mollieData['payment_id'],
                        'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                        'amount' => $price,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create Mollie payment: ' . $e->getMessage());
                }
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_BKASH) {
                try {
                    $bkashData = $this->createBkashPayment($price, $course, $user, $courseIdToUse);

                    $enrollment = Enrollment::firstOrCreate([
                        'user_id'   => $user->id,
                        'course_id' => $courseIdToUse,
                    ], [
                        'status' => Enrollment::STATUS_PENDING,
                    ]);

                    $payment = Payment::create([
                        'enrollment_id'    => $enrollment->id,
                        'bundle_id'        => $isBundle ? $course->id : null,
                        'payment_method'   => Payment::PAYMENT_METHOD_BKASH,
                        'amount'           => $price,
                        'status'           => Payment::STATUS_PENDING,
                        'bkash_payment_id' => $bkashData['paymentID'],
                        'coupon_id'        => $appliedCoupon?->id,
                        'discount_amount'  => $discountAmount,
                    ]);

                    $enrollment->update(['payment_id' => $payment->id]);

                    DB::commit();

                    return response()->json([
                        'success'       => true,
                        'checkout_url'  => $bkashData['bkashURL'],
                        'payment_id'    => $bkashData['paymentID'],
                        'enrollment_id' => $enrollment->id,
                        'bundle_id'     => $isBundle ? $course->id : null,
                        'amount'        => $price,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create bKash payment: ' . $e->getMessage());
                }
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_XPAY) {
                try {
                    $xpayData = $this->createXpaySession($price, $course, $user, $courseIdToUse);

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
                        'xpay_order_id'  => $xpayData['order_id'],
                        'coupon_id'      => $appliedCoupon?->id,
                        'discount_amount' => $discountAmount,
                    ]);

                    $enrollment->update(['payment_id' => $payment->id]);

                    DB::commit();

                    return response()->json([
                        'success'       => true,
                        'checkout_url'  => $xpayData['checkout_url'],
                        'order_id'      => $xpayData['order_id'],
                        'enrollment_id' => $enrollment->id,
                        'bundle_id'     => $isBundle ? $course->id : null,
                        'amount'        => $price,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create XPay session: ' . $e->getMessage());
                }
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_CHAPA) {
                try {
                    $chapaData = $this->createChapaSession($price, $course, $user, $courseIdToUse);

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
                        'chapa_tx_ref'   => $chapaData['tx_ref'],
                        'coupon_id'      => $appliedCoupon?->id,
                        'discount_amount' => $discountAmount,
                    ]);

                    $enrollment->update(['payment_id' => $payment->id]);

                    DB::commit();

                    return response()->json([
                        'success'       => true,
                        'checkout_url'  => $chapaData['checkout_url'],
                        'tx_ref'        => $chapaData['tx_ref'],
                        'enrollment_id' => $enrollment->id,
                        'bundle_id'     => $isBundle ? $course->id : null,
                        'amount'        => $price,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create Chapa session: ' . $e->getMessage());
                }
            } elseif ($price > 0 && $paymentMethod === Payment::PAYMENT_METHOD_PAYPAL) {
                try {
                    $paypalData = $this->createPaypalOrder($price, $course, $user);

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
                        'paypal_order_id' => $paypalData['order_id'],
                        'coupon_id' => $appliedCoupon?->id,
                        'discount_amount' => $discountAmount,
                    ]);

                    $enrollment->update(['payment_id' => $payment->id]);

                    DB::commit();

                    $credentials = $this->paymentGatewayService->credentialsFor(Payment::PAYMENT_METHOD_PAYPAL);
                    return response()->json([
                        'success' => true,
                        'order_id' => $paypalData['order_id'],
                        'approve_url' => $paypalData['approve_url'],
                        'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                        'amount' => $price,
                    ]);
                } catch (\Exception $e) {
                    throw new \Exception('Failed to create PayPal order: ' . $e->getMessage());
                }
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

                return response()->json([
                    'success' => true,
                    'message' => 'Your enrollment request has been submitted. Please wait for admin approval.',
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                ]);
            } elseif ($price == 0) {
                // Handle free course (or 100% coupon discounted)
                $enrollment = Enrollment::firstOrCreate([
                    'user_id' => $user->id,
                    'course_id' => $courseIdToUse,
                ], [
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                // Record coupon usage if a coupon was applied
                if ($appliedCoupon) {
                    CouponUsage::create([
                        'coupon_id' => $appliedCoupon->id,
                        'user_id' => $user->id,
                        'enrollment_id' => $enrollment->id,
                        'discount_amount' => $discountAmount,
                    ]);
                    $appliedCoupon->increment('used_count');
                }

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment, $course, $user);

                return response()->json([
                    'success' => true,
                    'message' => 'You have successfully enrolled in this course!',
                    'enrollment_id' => $enrollment->id,
                    'bundle_id' => $isBundle ? $course->id : null,
                ]);
            } else {
                // If we get here, it means price > 0 but no payment method matched
                throw new \Exception('Invalid payment method selected for paid course.');
            }
        } catch (\Exception $e) {
            Log::error('Enrollment failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'An error occurred during enrollment. Please try again.',
            ], 500);
        }
    }

    public function razorpayVerify(Request $request)
    {
        $request->validate([
            'razorpay_order_id' => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature' => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->razorpay_order_id !== $request->razorpay_order_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.'
            ], 400);
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
                    'razorpay_signature' => $request->razorpay_signature,
                    'status' => Payment::STATUS_COMPLETED,
                ]);

                $enrollment->update([
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment);

                $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                return response()->json([
                    'success'     => true,
                    'message'     => 'Payment successful! You have been enrolled.',
                    'enrollment_id' => $enrollment->id,
                    'bundle_id'   => $bundleData['bundle_id'] ?? null,
                    'bundle_title' => $bundleData['bundle_title'] ?? null,
                    'bundle_courses' => $bundleData['courses'] ?? [],
                ]);
            } else {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment verification failed.'
                ], 400);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred processing your payment.'
            ], 500);
        }
    }
    public function sslcommerzVerify(Request $request)
    {
        $request->validate([
            'tran_id' => 'required|string',
            'val_id' => 'required|string',
            'amount' => 'required',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->sslcommerz_tran_id !== $request->tran_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.'
            ], 400);
        }

        $credentials = $this->credentials('sslcommerz');
        $storeId = $credentials['store_id'] ?? '';
        $storePassword = $credentials['store_password'] ?? '';
        $mode = $credentials['mode'] ?? 'sandbox';

        $validationUrl = ($mode === 'live')
            ? "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php"
            : "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php";

        $queryParams = [
            'val_id' => $request->val_id,
            'store_id' => $storeId,
            'store_passwd' => $storePassword,
            'format' => 'json'
        ];

        $url = $validationUrl . "?" . http_build_query($queryParams);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For sandbox sometimes needed
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10); // 10 seconds timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 30 seconds total
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment with SSLCommerz. Connection error.'
            ], 500);
        }

        $result = json_decode($response, true);

        $status = strtoupper($result['status'] ?? '');

        if ($status === 'VALID' || $status === 'SUCCESS' || $status === 'VALIDATED') {
            DB::beginTransaction();
            try {
                $enrollment->payment->update([
                    'sslcommerz_val_id' => $request->val_id,
                    'status' => Payment::STATUS_COMPLETED,
                ]);

                $enrollment->update([
                    'status' => Enrollment::STATUS_APPROVED,
                    'enrolled_at' => now(),
                ]);

                $this->processRevenueShare($enrollment);

                DB::commit();

                $this->sendEnrollmentNotifications($enrollment);

                $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                return response()->json([
                    'success'        => true,
                    'message'        => 'Payment successful! You have been enrolled.',
                    'enrollment_id'  => $enrollment->id,
                    'bundle_id'      => $bundleData['bundle_id'] ?? null,
                    'bundle_title'   => $bundleData['bundle_title'] ?? null,
                    'bundle_courses' => $bundleData['courses'] ?? [],
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred processing your payment.'
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment validation failed: ' . ($result['error'] ?? 'Unknown error'),
            'details' => $result
        ], 400);
    }

    public function stripeVerify(Request $request)
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->stripe_payment_intent_id !== $request->payment_intent_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.'
            ], 400);
        }

        $credentials = $this->credentials('stripe');
        $stripeSecret = $credentials['secret'] ?? '';

        if (empty($stripeSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Stripe credentials are not configured.'
            ], 500);
        }

        Stripe::setApiKey($stripeSecret);

        try {
            $paymentIntent = PaymentIntent::retrieve($request->payment_intent_id);

            if ($paymentIntent->status === 'succeeded') {
                DB::beginTransaction();
                try {
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

                    $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                    return response()->json([
                        'success'        => true,
                        'message'        => 'Payment successful! You have been enrolled.',
                        'enrollment_id'  => $enrollment->id,
                        'bundle_id'      => $bundleData['bundle_id'] ?? null,
                        'bundle_title'   => $bundleData['bundle_title'] ?? null,
                        'bundle_courses' => $bundleData['courses'] ?? [],
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'An error occurred processing your payment.'
                    ], 500);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Stripe payment not succeeded. Status: ' . $paymentIntent->status,
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve Stripe payment: ' . $e->getMessage()
            ], 500);
        }
    }

    public function paystackVerify(Request $request)
    {
        $request->validate([
            'reference' => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->paystack_reference !== $request->reference) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.'
            ], 400);
        }

        $credentials = $this->credentials('paystack');
        $paystackSecret = $credentials['secret_key'] ?? '';

        if (empty($paystackSecret)) {
            return response()->json([
                'success' => false,
                'message' => 'Paystack credentials are not configured.'
            ], 500);
        }

        // Verify transaction with Paystack
        $url = "https://api.paystack.co/transaction/verify/{$request->reference}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$paystackSecret}",
            "Content-Type: application/json",
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to verify payment with Paystack.'
            ], 500);
        }

        $result = json_decode($response, true);

        if (isset($result['status']) && $result['status'] && $result['data']['status'] === 'success') {
            DB::beginTransaction();
            try {
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

                $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                return response()->json([
                    'success'        => true,
                    'message'        => 'Payment successful! You have been enrolled.',
                    'enrollment_id'  => $enrollment->id,
                    'bundle_id'      => $bundleData['bundle_id'] ?? null,
                    'bundle_title'   => $bundleData['bundle_title'] ?? null,
                    'bundle_courses' => $bundleData['courses'] ?? [],
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred processing your payment.'
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Payment verification failed: ' . ($result['message'] ?? 'Unknown error'),
        ], 400);
    }

    private function calculatePrice(Course $course): float
    {
        if ($course->pricing_model === 'free') {
            return 0;
        }

        return $course->sale_price ? (float) $course->sale_price : (float) $course->regular_price;
    }

    private function createRazorpayOrder(float $amount, int $courseId, int $userId): array
    {
        $credentials = $this->credentials('razorpay');
        $apiKey = $credentials['key'] ?? null;
        $apiSecret = $credentials['secret'] ?? null;

        if (!$apiKey || !$apiSecret) {
            throw new \RuntimeException('Razorpay credentials are not configured.');
        }

        $amountInPaise = (int) ($amount * 100);

        $currency = 'INR';

        $ch = curl_init('https://api.razorpay.com/v1/orders');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_USERPWD, $apiKey . ':' . $apiSecret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'amount' => $amountInPaise,
            'currency' => $currency,
            'receipt' => 'course_' . $courseId . '_user_' . $userId . '_' . time(),
        ]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('Failed to create Razorpay order: ' . $response);
        }

        return json_decode($response, true);
    }

    private function verifyRazorpaySignature(string $orderId, string $paymentId, string $signature): bool
    {
        $apiSecret = $this->credentials('razorpay')['secret'] ?? null;
        if (!$apiSecret)
            return false;

        $payload = $orderId . '|' . $paymentId;
        $expectedSignature = hash_hmac('sha256', $payload, $apiSecret);
        return hash_equals($expectedSignature, $signature);
    }

    private function credentials(string $identifier): array
    {
        return $this->paymentGatewayService->credentialsFor($identifier);
    }

    // ─────────────────────────────────────────────────────────────
    //  XPay session helper (API)
    // ─────────────────────────────────────────────────────────────

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
            'store_id'     => $storeId,
            'order_id'     => $orderId,
            'amount'       => number_format($amount, 2, '.', ''),
            'currency'     => 'PKR',
            'description'  => 'Course Enrollment: ' . $course->title,
            'customer'     => [
                'name'  => $user->name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
            ],
            'callback_url' => url('/payment/xpay/callback'),
            'metadata'     => [
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
     * Build bundle courses data for the success response.
     * Returns a flat array of course data if the enrollment was part of a bundle,
     * or an empty array for single-course enrollments.
     */
    private function buildBundleCoursesData(Enrollment $enrollment): array
    {
        if (!$enrollment->payment || !$enrollment->payment->bundle_id) {
            return [];
        }

        $bundle = \App\Models\Bundle::with('courses.instructor')
            ->find($enrollment->payment->bundle_id);

        if (!$bundle) {
            return [];
        }

        return [
            'bundle_id'    => $bundle->id,
            'bundle_title' => $bundle->title,
            'courses'      => $bundle->courses->map(function ($course) {
                return [
                    'id'              => $course->id,
                    'title'           => $course->title,
                    'thumbnail'       => $course->featured_image
                        ? asset('storage/' . $course->featured_image)
                        : null,
                    'instructor_name' => $course->instructor->name ?? null,
                    'rating'          => $course->rating ?? 0,
                    'students_count'  => $course->students_count ?? 0,
                ];
            })->values()->toArray(),
        ];
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

        // If currency is not USD or EUR, fallback to EUR
        if (!in_array($currency, ['USD', 'EUR', 'GBP'])) {
            $currency = 'EUR';
        }

        $paymentData = [
            'amount' => [
                'currency' => $currency,
                'value' => $amountValue,
            ],
            'description' => 'Course Enrollment: ' . $course->title,
            'redirectUrl' => config('app.url') . '/payment/mollie/callback',
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
            throw new \Exception('Failed to create Mollie payment. HTTP Code: ' . $httpCode . ' Response: ' . $response);
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
     * Verify Mollie payment
     */
    public function mollieVerify(Request $request)
    {
        $request->validate([
            'payment_id' => 'required|string',
        ]);

        $paymentId = $request->payment_id;
        $apiKey = $this->credentials('mollie')['api_key'] ?? null;

        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'Mollie API key not configured.'], 500);
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
        curl_close($ch);

        if ($httpCode !== 200) {
            return response()->json(['success' => false, 'message' => 'Failed to verify payment with Mollie.'], 400);
        }

        $paymentData = json_decode($response, true);

        if (($paymentData['status'] ?? '') === 'paid') {
            $payment = Payment::where('mollie_payment_id', $paymentId)->first();

            if ($payment && $payment->status === Payment::STATUS_PENDING) {
                DB::beginTransaction();
                try {
                    $payment->update(['status' => Payment::STATUS_COMPLETED]);
                    $payment->enrollment->update([
                        'status' => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);

                    $this->processRevenueShare($payment->enrollment);

                    DB::commit();

                    $this->sendEnrollmentNotifications($payment->enrollment);

                    $bundleData = $this->buildBundleCoursesData($payment->enrollment->fresh('payment'));
                    return response()->json([
                        'success'        => true,
                        'message'        => 'Payment successful! You have been enrolled.',
                        'enrollment_id'  => $payment->enrollment_id,
                        'bundle_id'      => $bundleData['bundle_id'] ?? null,
                        'bundle_title'   => $bundleData['bundle_title'] ?? null,
                        'bundle_courses' => $bundleData['courses'] ?? [],
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Error updating enrollment: ' . $e->getMessage()], 500);
                }
            }

            return response()->json(['success' => true, 'message' => 'Payment already processed or not found.']);
        }

        return response()->json(['success' => false, 'message' => 'Payment status: ' . ($paymentData['status'] ?? 'unknown')]);
    }

    public function paypalVerify(Request $request)
    {
        $request->validate([
            'order_id' => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->paypal_order_id !== $request->order_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.'
            ], 400);
        }

        $accessToken = $this->getPaypalAccessToken();
        $credentials = $this->paymentGatewayService->credentialsFor('paypal');
        $mode = $credentials['mode'] ?? 'sandbox';
        $baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        DB::beginTransaction();

        try {
            // Capture the order
            $ch = curl_init($baseUrl . '/v2/checkout/orders/' . $request->order_id . '/capture');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken,
            ]);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 201) {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment capture failed.'
                ], 400);
            }

            $captureData = json_decode($response, true);

            if (!isset($captureData['status']) || $captureData['status'] !== 'COMPLETED') {
                $enrollment->payment->update([
                    'status' => Payment::STATUS_FAILED,
                ]);

                DB::commit();

                return response()->json([
                    'success' => false,
                    'message' => 'Payment was not successful.'
                ], 400);
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

            $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

            return response()->json([
                'success'        => true,
                'message'        => 'Payment successful! You have been enrolled.',
                'enrollment_id'  => $enrollment->id,
                'bundle_id'      => $bundleData['bundle_id'] ?? null,
                'bundle_title'   => $bundleData['bundle_title'] ?? null,
                'bundle_courses' => $bundleData['courses'] ?? [],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'An error occurred processing your payment: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getPaypalAccessToken(): string
    {
        $credentials = $this->paymentGatewayService->credentialsFor('paypal');
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

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

    private function createPaypalOrder(float $amount, $course, $user): array
    {
        $courseIdToUse = $course->id;
        $accessToken = $this->getPaypalAccessToken();
        $credentials = $this->paymentGatewayService->credentialsFor('paypal');
        $mode = $credentials['mode'] ?? 'sandbox';
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
                'return_url' => config('app.url') . '/payment/paypal/callback?success=true',
                'cancel_url' => config('app.url') . '/payment/paypal/callback?success=false',
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

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
     * Obtain a short-lived bKash token (API controller copy).
     */
    private function getBkashToken(): array
    {
        $credentials = $this->credentials('bkash');
        $appKey    = $credentials['app_key']    ?? null;
        $appSecret = $credentials['app_secret'] ?? null;
        $username  = $credentials['username']   ?? null;
        $password  = $credentials['password']   ?? null;
        $mode      = $credentials['mode']       ?? 'sandbox';

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

        $response  = curl_exec($ch);
        $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
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
     * Create a bKash payment (Checkout API) and return paymentID + bkashURL.
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
            'mode'                  => '0011',
            'payerReference'        => (string) $user->id,
            'callbackURL'           => config('app.url') . '/payment/bkash/callback',
            'amount'                => number_format($amount, 2, '.', ''),
            'currency'              => 'BDT',
            'intent'                => 'sale',
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
     * Verify a bKash payment from the mobile app.
     * Calls bKash queryPayment API and marks enrollment as approved on success.
     */
    public function bkashVerify(\Illuminate\Http\Request $request)
    {
        $request->validate([
            'payment_id'    => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $bkashPaymentId = $request->payment_id;

        $payment = Payment::where('bkash_payment_id', $bkashPaymentId)->first();

        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'bKash payment record not found.'], 404);
        }

        if ($payment->status === Payment::STATUS_COMPLETED) {
            $bundleData = $this->buildBundleCoursesData($payment->enrollment->fresh('payment'));
            return response()->json([
                'success'        => true,
                'message'        => 'Payment already processed.',
                'enrollment_id'  => $payment->enrollment_id,
                'bundle_id'      => $bundleData['bundle_id'] ?? null,
                'bundle_title'   => $bundleData['bundle_title'] ?? null,
                'bundle_courses' => $bundleData['courses'] ?? [],
            ]);
        }

        try {
            $tokenData = $this->getBkashToken();
            $idToken   = $tokenData['id_token'];
            $baseUrl   = $tokenData['base_url'];
            $appKey    = $tokenData['app_key'];

            // Execute the payment (this is the authoritative step for mobile flow)
            $ch = curl_init($baseUrl . '/execute');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['paymentID' => $bkashPaymentId]));
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
                throw new \Exception('bKash execute cURL error: ' . $curlError);
            }

            if ($httpCode !== 200) {
                throw new \Exception('bKash execute failed. HTTP ' . $httpCode);
            }

            $executeData = json_decode($response, true);
            $transactionStatus = $executeData['transactionStatus'] ?? $executeData['statusMessage'] ?? '';
            $trxId             = $executeData['trxID'] ?? null;

            if (strtolower($transactionStatus) === 'completed') {
                $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

                DB::beginTransaction();
                try {
                    $payment->update([
                        'bkash_trx_id' => $trxId,
                        'status'       => Payment::STATUS_COMPLETED,
                    ]);

                    $enrollment->update([
                        'status'      => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);

                    $this->processRevenueShare($enrollment);

                    DB::commit();

                    $this->sendEnrollmentNotifications($enrollment);

                    $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                    return response()->json([
                        'success'        => true,
                        'message'        => 'Payment successful! You have been enrolled.',
                        'enrollment_id'  => $enrollment->id,
                        'bundle_id'      => $bundleData['bundle_id'] ?? null,
                        'bundle_title'   => $bundleData['bundle_title'] ?? null,
                        'bundle_courses' => $bundleData['courses'] ?? [],
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Error updating enrollment: ' . $e->getMessage()], 500);
                }
            }

            return response()->json(['success' => false, 'message' => 'bKash payment status: ' . $transactionStatus], 400);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'bKash verification error: ' . $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  XPay verify
    // ─────────────────────────────────────────────────────────────

    /**
     * Verify an XPay payment for the Flutter app.
     * Called after WebView detects the XPay callback URL.
     */
    public function xpayVerify(Request $request)
    {
        $request->validate([
            'order_id'      => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->xpay_order_id !== $request->order_id) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.',
            ], 400);
        }

        $credentials = $this->credentials('xpay');
        $apiKey      = $credentials['api_key'] ?? null;
        $mode        = $credentials['mode']     ?? 'sandbox';

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'XPay credentials are not configured.',
            ], 500);
        }

        $baseUrl = $mode === 'live'
            ? 'https://api.xpay.com.pk/api/v1'
            : 'https://sandbox.xpay.com.pk/api/v1';

        try {
            // Call XPay verify API
            $ch = curl_init($baseUrl . '/checkout/verify/' . $request->order_id);
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
                ?? ''
            );

            $verifiedTxId = $verifyData['data']['transaction_id']
                ?? $verifyData['transaction_id']
                ?? null;

            if ($verifiedStatus === 'success' || $verifiedStatus === 'paid' || $verifiedStatus === 'completed') {
                DB::beginTransaction();
                try {
                    $enrollment->payment->update([
                        'xpay_transaction_id' => $verifiedTxId,
                        'status'              => Payment::STATUS_COMPLETED,
                    ]);

                    $enrollment->update([
                        'status'      => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);

                    $this->processRevenueShare($enrollment);

                    DB::commit();

                    $this->sendEnrollmentNotifications($enrollment);

                    $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                    return response()->json([
                        'success'        => true,
                        'message'        => 'Payment successful! You have been enrolled.',
                        'enrollment_id'  => $enrollment->id,
                        'bundle_id'      => $bundleData['bundle_id']   ?? null,
                        'bundle_title'   => $bundleData['bundle_title'] ?? null,
                        'bundle_courses' => $bundleData['courses']      ?? [],
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Error updating enrollment: ' . $e->getMessage(),
                    ], 500);
                }
            }

            // Payment not confirmed by XPay
            $enrollment->payment->update(['status' => Payment::STATUS_FAILED]);

            return response()->json([
                'success' => false,
                'message' => 'XPay payment status: ' . ($verifiedStatus ?: 'unknown'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'XPay verification error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Chapa Methods (Mobile API)
    // ─────────────────────────────────────────────────────────────

    /**
     * Create a Chapa checkout session and return tx_ref + checkout_url.
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
            'callback_url' => config('app.url') . '/payment/chapa/callback',
            'return_url'   => config('app.url') . '/payment/chapa/callback?tx_ref=' . $txRef,
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

    /**
     * Verify a Chapa payment for Flutter mobile app.
     */
    public function chapaVerify(Request $request)
    {
        $request->validate([
            'tx_ref'        => 'required|string',
            'enrollment_id' => 'required|exists:enrollments,id',
        ]);

        $enrollment = Enrollment::with('payment')->findOrFail($request->enrollment_id);

        if (!$enrollment->payment || $enrollment->payment->chapa_tx_ref !== $request->tx_ref) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid payment details.',
            ], 400);
        }

        if ($enrollment->payment->status === Payment::STATUS_COMPLETED) {
            $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));
            return response()->json([
                'success'        => true,
                'message'        => 'Payment already verified.',
                'enrollment_id'  => $enrollment->id,
                'bundle_id'      => $bundleData['bundle_id']   ?? null,
                'bundle_title'   => $bundleData['bundle_title'] ?? null,
                'bundle_courses' => $bundleData['courses']      ?? [],
            ]);
        }

        $credentials = $this->credentials('chapa');
        $secretKey   = $credentials['secret_key'] ?? null;

        if (empty($secretKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Chapa credentials are not configured.',
            ], 500);
        }

        try {
            $ch = curl_init('https://api.chapa.co/v1/transaction/verify/' . $request->tx_ref);
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

            if ($verifiedStatus === 'success') {
                DB::beginTransaction();
                try {
                    $enrollment->payment->update([
                        'chapa_reference' => $reference,
                        'status'          => Payment::STATUS_COMPLETED,
                    ]);

                    $enrollment->update([
                        'status'      => Enrollment::STATUS_APPROVED,
                        'enrolled_at' => now(),
                    ]);

                    $this->processRevenueShare($enrollment);

                    DB::commit();

                    $this->sendEnrollmentNotifications($enrollment);

                    $bundleData = $this->buildBundleCoursesData($enrollment->fresh('payment'));

                    return response()->json([
                        'success'        => true,
                        'message'        => 'Payment successful! You have been enrolled.',
                        'enrollment_id'  => $enrollment->id,
                        'bundle_id'      => $bundleData['bundle_id']   ?? null,
                        'bundle_title'   => $bundleData['bundle_title'] ?? null,
                        'bundle_courses' => $bundleData['courses']      ?? [],
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Error updating enrollment: ' . $e->getMessage(),
                    ], 500);
                }
            }

            $enrollment->payment->update(['status' => Payment::STATUS_FAILED]);

            return response()->json([
                'success' => false,
                'message' => 'Chapa payment status: ' . ($verifiedStatus ?: 'unknown'),
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Chapa verification error: ' . $e->getMessage(),
            ], 500);
        }
    }

}
