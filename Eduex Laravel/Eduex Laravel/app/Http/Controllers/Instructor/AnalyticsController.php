<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Analytics Controller
 *
 * This controller handles the analytics functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class AnalyticsController extends Controller
{
    /**
     * Display the analytics dashboard
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $currency = currency_code();

        $baseEnrollmentQuery = Enrollment::query()
            ->whereHas('course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            });

        $baseCourseQuery = Course::query()
            ->where('instructor_id', $user->id);

        $basePaymentQuery = Payment::query()
            ->whereHas('enrollment.course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->where('status', Payment::STATUS_COMPLETED);

        $totalRevenue = (clone $basePaymentQuery)->sum('instructor_earning');
        $monthlyRevenue = (clone $basePaymentQuery)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->sum('instructor_earning');
        $previousMonthRevenue = (clone $basePaymentQuery)
            ->whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])
            ->sum('instructor_earning');
        $revenueChangePct = $previousMonthRevenue > 0
            ? round((($monthlyRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100, 1)
            : null;

        $totalStudents = (clone $baseEnrollmentQuery)->distinct('user_id')->count('user_id');
        $newStudentsThisMonth = (clone $baseEnrollmentQuery)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->distinct('user_id')
            ->count('user_id');
        $newStudentsLastMonth = (clone $baseEnrollmentQuery)
            ->whereBetween('created_at', [
                Carbon::now()->subMonth()->startOfMonth(),
                Carbon::now()->subMonth()->endOfMonth(),
            ])
            ->distinct('user_id')
            ->count('user_id');
        $studentsChangePct = $newStudentsLastMonth > 0
            ? round((($newStudentsThisMonth - $newStudentsLastMonth) / $newStudentsLastMonth) * 100, 1)
            : null;

        $totalEnrollments = (clone $baseEnrollmentQuery)->count();
        $monthlyEnrollments = (clone $baseEnrollmentQuery)
            ->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->count();

        $averageRating = Review::query()
            ->whereHas('course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->avg('rating') ?? 0;
        $totalReviews = Review::query()
            ->whereHas('course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->count();

        $courseStats = $baseCourseQuery
            ->select([
                DB::raw('COUNT(*) as total_courses'),
                DB::raw('SUM(CASE WHEN status = \'published\' THEN 1 ELSE 0 END) as active_courses'),
            ])
            ->first();

        $completionStats = (clone $baseEnrollmentQuery)
            ->select([
                DB::raw('SUM(CASE WHEN status = \'' . Enrollment::STATUS_COMPLETED . '\' THEN 1 ELSE 0 END) as completed'),
                DB::raw('COUNT(*) as total'),
            ])
            ->first();

        $completionRate = $completionStats->total > 0
            ? round(($completionStats->completed / $completionStats->total) * 100, 1)
            : 0;

        $monthlyRevenueData = (clone $basePaymentQuery)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, SUM(instructor_earning) as total')
            ->where('created_at', '>=', Carbon::now()->startOfMonth()->subMonths(11))
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        $monthlyEnrollmentData = (clone $baseEnrollmentQuery)
            ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as ym, COUNT(*) as total')
            ->where('created_at', '>=', Carbon::now()->startOfMonth()->subMonths(11))
            ->groupBy('ym')
            ->orderBy('ym')
            ->pluck('total', 'ym');

        $labels = [];
        $revenueData = [];
        $enrollmentsData = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $key = $month->format('Y-m');
            $labels[] = $month->format('M');
            $revenueData[] = round((float) ($monthlyRevenueData[$key] ?? 0), 2);
            $enrollmentsData[] = (int) ($monthlyEnrollmentData[$key] ?? 0);
        }

        $topCourses = $baseCourseQuery
            ->withCount([
                'enrollments as enrollments_count',
                'reviews as reviews_count',
            ])
            ->withAvg('reviews', 'rating')
            ->select('courses.*')
            ->selectSub(function ($query) {
                $query->selectRaw('SUM(payments.instructor_earning)')
                    ->from('payments')
                    ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
                    ->whereColumn('enrollments.course_id', 'courses.id')
                    ->where('payments.status', Payment::STATUS_COMPLETED);
            }, 'revenue_sum')
            ->orderByDesc('revenue_sum')
            ->limit(5)
            ->get()
            ->map(function ($course) {
                $enrollmentsCount = $course->enrollments_count ?? 0;
                $completedCount = $course->enrollments()
                    ->where('status', Enrollment::STATUS_COMPLETED)
                    ->count();
                $completionRate = $enrollmentsCount > 0
                    ? round(($completedCount / $enrollmentsCount) * 100, 1)
                    : 0;

                return [
                    'title' => $course->title,
                    'enrollments' => $enrollmentsCount,
                    'revenue' => round($course->revenue_sum ?? 0, 2),
                    'rating' => round($course->reviews_avg_rating ?? 0, 1),
                    'completion_rate' => $completionRate,
                ];
            });

        $engagementActive = (clone $baseEnrollmentQuery)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->distinct('user_id')
            ->count('user_id');

        $engagementCompleting = (clone $baseEnrollmentQuery)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->distinct('user_id')
            ->count('user_id');

        $engagementAtRisk = (clone $baseEnrollmentQuery)
            ->where('status', Enrollment::STATUS_PENDING)
            ->distinct('user_id')
            ->count('user_id');

        $studentEngagement = [
            'active_students' => $engagementActive,
            'active_percentage' => $totalStudents > 0 ? round(($engagementActive / $totalStudents) * 100, 1) : 0,
            'completing_students' => $engagementCompleting,
            'completing_percentage' => $totalStudents > 0 ? round(($engagementCompleting / $totalStudents) * 100, 1) : 0,
            'at_risk_students' => $engagementAtRisk,
            'at_risk_percentage' => $totalStudents > 0 ? round(($engagementAtRisk / $totalStudents) * 100, 1) : 0,
        ];

        $recentEnrollments = (clone $baseEnrollmentQuery)
            ->with(['course:id,title', 'user:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Enrollment $enrollment) {
                return [
                    'type' => 'enrollment',
                    'icon' => 'fa-user-plus',
                    'message' => sprintf(
                        '%s enrolled in "%s"',
                        $enrollment->user->name ?? 'A student',
                        $enrollment->course->title ?? 'a course'
                    ),
                    'time' => $enrollment->created_at->diffForHumans(),
                    'timestamp' => $enrollment->created_at,
                ];
            });

        $recentReviews = Review::query()
            ->whereHas('course', function ($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->with(['course:id,title', 'user:id,name'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (Review $review) {
                return [
                    'type' => 'review',
                    'icon' => 'fa-star',
                    'message' => sprintf(
                        '%s rated "%s" %s stars',
                        $review->user->name ?? 'A student',
                        $review->course->title ?? 'a course',
                        number_format($review->rating, 1)
                    ),
                    'time' => $review->created_at->diffForHumans(),
                    'timestamp' => $review->created_at,
                ];
            });

        $recentActivity = $recentEnrollments
            ->merge($recentReviews)
            ->sortByDesc('timestamp')
            ->take(8)
            ->map(function ($item) {
                unset($item['timestamp']);
                return $item;
            })
            ->values();

        $analytics = [
            'overview' => [
                'currency' => $currency,
                'total_revenue' => $totalRevenue,
                'monthly_revenue' => $monthlyRevenue,
                'previous_month_revenue' => $previousMonthRevenue,
                'revenue_change_pct' => $revenueChangePct,
                'total_students' => $totalStudents,
                'new_students_this_month' => $newStudentsThisMonth,
                'students_change_pct' => $studentsChangePct,
                'total_courses' => $courseStats->total_courses ?? 0,
                'active_courses' => $courseStats->active_courses ?? 0,
                'total_enrollments' => $totalEnrollments,
                'monthly_enrollments' => $monthlyEnrollments,
                'average_rating' => round($averageRating, 1),
                'total_reviews' => $totalReviews,
                'completion_rate' => $completionRate,
                'refund_rate' => 0,
            ],
            'revenue' => [
                'labels' => $labels,
                'data' => $revenueData,
                'currency' => $currency,
            ],
            'enrollments' => [
                'labels' => $labels,
                'data' => $enrollmentsData,
            ],
            'top_courses' => $topCourses,
            'student_engagement' => $studentEngagement,
            'recent_activity' => $recentActivity,
        ];

        return view('instructor.analytics', compact('analytics', 'user'));
    }
}
