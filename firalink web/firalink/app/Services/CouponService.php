<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Course;
use App\Models\User;
use Carbon\Carbon;
use Exception;

/**
 * Coupon Service
 *
 * Handles the business logic for coupon validation and discount calculation.
 *
 * @package App\Services
 */
class CouponService
{
    /**
     * Validate a coupon code for a given course and user.
     *
     * @param string $code
     * @param Course|int $course
     * @param User|null $user
     * @return Coupon
     * @throws Exception
     */
    public function validate(string $code, $course, ?User $user): Coupon
    {
        if (!$user) {
            throw new Exception('User authentication is required to apply a coupon.');
        }

        $coupon = Coupon::where('code', $code)
            ->where('status', 'active')
            ->first();

        if (!$coupon) {
            throw new Exception('Invalid coupon code or the coupon is inactive.');
        }

        // Check date range
        $now = Carbon::now();
        if ($coupon->valid_from && $now->lt(Carbon::parse($coupon->valid_from))) {
            throw new Exception('This coupon is not active yet.');
        }
        if ($coupon->valid_to && $now->gt(Carbon::parse($coupon->valid_to))) {
            throw new Exception('This coupon has expired.');
        }

        // Check usage limit
        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw new Exception('This coupon usage limit has been reached.');
        }

        // Check if user already used it
        $alreadyUsed = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $user->id)
            ->exists();
        if ($alreadyUsed) {
            throw new Exception('You have already used this coupon code.');
        }

        // Resolve course
        if (is_numeric($course)) {
            $course = Course::findOrFail($course);
        }

        // Check course restriction
        if ($coupon->course_id !== null && $coupon->course_id !== $course->id) {
            throw new Exception('This coupon is not valid for this course.');
        }

        // Check instructor restriction
        if ($coupon->instructor_id !== null && $coupon->instructor_id !== $course->instructor_id) {
            throw new Exception('This coupon is not valid for this course.');
        }

        return $coupon;
    }

    /**
     * Calculate discount amount.
     *
     * @param Coupon $coupon
     * @param float $price
     * @return float
     */
    public function calculateDiscount(Coupon $coupon, float $price): float
    {
        if ($price <= 0) {
            return 0.0;
        }

        if ($coupon->type === 'percentage') {
            $discount = $price * ($coupon->value / 100);
        } else {
            $discount = $coupon->value;
        }

        // Cap discount at the price
        return round(min($discount, $price), 2);
    }
}
