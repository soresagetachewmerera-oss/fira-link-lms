<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\CouponRequest;
use App\Models\Coupon;
use App\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Instructor Coupon Controller (API)
 *
 * Handles coupon management for the instructor mobile app.
 *
 * @package App\Http\Controllers\Api\Instructor
 */
class CouponController extends Controller
{
    /**
     * List coupons belonging to the authenticated instructor.
     */
    public function index(): JsonResponse
    {
        $coupons = Coupon::with('course:id,title')
            ->where('instructor_id', Auth::id())
            ->latest()
            ->get()
            ->map(fn ($coupon) => $this->formatCoupon($coupon));

        return response()->json(['success' => true, 'data' => $coupons]);
    }

    /**
     * Show a single coupon.
     */
    public function show(int $id): JsonResponse
    {
        $coupon = Coupon::with('course:id,title')
            ->where('instructor_id', Auth::id())
            ->findOrFail($id);

        return response()->json(['success' => true, 'data' => $this->formatCoupon($coupon)]);
    }

    /**
     * Create a new coupon for this instructor.
     */
    public function store(CouponRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['instructor_id'] = Auth::id();
        $data['used_count'] = 0;

        // Security: ensure the course belongs to this instructor
        if (!empty($data['course_id'])) {
            $course = Course::where('id', $data['course_id'])
                ->where('instructor_id', Auth::id())
                ->first();

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected course does not belong to you.',
                ], 403);
            }
        }

        $coupon = Coupon::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully.',
            'data' => $this->formatCoupon($coupon->load('course:id,title')),
        ], 201);
    }

    /**
     * Update an existing instructor coupon.
     */
    public function update(CouponRequest $request, int $id): JsonResponse
    {
        $coupon = Coupon::where('instructor_id', Auth::id())->findOrFail($id);

        $data = $request->validated();
        $data['instructor_id'] = Auth::id();

        // Security: ensure the course belongs to this instructor
        if (!empty($data['course_id'])) {
            $course = Course::where('id', $data['course_id'])
                ->where('instructor_id', Auth::id())
                ->first();

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected course does not belong to you.',
                ], 403);
            }
        }

        $coupon->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully.',
            'data' => $this->formatCoupon($coupon->load('course:id,title')),
        ]);
    }

    /**
     * Delete an instructor coupon.
     */
    public function destroy(int $id): JsonResponse
    {
        $coupon = Coupon::where('instructor_id', Auth::id())->findOrFail($id);
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully.',
        ]);
    }

    /**
     * Format coupon for API response.
     */
    private function formatCoupon(Coupon $coupon): array
    {
        return [
            'id' => $coupon->id,
            'code' => $coupon->code,
            'type' => $coupon->type,
            'value' => (float) $coupon->value,
            'valid_from' => $coupon->valid_from?->toDateString(),
            'valid_to' => $coupon->valid_to?->toDateString(),
            'usage_limit' => $coupon->usage_limit,
            'used_count' => $coupon->used_count,
            'course_id' => $coupon->course_id,
            'course_title' => $coupon->course?->title,
            'status' => $coupon->status,
            'is_valid' => $coupon->isValidNow(),
        ];
    }
}
