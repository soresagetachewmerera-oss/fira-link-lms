<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Course;
use App\Http\Requests\CouponRequest;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

/**
 * CouponController
 *
 * CRUD management for coupons by admins.
 *
 * @package App\Http\Controllers\Admin
 */
class CouponController extends Controller
{
    /**
     * Display a listing of coupons.
     */
    public function index()
    {
        $coupons = Coupon::with(['course', 'instructor'])
            ->latest()
            ->paginate(15);

        return view('admin.coupons.index', compact('coupons'));
    }

    /**
     * Show the form for creating a new coupon.
     */
    public function create()
    {
        $courses = Course::where('status', 'published')
            ->orderBy('title')
            ->get();

        return view('admin.coupons.create', compact('courses'));
    }

    /**
     * Store a newly created coupon in storage.
     */
    public function store(CouponRequest $request)
    {
        Coupon::create($request->validated());

        ToastMagic::success('Coupon created successfully.');

        return redirect()->route('admin.coupons.index');
    }

    /**
     * Show the form for editing the specified coupon.
     */
    public function edit($id)
    {
        $coupon = Coupon::findOrFail($id);
        $courses = Course::where('status', 'published')
            ->orderBy('title')
            ->get();

        return view('admin.coupons.edit', compact('coupon', 'courses'));
    }

    /**
     * Update the specified coupon in storage.
     */
    public function update(CouponRequest $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update($request->validated());

        ToastMagic::success('Coupon updated successfully.');

        return redirect()->route('admin.coupons.index');
    }

    /**
     * Remove the specified coupon from storage.
     */
    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        ToastMagic::success('Coupon deleted successfully.');

        return redirect()->route('admin.coupons.index');
    }
}
