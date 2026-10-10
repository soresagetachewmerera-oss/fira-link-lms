<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Payment Model
 *
 * This model represents a payment for a course.
 *
 * @package App\Models
 */
class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'bundle_id',
        'subscription_plan_id',
        'user_subscription_id',
        'enrollment_id',
        'tour_booking_id',
        'payment_method',
        'amount',
        'platform_commission',
        'instructor_earning',
        'commission_processed',
        'status',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'stripe_payment_intent_id',
        'stripe_client_secret',
        'paystack_reference',
        'paystack_access_code',
        'flutterwave_transaction_id',
        'flutterwave_tx_ref',
        'paypal_order_id',
        'paypal_payer_id',
        'paypal_payment_id',
        'sslcommerz_tran_id',
        'sslcommerz_session_key',
        'sslcommerz_val_id',
        'mollie_payment_id',
        'bkash_payment_id',
        'bkash_trx_id',
        'xpay_order_id',
        'xpay_transaction_id',
        'chapa_tx_ref',
        'chapa_reference',
        'receipt_file',
        'transaction_id',
        'notes',
        'coupon_id',
        'discount_amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_commission' => 'decimal:2',
        'instructor_earning' => 'decimal:2',
        'commission_processed' => 'boolean',
        'discount_amount' => 'decimal:2',
    ];

    protected static function booted()
    {
        static::saved(function ($payment) {
            if ($payment->isDirty('status') && $payment->status === self::STATUS_COMPLETED) {
                if ($payment->coupon_id) {
                    \App\Models\CouponUsage::firstOrCreate([
                        'coupon_id' => $payment->coupon_id,
                        'user_id' => $payment->user_id ?? ($payment->enrollment?->user_id),
                        'enrollment_id' => $payment->enrollment_id,
                    ], [
                        'discount_amount' => $payment->discount_amount ?? 0.00,
                    ]);

                    $coupon = \App\Models\Coupon::find($payment->coupon_id);
                    if ($coupon) {
                        $coupon->increment('used_count');
                    }
                }
            }
        });
    }

    const PAYMENT_METHOD_RAZORPAY = 'razorpay';
    const PAYMENT_METHOD_OFFLINE = 'offline';
    const PAYMENT_METHOD_STRIPE = 'stripe';
    const PAYMENT_METHOD_PAYSTACK = 'paystack';
    const PAYMENT_METHOD_FLUTTERWAVE = 'flutterwave';
    const PAYMENT_METHOD_PAYPAL = 'paypal';
    const PAYMENT_METHOD_SSLCOMMERZ = 'sslcommerz';
    const PAYMENT_METHOD_MOLLIE = 'mollie';
    const PAYMENT_METHOD_BKASH = 'bkash';
    const PAYMENT_METHOD_XPAY = 'xpay';
    const PAYMENT_METHOD_CHAPA = 'chapa';

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function tourBooking(): BelongsTo
    {
        return $this->belongsTo(TourBooking::class, 'tour_booking_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(Bundle::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    public function userSubscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'user_subscription_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getReceiptUrlAttribute(): ?string
    {
        if (!$this->receipt_file) {
            return null;
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (!$disk->exists($this->receipt_file)) {
            return null;
        }

        return $disk->url($this->receipt_file);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isRazorpay(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_RAZORPAY;
    }

    public function isOffline(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_OFFLINE;
    }

    public function isStripe(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_STRIPE;
    }

    public function isPaystack(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PAYSTACK;
    }

    public function isFlutterwave(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_FLUTTERWAVE;
    }

    public function isPaypal(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_PAYPAL;
    }

    public function isSslcommerz(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_SSLCOMMERZ;
    }

    public function isMollie(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_MOLLIE;
    }

    public function isBkash(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_BKASH;
    }

    public function isXpay(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_XPAY;
    }

    public function isChapa(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_CHAPA;
    }
}
