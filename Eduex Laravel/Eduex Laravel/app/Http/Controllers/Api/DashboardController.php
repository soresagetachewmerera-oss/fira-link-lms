<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Payment;
use App\Services\LiveClassService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(protected LiveClassService $liveClassService) {}
    /**
     * Get student dashboard statistics.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Enrollment stats
        $totalCourses = Enrollment::where('user_id', $user->id)->count();
        $completedCourses = Enrollment::where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_COMPLETED)
            ->count();
        $inProgressCourses = Enrollment::where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_APPROVED) // approved but not completed
            ->count();

        // 2. Total spending
        $totalSpent = Payment::join('enrollments', 'payments.enrollment_id', '=', 'enrollments.id')
            ->where('enrollments.user_id', $user->id)
            ->where('payments.status', 'completed')
            ->sum('payments.amount');

        // 3. Learning activity (last 30 days)
        $activityData = LessonProgress::where('user_id', $user->id)
            ->where('is_completed', true)
            ->where('completed_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(completed_at) as date'),
                DB::raw('COUNT(*) as lessons_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        // Ensure we have 30 days even if data is missing
        $days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $days->push(now()->subDays($i)->format('Y-m-d'));
        }

        $formattedActivityData = $days->map(function ($date) use ($activityData) {
            $found = $activityData->firstWhere('date', $date);
            return [
                'month' => date('d M', strtotime($date)), // Using 'month' key to maintain compatibility while label is day
                'hours' => $found ? $found->lessons_count : 0,
            ];
        });

        // 4. Continue learning (latest enrollments with progress)
        $continueLearning = Enrollment::where('user_id', $user->id)
            ->where('status', Enrollment::STATUS_APPROVED)
            ->with([
                'course' => function ($q) {
                    $q->withCount('lessons');
                }
            ])
            ->with([
                'lessonProgress' => function ($q) {
                    $q->where('is_completed', true);
                }
            ])
            ->latest()
            ->take(3)
            ->get()
            ->map(function ($enrollment) {
                $totalLessons = $enrollment->course->lessons_count ?: 1;
                $completedLessons = $enrollment->lessonProgress->count();
                $progress = $completedLessons / $totalLessons;

                return [
                    'course_id' => $enrollment->course_id,
                    'title' => $enrollment->course->title,
                    'progress' => $progress,
                    'image' => $enrollment->course->thumbnail,
                    'next_lesson' => 'Continue where you left off', // placeholder
                ];
            });

        // 5. Upcoming live class (nearest enrolled)
        $upcomingLiveClass = $this->liveClassService->getUpcomingForStudent($user);
        $upcomingLiveData  = null;
        if ($upcomingLiveClass) {
            $upcomingLiveData = [
                'id'                => $upcomingLiveClass->id,
                'title'             => $upcomingLiveClass->title,
                'course_id'         => $upcomingLiveClass->course_id,
                'course_title'      => $upcomingLiveClass->course?->title,
                'course_thumbnail'  => $upcomingLiveClass->course?->thumbnail,
                'join_url'          => $upcomingLiveClass->join_url,
                'scheduled_at'      => $upcomingLiveClass->scheduled_at->toIso8601String(),
                'duration_minutes'  => $upcomingLiveClass->duration_minutes,
                'status'            => $upcomingLiveClass->status,
                'minutes_until'     => $upcomingLiveClass->minutes_until,
                'is_currently_live' => $upcomingLiveClass->is_currently_live,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => [
                    'total_courses'      => $totalCourses,
                    'completed_courses'  => $completedCourses,
                    'in_progress_courses' => $inProgressCourses,
                    'total_spent'        => number_format($totalSpent, 2),
                ],
                'activity'            => $formattedActivityData,
                'continue_learning'   => $continueLearning,
                'upcoming_live_class' => $upcomingLiveData,
            ]
        ]);
    }
}
