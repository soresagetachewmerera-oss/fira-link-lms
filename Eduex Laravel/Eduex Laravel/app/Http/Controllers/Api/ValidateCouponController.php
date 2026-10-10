<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateCouponRequest;
use App\Services\CouponService;
use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Exception;

/**
 * ValidateCouponController
 *
 * API endpoint to validate coupons for students.
 *
 * @package App\Http\Controllers\Api
 */
class ValidateCouponController extends Controller
{
    public function __construct(private CouponService $couponService)
    {
    }

    /**
     * Validate a coupon code for checkout.
     */
    public function validateCoupon(ValidateCouponRequest $request)
    {
        try {
            $code = $request->input('code');
            $courseId = $request->input('course_id');
            $user = Auth::user();

            $course = Course::findOrFail($courseId);
            $price = $course->sale_price ? (float) $course->sale_price : (float) $course->regular_price;

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
                'final_price' => $finalPrice,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage() ?: 'Invalid coupon code.',
            ], 400);
        }
    }
}
