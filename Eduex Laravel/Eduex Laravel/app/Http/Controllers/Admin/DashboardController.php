<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\EventBooking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $thisMonthStart = Carbon::now()->startOfMonth();
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // 1. Overview Stats
        $totalRevenue = Payment::where('status', Payment::STATUS_COMPLETED)->sum('amount');
        $lastMonthRevenue = Payment::where('status', Payment::STATUS_COMPLETED)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->sum('amount');
        $revenueChangePct = $lastMonthRevenue > 0
            ? round((($totalRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100, 1)
            : null;

        $totalStudents = User::where('role', 'student')->count();
        $newStudentsThisMonth = User::where('role', 'student')
            ->where('created_at', '>=', $thisMonthStart)
            ->count();
        $lastMonthNewStudents = User::where('role', 'student')
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();
        $studentsChangePct = $lastMonthNewStudents > 0
            ? round((($newStudentsThisMonth - $lastMonthNewStudents) / $lastMonthNewStudents) * 100, 1)
            : null;

        $totalEnrollments = Enrollment::count();
        $thisMonthEnrollments = Enrollment::where('created_at', '>=', $thisMonthStart)->count();

        $averageRating = Review::where('is_approved', true)->avg('rating') ?: 0;
        $totalApprovedReviews = Review::where('is_approved', true)->count();

        // 2. Pending Offline Payments
        $pendingOfflinePayments = Payment::where('payment_method', Payment::PAYMENT_METHOD_OFFLINE)
            ->where('status', Payment::STATUS_PENDING)
            ->count();

        // 3. Multi-period Chart Data
        $charts = [
            'thirty_days' => $this->getChartData('thirty_days'),
            'this_year' => $this->getChartData('this_year'),
            'last_12_months' => $this->getChartData('last_12_months'),
        ];

        // 3. Secondary Stats
        $totalCourses = Course::count();
        $activeCourses = Course::where('status', 'published')->count();
        $completionStats = Enrollment::select([
            DB::raw('SUM(CASE WHEN status = \'' . Enrollment::STATUS_COMPLETED . '\' THEN 1 ELSE 0 END) as completed'),
            DB::raw('COUNT(*) as total'),
        ])->first();
        $completionRate = $completionStats->total > 0
            ? round(($completionStats->completed / $completionStats->total) * 100, 1)
            : 0;

        // 4. Top Performing Courses
        $topCourseData = Enrollment::join('payments', 'enrollments.id', '=', 'payments.enrollment_id')
            ->where('payments.status', Payment::STATUS_COMPLETED)
            ->select('enrollments.course_id', DB::raw('SUM(payments.amount) as total_revenue'))
            ->groupBy('enrollments.course_id')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get();

        $topCourses = Course::whereIn('id', $topCourseData->pluck('course_id'))
            ->withCount(['enrollments', 'reviews'])
            ->withAvg('reviews', 'rating')
            ->select('id', 'title', 'featured_image')
            ->get()
            ->map(function ($course) use ($topCourseData) {
                $revenue = (float) ($topCourseData->firstWhere('course_id', $course->id)->total_revenue ?? 0);

                $completedCount = Enrollment::where('course_id', $course->id)
                    ->where('status', Enrollment::STATUS_COMPLETED)
                    ->count();
                $courseCompletionRate = $course->enrollments_count > 0
                    ? round(($completedCount / $course->enrollments_count) * 100, 1)
                    : 0;

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'thumbnail' => $course->thumbnail,
                    'enrollments' => $course->enrollments_count,
                    'revenue' => $revenue,
                    'rating' => round($course->reviews_avg_rating ?? 0, 1),
                    'completion_rate' => $courseCompletionRate,
                ];
            })
            ->sortByDesc('revenue')
            ->values();

        // 5. Student Engagement
        $activeEngaged = Enrollment::whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->distinct('user_id')->count('user_id');
        $completingEngaged = Enrollment::where('status', Enrollment::STATUS_COMPLETED)
            ->distinct('user_id')->count('user_id');
        $atRiskEngaged = Enrollment::where('status', Enrollment::STATUS_PENDING)
            ->distinct('user_id')->count('user_id');

        $studentEngagement = [
            'active_students' => $activeEngaged,
            'active_percentage' => $totalStudents > 0 ? round(($activeEngaged / $totalStudents) * 100, 1) : 0,
            'completing_students' => $completingEngaged,
            'completing_percentage' => $totalStudents > 0 ? round(($completingEngaged / $totalStudents) * 100, 1) : 0,
            'at_risk_students' => $atRiskEngaged,
            'at_risk_percentage' => $totalStudents > 0 ? round(($atRiskEngaged / $totalStudents) * 100, 1) : 0,
        ];

        // 6. Recent Activity
        $recentEnrollments = Enrollment::with(['user:id,name', 'course:id,title'])
            ->latest()->limit(5)->get()->map(fn($e) => [
                'type' => 'enrollment',
                'icon' => 'fa-user-plus',
                'message' => ($e->user->name ?? 'Student') . ' enrolled in "' . ($e->course->title ?? 'Course') . '"',
                'time' => $e->created_at->diffForHumans(),
                'timestamp' => $e->created_at,
            ]);

        $recentReviews = Review::with(['user:id,name', 'course:id,title'])
            ->where('is_approved', true)
            ->latest()->limit(5)->get()->map(fn($r) => [
                'type' => 'review',
                'icon' => 'fa-star',
                'message' => ($r->user->name ?? 'Student') . ' rated "' . ($r->course->title ?? 'Course') . '" ' . round($r->rating, 1) . ' stars',
                'time' => $r->created_at->diffForHumans(),
                'timestamp' => $r->created_at,
            ]);

        $recentActivity = $recentEnrollments->merge($recentReviews)->sortByDesc('timestamp')->take(8)->values();

        // 7. Recent Event Bookings
        $recentEventBookings = EventBooking::with(['user:id,name', 'event:id,title'])
            ->latest('booked_at')
            ->limit(5)
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'user_name' => $b->full_name ?: ($b->user->name ?? 'Student'),
                'event_title' => $b->event->title ?? 'Event',
                'seats' => $b->seats,
                'status' => $b->status,
                'time' => $b->booked_at ? $b->booked_at->diffForHumans() : $b->created_at->diffForHumans(),
            ]);

        // 8. Recent Transactions
        $sales = Payment::with(['enrollment.user:id,name', 'enrollment.course:id,title'])
            ->orderByDesc('created_at')->limit(10)->get()->map(fn($p) => [
                'id' => 'sale-' . $p->id,
                'type' => 'sale',
                'description' => $p->enrollment->course->title ?? 'Course Sale',
                'student' => $p->enrollment->user->name ?? 'User',
                'amount' => (float) $p->amount,
                'status' => $p->status,
                'date' => $p->created_at->format('M d, Y'),
                'raw_date' => $p->created_at,
            ]);

        $recentTransactions = $sales;

        $analytics = [
            'overview' => [
                'total_revenue' => $totalRevenue,
                'revenue_change_pct' => $revenueChangePct,
                'total_students' => $totalStudents,
                'new_students_this_month' => $newStudentsThisMonth,
                'students_change_pct' => $studentsChangePct,
                'total_enrollments' => $totalEnrollments,
                'this_month_enrollments' => $thisMonthEnrollments,
                'average_rating' => $averageRating,
                'total_reviews' => $totalApprovedReviews,
                'completion_rate' => $completionRate,
                'active_courses' => $activeCourses,
                'total_courses' => $totalCourses,
                'refund_rate' => 0,
                'pending_offline_payments' => $pendingOfflinePayments,
            ],
            'charts' => $charts,
            'top_courses' => $topCourses,
            'student_engagement' => $studentEngagement,
            'recent_activity' => $recentActivity,
            'recent_event_bookings' => $recentEventBookings,
            'recent_transactions' => $recentTransactions,
        ];

        return view('admin.dashboard', compact('analytics'));
    }

    private function getChartData($period)
    {
        $labels = [];
        $revenue = [];
        $enrollments = [];

        if ($period === 'thirty_days') {
            for ($i = 29; $i >= 0; $i--) {
                $date = Carbon::now()->subDays($i);
                $labels[] = $date->format('d M');
                $revenue[] = (float) Payment::where('status', Payment::STATUS_COMPLETED)
                    ->whereDate('created_at', $date)
                    ->sum('amount');
                $enrollments[] = Enrollment::whereDate('created_at', $date)->count();
            }
        } elseif ($period === 'this_year') {
            $startOfYear = Carbon::now()->startOfYear();
            $currentMonth = Carbon::now()->month;
            for ($i = 1; $i <= $currentMonth; $i++) {
                $date = $startOfYear->copy()->month($i);
                $labels[] = $date->format('M');
                $revenue[] = (float) Payment::where('status', Payment::STATUS_COMPLETED)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount');
                $enrollments[] = Enrollment::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
            }
        } else { // last_12_months
            for ($i = 11; $i >= 0; $i--) {
                $date = Carbon::now()->subMonths($i);
                $labels[] = $date->format('M');
                $revenue[] = (float) Payment::where('status', Payment::STATUS_COMPLETED)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->sum('amount');
                $enrollments[] = Enrollment::whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->count();
            }
        }

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'enrollments' => $enrollments,
        ];
    }
}
