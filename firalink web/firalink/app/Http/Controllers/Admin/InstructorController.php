<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Instructor Controller
 *
 * This controller handles the management of instructors.
 *
 * @package App\Http\Controllers\Admin
 */
class InstructorController extends Controller
{
    /**
     * Display all instructors
     */
    public function index()
    {
        $query = User::where('role', 'instructor')
            ->withCount('courses');

        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%");
            });
        }

        if (request()->has('status') && request()->status) {
            if (request()->status === 'approved') {
                $query->where('is_approved', true);
            } elseif (request()->status === 'pending') {
                $query->where('is_approved', false)
                    ->whereNull('rejection_reason');
            } elseif (request()->status === 'rejected') {
                $query->where('is_approved', false)
                    ->whereNotNull('rejection_reason');
            }
        }

        $instructors = $query->with('courses.enrollments')->latest()->paginate(20)->withQueryString();

        $instructors->getCollection()->transform(function ($instructor) {
            $instructor->total_enrollments = $instructor->courses->sum(function ($course) {
                return $course->enrollments->count();
            });
            return $instructor;
        });

        $pendingCount = User::where('role', 'instructor')
            ->where('is_approved', false)
            ->whereNull('rejection_reason')
            ->count();

        return view('admin.instructors.index', compact('instructors', 'pendingCount'));
    }

    /**
     * Display pending instructor applications
     */
    public function pending()
    {
        $query = User::where('role', 'instructor')
            ->where('is_approved', false)
            ->whereNull('rejection_reason')
            ->withCount('courses');

        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('specialization', 'like', "%{$search}%");
            });
        }

        $pendingInstructors = $query->latest()->paginate(20)->withQueryString();

        return view('admin.instructors.pending', compact('pendingInstructors'));
    }

    /**
     * Display instructor details
     */
    public function show($id)
    {
        $instructor = User::where('role', 'instructor')
            ->with(['courses.enrollments', 'courses.reviews'])
            ->findOrFail($id);

        $totalCourses = $instructor->courses->count();
        $totalEnrollments = $instructor->courses->sum(function ($course) {
            return $course->enrollments->count();
        });

        $courseIds = $instructor->courses->pluck('id');
        $enrollmentIds = Enrollment::whereIn('course_id', $courseIds)->pluck('id');
        $totalRevenue = Payment::whereIn('enrollment_id', $enrollmentIds)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $publishedCourses = $instructor->courses->where('status', 'published')->count();
        $pendingCourses = $instructor->courses->where('status', 'pending')->count();

        return view('admin.instructors.show', compact(
            'instructor',
            'totalCourses',
            'totalEnrollments',
            'totalRevenue',
            'publishedCourses',
            'pendingCourses'
        ));
    }

    public function edit($id)
    {
        $instructor = User::where('role', 'instructor')->findOrFail($id);

        return view('admin.instructors.edit', compact('instructor'));
    }

    public function update(Request $request, $id)
    {
        $instructor = User::where('role', 'instructor')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $instructor->id,
            'phone' => 'nullable|string|max:20',
            'specialization' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'years_experience' => 'nullable|integer|min:0',
            'password' => 'nullable|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Fix the validation errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        $instructor->name = $request->name;
        $instructor->email = $request->email;
        $instructor->phone = $request->input('phone');
        $instructor->specialization = $request->input('specialization');
        $instructor->bio = $request->input('bio');
        $instructor->years_experience = $request->input('years_experience');
        $instructor->linkedin = $request->input('linkedin');
        $instructor->twitter = $request->input('twitter');
        $instructor->facebook = $request->input('facebook');
        $instructor->youtube = $request->input('youtube');

        if ($request->filled('password')) {
            $instructor->password = Hash::make($request->password);
        }

        $isApproved = $request->boolean('is_approved');
        $instructor->is_approved = $isApproved;
        $instructor->approval_reason = $isApproved ? $request->input('approval_reason') : null;
        $instructor->rejection_reason = $isApproved ? null : $request->input('rejection_reason');

        $instructor->save();

        ToastMagic::success('Instructor details updated successfully.');

        return redirect()->route('admin.instructors.show', $instructor->id);
    }

    /**
     * Approve an instructor
     */
    public function approve($id)
    {
        $instructor = User::where('role', 'instructor')->findOrFail($id);

        $request = request();
        $reason = $request->input('reason', '');

        $instructor->is_approved = true;
        $instructor->approval_reason = $reason;
        $instructor->rejection_reason = null;
        $instructor->save();

        if (!$instructor->hasRole('instructor')) {
            $instructor->assignRole('instructor');
        }

        $message = "Instructor '{$instructor->name}' has been approved successfully.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        ToastMagic::success($message);
        return back();
    }

    /**
     * Reject an instructor application
     */
    public function reject($id)
    {
        $instructor = User::where('role', 'instructor')->findOrFail($id);

        $request = request();
        $reason = $request->input('reason', '');

        $instructorName = $instructor->name;
        $instructor->is_approved = false;
        $instructor->rejection_reason = $reason;
        $instructor->approval_reason = null;
        $instructor->save();

        $message = "Instructor application from '{$instructorName}' has been rejected.";
        if ($reason) {
            $message .= " Reason: {$reason}";
        }

        ToastMagic::info($message);
        return back();
    }
}
