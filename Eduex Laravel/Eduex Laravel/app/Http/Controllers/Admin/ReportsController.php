<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reports Controller
 *
 * This controller handles the management of reports.
 *
 * @package App\Http\Controllers\Admin
 */
class ReportsController extends Controller
{
    /**
     * Display the reports index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request): View
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $startOfPreviousMonth = $startOfMonth->copy()->subMonth();
        $endOfPreviousMonth = $startOfPreviousMonth->copy()->endOfMonth();

        $completedPaymentsQuery = Payment::query()->where('status', Payment::STATUS_COMPLETED);

        $financials = [
            'total_revenue' => (clone $completedPaymentsQuery)->sum('amount'),
            'total_instructor' => (clone $completedPaymentsQuery)->sum('instructor_earning'),
            'total_platform' => (clone $completedPaymentsQuery)->sum('platform_commission'),
            'monthly_revenue' => (clone $completedPaymentsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('amount'),
            'monthly_instructor' => (clone $completedPaymentsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('instructor_earning'),
            'monthly_platform' => (clone $completedPaymentsQuery)->whereBetween('created_at', [$startOfMonth, $endOfMonth])->sum('platform_commission'),
            'previous_month_revenue' => (clone $completedPaymentsQuery)->whereBetween('created_at', [$startOfPreviousMonth, $endOfPreviousMonth])->sum('amount'),
        ];

        $financials['revenue_growth'] = $financials['previous_month_revenue'] > 0
            ? (($financials['monthly_revenue'] - $financials['previous_month_revenue']) / $financials['previous_month_revenue']) * 100
            : null;

        $enrollmentStats = [
            'total_enrollments' => Enrollment::count(),
            'completed_enrollments' => Enrollment::completed()->count(),
            'new_enrollments_month' => Enrollment::whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
        ];

        $enrollmentStats['completion_rate'] = $enrollmentStats['total_enrollments'] > 0
            ? ($enrollmentStats['completed_enrollments'] / $enrollmentStats['total_enrollments']) * 100
            : 0;

        $studentStats = [
            'total_students' => User::where('role', 'student')->count(),
            'new_students_month' => User::where('role', 'student')->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count(),
            'total_instructors' => User::where('role', 'instructor')->count(),
            'approved_instructors' => User::where('role', 'instructor')->where('is_approved', true)->count(),
        ];

        $topCourses = $this->topCoursesReport();
        $topInstructors = $this->topInstructorsReport();
        $monthlyTrend = $this->monthlyTrendReport(6);

        $monthlyTrendChart = [
            'labels' => $monthlyTrend->pluck('label')->values()->all(),
            'revenue' => $monthlyTrend->pluck('revenue')->values()->all(),
            'enrollments' => $monthlyTrend->pluck('enrollments')->values()->all(),
            'completions' => $monthlyTrend->pluck('completions')->values()->all(),
        ];

        $topCourseChart = [
            'labels' => $topCourses->pluck('course_title')->values()->all(),
            'revenue' => $topCourses->pluck('total_revenue')->values()->all(),
            'sales' => $topCourses->pluck('total_sales')->values()->all(),
        ];

        $topInstructorChart = [
            'labels' => $topInstructors->pluck('instructor_name')->values()->all(),
            'earnings' => $topInstructors->pluck('total_earning')->values()->all(),
        ];

        return view('admin.reports.index', [
            'financials' => $financials,
            'enrollmentStats' => $enrollmentStats,
            'studentStats' => $studentStats,
            'topCourses' => $topCourses,
            'topInstructors' => $topInstructors,
            'monthlyTrend' => $monthlyTrend,
            'monthlyTrendChart' => $monthlyTrendChart,
            'topCourseChart' => $topCourseChart,
            'topInstructorChart' => $topInstructorChart,
        ]);
    }

    /**
     * Get the top courses report
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function topCoursesReport(int $limit = 5): Collection
    {
        $rows = Payment::query()
            ->select([
                'courses.id as course_id',
                'courses.title as course_title',
                DB::raw('SUM(payments.amount) as total_revenue'),
                DB::raw('COUNT(payments.id) as total_sales'),
                DB::raw('SUM(CASE WHEN enrollments.status = "' . Enrollment::STATUS_COMPLETED . '" THEN 1 ELSE 0 END) as total_completions'),
            ])
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->where('payments.status', Payment::STATUS_COMPLETED)
            ->groupBy('courses.id', 'courses.title')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) {
            return [
                'course_id' => (int) $row->course_id,
                'course_title' => $row->course_title,
                'total_revenue' => (float) $row->total_revenue,
                'total_sales' => (int) $row->total_sales,
                'total_completions' => (int) $row->total_completions,
            ];
        });
    }

    /**
     * Get the top instructors report
     *
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    private function topInstructorsReport(int $limit = 5): Collection
    {
        $rows = Payment::query()
            ->select([
                'courses.instructor_id',
                DB::raw('SUM(payments.instructor_earning) as total_earning'),
                DB::raw('COUNT(payments.id) as total_sales'),
            ])
            ->join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->join('courses', 'enrollments.course_id', '=', 'courses.id')
            ->where('payments.status', Payment::STATUS_COMPLETED)
            ->whereNotNull('courses.instructor_id')
            ->groupBy('courses.instructor_id')
            ->orderByDesc('total_earning')
            ->limit($limit)
            ->get();

        $instructors = User::whereIn('id', $rows->pluck('instructor_id')->filter()->all())
            ->get()
            ->keyBy('id');

        return $rows->map(function ($row) use ($instructors) {
            $instructor = $instructors->get($row->instructor_id);

            return [
                'instructor_id' => (int) $row->instructor_id,
                'instructor_name' => $instructor?->name ?? 'Unknown Instructor',
                'total_earning' => (float) $row->total_earning,
                'total_sales' => (int) $row->total_sales,
            ];
        });
    }

    /**
     * Get the monthly trend report
     *
     * @param int $months
     * @return \Illuminate\Support\Collection
     */
    private function monthlyTrendReport(int $months = 6): Collection
    {
        $data = collect();
        $months = max($months, 1);

        for ($i = $months - 1; $i >= 0; $i--) {
            $period = Carbon::now()->subMonths($i);
            $start = $period->copy()->startOfMonth();
            $end = $period->copy()->endOfMonth();

            $data->push([
                'label' => $period->format('M Y'),
                'revenue' => (float) Payment::where('status', Payment::STATUS_COMPLETED)
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('amount'),
                'enrollments' => Enrollment::whereBetween('created_at', [$start, $end])->count(),
                'completions' => Enrollment::completed()->whereBetween('updated_at', [$start, $end])->count(),
            ]);
        }

        return $data;
    }
}
