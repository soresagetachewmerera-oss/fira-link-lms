<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Student Controller
 *
 * This controller handles the management of students.
 *
 * @package App\Http\Controllers\Admin
 */
class StudentController extends Controller
{
    /**
     * Display all students
     */
    public function index()
    {
        $query = User::where('role', 'student');

        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $students = $query->with(['enrollments.course', 'enrollments.payment'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $students->getCollection()->transform(function ($student) {
            $enrollments = $student->enrollments;
            $student->total_enrollments = $enrollments->count();
            $student->completed_courses = $enrollments->where('status', Enrollment::STATUS_COMPLETED)->count();

            $paymentIds = $enrollments->pluck('payment_id')->filter();
            $student->total_spent = Payment::whereIn('id', $paymentIds)
                ->where('status', Payment::STATUS_COMPLETED)
                ->sum('amount');

            $student->active_enrollments = $enrollments->where('status', Enrollment::STATUS_APPROVED)->count();

            return $student;
        });

        $totalStudents = User::where('role', 'student')->count();

        return view('admin.students.index', compact('students', 'totalStudents'));
    }

    /**
     * Display student details
     */
    public function show($id)
    {
        $student = User::where('role', 'student')
            ->with([
                'enrollments.course.instructor',
                'enrollments.course.topics.lessons',
                'enrollments.payment',
                'enrollments.lessonProgress',
                'reviews.course'
            ])
            ->findOrFail($id);

        $enrollments = $student->enrollments;
        $totalEnrollments = $enrollments->count();
        $completedCourses = $enrollments->where('status', Enrollment::STATUS_COMPLETED)->count();
        $activeEnrollments = $enrollments->where('status', Enrollment::STATUS_APPROVED)->count();

        $paymentIds = $enrollments->pluck('payment_id')->filter();
        $totalSpent = Payment::whereIn('id', $paymentIds)
            ->where('status', Payment::STATUS_COMPLETED)
            ->sum('amount');

        $enrollmentsWithProgress = $enrollments->map(function ($enrollment) {
            $course = $enrollment->course;
            if (!$course) {
                return null;
            }

            $completedLessons = $enrollment->lessonProgress()->where('is_completed', true)->count();
            $totalLessons = 0;
            foreach ($course->topics as $topic) {
                $totalLessons += $topic->lessons()->count();
            }
            $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

            $enrollment->progress_percentage = $progress;
            $enrollment->completed_lessons = $completedLessons;
            $enrollment->total_lessons = $totalLessons;

            return $enrollment;
        })->filter();

        return view('admin.students.show', compact(
            'student',
            'totalEnrollments',
            'completedCourses',
            'totalSpent',
            'activeEnrollments',
            'enrollmentsWithProgress'
        ));
    }

    /**
     * Display student edit form
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function edit($id)
    {
        $student = User::where('role', 'student')->findOrFail($id);

        return view('admin.students.edit', compact('student'));
    }

    /**
     * Update student details
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $student = User::where('role', 'student')->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $student->id,
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Fix the validation errors and try again.');

            return back()->withErrors($validator)->withInput();
        }

        $student->name = $request->name;
        $student->email = $request->email;
        $student->phone = $request->input('phone');

        if ($request->filled('password')) {
            $student->password = Hash::make($request->password);
        }

        $student->save();

        ToastMagic::success('Student details updated successfully.');

        return redirect()->route('admin.students.show', $student->id);
    }
}
