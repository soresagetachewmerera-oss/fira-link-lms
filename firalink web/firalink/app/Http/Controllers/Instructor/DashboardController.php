<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Dashboard Controller
 *
 * This controller handles the dashboard functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class DashboardController extends Controller
{
    /**
     * Display the instructor dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $coursesQuery = $user->courses();

        $stats = [
            'courses' => (clone $coursesQuery)->count(),
            'students' => $this->countUniqueStudents($user->id),
            'average_rating' => $this->averageRating($user->id),
            'earnings' => $this->totalEarnings($user->id),
        ];

        $recentCourses = $user->courses()
            ->with(['topics.lessons:id,course_topic_id'])
            ->withCount([
                'enrollments as students_count' => fn($q) => $q->whereIn('status', [
                    Enrollment::STATUS_APPROVED,
                    Enrollment::STATUS_COMPLETED,
                ]),
            ])
            ->withAvg('reviews as average_rating', 'rating')
            ->latest('updated_at')
            ->take(3)
            ->get();

        $activities = $this->recentActivity($user->id);

        $statCards = [
            [
                'icon' => 'fa-solid fa-graduation-cap',
                'class' => 'stat-icon-1',
                'label' => 'Total Courses',
                'value' => number_format($stats['courses']),
            ],
            [
                'icon' => 'fa-solid fa-users',
                'class' => 'stat-icon-2',
                'label' => 'Unique Students',
                'value' => number_format($stats['students']),
            ],
            [
                'icon' => 'fa-solid fa-star',
                'class' => 'stat-icon-3',
                'label' => 'Average Rating',
                'value' => $stats['average_rating'] > 0 ? number_format($stats['average_rating'], 1) : '—',
            ],
            [
                'icon' => 'fa-solid fa-dollar-sign',
                'class' => 'stat-icon-4',
                'label' => 'Total Earnings',
                'value' => $stats['earnings'] > 0 ? currency_format($stats['earnings']) : currency_format(0),
            ],
        ];

        return view('instructor.dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recentCourses' => $recentCourses,
            'activities' => $activities,
            'statCards' => $statCards,
        ]);
    }

    private function countUniqueStudents(int $instructorId): int
    {
        return Enrollment::query()
            ->whereIn('status', [
                Enrollment::STATUS_APPROVED,
                Enrollment::STATUS_COMPLETED,
            ])
            ->whereHas('course', fn($q) => $q->where('instructor_id', $instructorId))
            ->distinct('user_id')
            ->count('user_id');
    }

    private function averageRating(int $instructorId): float
    {
        $rating = Review::query()
            ->where('is_approved', true)
            ->whereHas('course', fn($q) => $q->where('instructor_id', $instructorId))
            ->avg('rating');

        return $rating ? round($rating, 1) : 0.0;
    }

    private function totalEarnings(int $instructorId): float
    {
        $amount = Payment::query()
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereHas('enrollment.course', fn($q) => $q->where('instructor_id', $instructorId))
            ->sum('instructor_earning');

        return (float) $amount;
    }

    private function recentActivity(int $instructorId)
    {
        $enrollments = Enrollment::query()
            ->with(['user:id,name', 'course:id,title'])
            ->whereIn('status', [
                Enrollment::STATUS_APPROVED,
                Enrollment::STATUS_COMPLETED,
            ])
            ->whereHas('course', fn($q) => $q->where('instructor_id', $instructorId))
            ->latest('enrolled_at')
            ->take(5)
            ->get()
            ->map(fn($enrollment) => [
                'icon' => 'fa-solid fa-user-plus',
                'title' => 'New student enrolled',
                'description' => sprintf('%s enrolled in "%s"', $enrollment->user?->name ?? 'A student', $enrollment->course?->title ?? 'your course'),
                'timestamp' => $enrollment->enrolled_at ?? $enrollment->created_at,
            ]);

        $reviews = Review::query()
            ->with(['user:id,name', 'course:id,title'])
            ->where('is_approved', true)
            ->whereHas('course', fn($q) => $q->where('instructor_id', $instructorId))
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($review) => [
                'icon' => 'fa-solid fa-star',
                'title' => 'New review received',
                'description' => sprintf('%s rated "%s" %d stars', $review->user?->name ?? 'A learner', $review->course?->title ?? 'your course', (int) $review->rating),
                'timestamp' => $review->created_at,
            ]);

        return $enrollments
            ->merge($reviews)
            ->sortByDesc('timestamp')
            ->take(5)
            ->map(function ($activity) {
                $activity['relative_time'] = $activity['timestamp']
                    ? Carbon::parse($activity['timestamp'])->diffForHumans()
                    : null;

                return $activity;
            })
            ->values();
    }
}
