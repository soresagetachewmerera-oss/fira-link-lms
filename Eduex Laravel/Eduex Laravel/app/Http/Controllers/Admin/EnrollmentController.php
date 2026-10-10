<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Carbon\Carbon;

/**
 * Enrollment Controller
 *
 * This controller handles the management of enrollments.
 *
 * @package App\Http\Controllers\Admin
 */
class EnrollmentController extends Controller
{
    /**
     * Display all enrollments
     */
    public function index()
    {
        $viewData = $this->getIndexData();
        return view('admin.enrollments.index', $viewData);
    }

    /**
     * Display pending enrollments
     */
    public function pending()
    {
        request()->merge(['status' => Enrollment::STATUS_PENDING]);
        $viewData = $this->getIndexData();
        return view('admin.enrollments.pending', $viewData);
    }

    /**
     * Display completed enrollments
     */
    public function completed()
    {
        request()->merge(['status' => Enrollment::STATUS_COMPLETED]);
        $viewData = $this->getIndexData();
        return view('admin.enrollments.completed', $viewData);
    }

    /**
     * Get index data
     */
    private function getIndexData()
    {
        $query = Enrollment::with(['user', 'course.instructor', 'course.topics.lessons', 'payment']);

        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('course', function ($courseQuery) use ($search) {
                        $courseQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if (request()->has('status') && request()->status) {
            $query->where('status', request()->status);
        }

        if (request()->has('course_id') && request()->course_id) {
            $query->where('course_id', request()->course_id);
        }

        if (request()->has('student_id') && request()->student_id) {
            $query->where('user_id', request()->student_id);
        }

        if (request()->has('instructor_id') && request()->instructor_id) {
            $query->whereHas('course', function ($q) {
                $q->where('instructor_id', request()->instructor_id);
            });
        }

        if (request()->has('date_from') && request()->date_from) {
            $query->whereDate('created_at', '>=', request()->date_from);
        }
        if (request()->has('date_to') && request()->date_to) {
            $query->whereDate('created_at', '<=', request()->date_to);
        }

        if (request()->has('payment_status') && request()->payment_status) {
            if (request()->payment_status === 'paid') {
                $query->whereHas('payment', function ($q) {
                    $q->where('status', Payment::STATUS_COMPLETED);
                });
            } elseif (request()->payment_status === 'unpaid') {
                $query->where(function ($q) {
                    $q->whereNull('payment_id')
                        ->orWhereHas('payment', function ($paymentQuery) {
                            $paymentQuery->where('status', '!=', Payment::STATUS_COMPLETED);
                        });
                });
            } elseif (request()->payment_status === 'pending') {
                $query->whereHas('payment', function ($q) {
                    $q->where('status', Payment::STATUS_PENDING);
                });
            }
        }

        $enrollments = $query->latest()->paginate(20)->withQueryString();

        $enrollments->getCollection()->transform(function ($enrollment) {
            $course = $enrollment->course;
            if ($course) {
                $completedLessons = $enrollment->lessonProgress()->where('is_completed', true)->count();
                $totalLessons = 0;
                foreach ($course->topics as $topic) {
                    $totalLessons += $topic->lessons()->count();
                }
                $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;
                $enrollment->progress_percentage = $progress;
                $enrollment->completed_lessons = $completedLessons;
                $enrollment->total_lessons = $totalLessons;
            }
            return $enrollment;
        });

        $totalEnrollments = Enrollment::count();
        $pendingEnrollments = Enrollment::where('status', Enrollment::STATUS_PENDING)->count();
        $approvedEnrollments = Enrollment::where('status', Enrollment::STATUS_APPROVED)->count();
        $completedEnrollments = Enrollment::where('status', Enrollment::STATUS_COMPLETED)->count();
        $cancelledEnrollments = Enrollment::where('status', Enrollment::STATUS_CANCELLED)->count();

        $totalRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereHas('enrollment')
            ->sum('amount');

        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $instructors = User::where('role', 'instructor')->where('is_approved', true)->orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get();

        return compact(
            'enrollments',
            'totalEnrollments',
            'pendingEnrollments',
            'approvedEnrollments',
            'completedEnrollments',
            'cancelledEnrollments',
            'totalRevenue',
            'courses',
            'instructors',
            'students'
        );
    }

    /**
     * Display enrollment details
     */
    public function show($id)
    {
        $enrollment = Enrollment::with([
            'user',
            'course.instructor',
            'course.topics.lessons',
            'payment',
            'lessonProgress.lesson',
            'quizAttempts.quiz',
            'assignmentSubmissions.assignment'
        ])->findOrFail($id);

        $course = $enrollment->course;
        $completedLessons = $enrollment->lessonProgress()->where('is_completed', true)->count();
        $totalLessons = 0;
        if ($course) {
            foreach ($course->topics as $topic) {
                $totalLessons += $topic->lessons()->count();
            }
        }
        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $otherEnrollments = Enrollment::where('user_id', $enrollment->user_id)
            ->where('id', '!=', $enrollment->id)
            ->with('course')
            ->latest()
            ->limit(5)
            ->get();

        return view('admin.enrollments.show', compact(
            'enrollment',
            'progress',
            'completedLessons',
            'totalLessons',
            'otherEnrollments'
        ));
    }

    /**
     * Update enrollment status
     */
    public function updateStatus(Request $request, $id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $request->validate([
            'status' => 'required|in:pending,approved,completed,cancelled',
        ]);

        $oldStatus = $enrollment->status;
        $enrollment->status = $request->status;

        if ($request->status === Enrollment::STATUS_APPROVED && !$enrollment->enrolled_at) {
            $enrollment->enrolled_at = now();
        }

        $enrollment->save();

        ToastMagic::success("Enrollment status updated from " . ucfirst($oldStatus) . " to " . ucfirst($request->status) . ".");

        return back();
    }

    /**
     * Approve enrollment
     */
    public function approve($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $enrollment->status = Enrollment::STATUS_APPROVED;
        if (!$enrollment->enrolled_at) {
            $enrollment->enrolled_at = now();
        }
        $enrollment->save();

        ToastMagic::success("Enrollment approved successfully.");

        return back();
    }

    /**
     * Reject enrollment
     */
    public function reject($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $enrollment->status = Enrollment::STATUS_CANCELLED;
        $enrollment->save();

        ToastMagic::info("Enrollment rejected.");

        return back();
    }

    /**
     * Cancel enrollment
     */
    public function cancel($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $enrollment->status = Enrollment::STATUS_CANCELLED;
        $enrollment->save();

        ToastMagic::info("Enrollment cancelled.");

        return back();
    }

    /**
     * Mark enrollment as completed
     */
    public function complete($id)
    {
        $enrollment = Enrollment::findOrFail($id);

        $enrollment->status = Enrollment::STATUS_COMPLETED;
        $enrollment->save();

        ToastMagic::success("Enrollment marked as completed.");

        return back();
    }
}
