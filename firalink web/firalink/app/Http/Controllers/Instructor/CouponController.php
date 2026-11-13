<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Course;
use App\Http\Requests\CouponRequest;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CouponController
 *
 * CRUD management for coupons by instructors.
 *
 * @package App\Http\Controllers\Instructor
 */
class CouponController extends Controller
{
    /**
     * Display a listing of coupons.
     */
    public function index()
    {
        $coupons = Coupon::with('course')
            ->where('instructor_id', Auth::id())
            ->latest()
            ->paginate(15);

        return view('instructor.coupons.index', compact('coupons'));
    }

    /**
     * Show the form for creating a new coupon.
     */
    public function create()
    {
        // Instructors can only create coupons for their own published courses
        $courses = Course::where('instructor_id', Auth::id())
            ->where('status', 'published')
            ->orderBy('title')
            ->get();

        return view('instructor.coupons.create', compact('courses'));
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(CouponRequest $request)
    {
        $data = $request->validated();
        $data['instructor_id'] = Auth::id();

        // Security check: ensure course belongs to the instructor if course_id is set
        if (!empty($data['course_id'])) {
            $course = Course::where('id', $data['course_id'])
                ->where('instructor_id', Auth::id())
                ->first();
            if (!$course) {
                ToastMagic::error('Unauthorized course selected.');
                return back()->withInput();
            }
        }

        Coupon::create($data);

        ToastMagic::success('Coupon created successfully.');

        return redirect()->route('instructor.coupons.index');
    }

    /**
     * Show the form for editing the specified coupon.
     */
    public function edit($id)
    {
        $coupon = Coupon::where('instructor_id', Auth::id())->findOrFail($id);
        
        $courses = Course::where('instructor_id', Auth::id())
            ->where('status', 'published')
            ->orderBy('title')
            ->get();

        return view('instructor.coupons.edit', compact('coupon', 'courses'));
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(CouponRequest $request, $id)
    {
        $coupon = Coupon::where('instructor_id', Auth::id())->findOrFail($id);
        
        $data = $request->validated();
        $data['instructor_id'] = Auth::id();

        // Security check: ensure course belongs to the instructor if course_id is set
        if (!empty($data['course_id'])) {
            $course = Course::where('id', $data['course_id'])
                ->where('instructor_id', Auth::id())
                ->first();
            if (!$course) {
                ToastMagic::error('Unauthorized course selected.');
                return back()->withInput();
            }
        }

        $coupon->update($data);

        ToastMagic::success('Coupon updated successfully.');

        return redirect()->route('instructor.coupons.index');
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy($id)
    {
        $coupon = Coupon::where('instructor_id', Auth::id())->findOrFail($id);
        $coupon->delete();

        ToastMagic::success('Coupon deleted successfully.');

        return redirect()->route('instructor.coupons.index');
    }
}
