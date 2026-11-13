<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Review;
use App\Models\EventBooking;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Get instructor dashboard statistics and recent activity.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending approval.',
                'is_approved' => false
            ], 403);
        }

        $coursesQuery = $user->courses();

        $stats = [
            'total_courses'      => (clone $coursesQuery)->count(),
            'total_students'     => $this->countUniqueStudents($user->id),
            'average_rating'     => $this->averageRating($user->id),
            'total_earnings'     => $this->totalEarnings($user->id),
            'total_events'       => \App\Models\Event::whereHas('instructors', fn($q) => $q->where('users.id', $user->id))->count(),
            'total_enrollments'  => $this->totalEnrollments($user->id),
            'available_balance'  => $this->availableBalance($user->id),
            'currency'           => settings('general.currency_code', 'USD'),
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
            ->take(5)
            ->get()
            ->map(function ($course) {
                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'thumbnail' => $course->thumbnail,
                    'students_count' => $course->students_count,
                    'average_rating' => round($course->average_rating ?? 0, 1),
                    'price' => $course->sale_price ?? $course->regular_price ?? 0,
                    'pricing_model' => $course->pricing_model,
                    'lessons_count' => $course->topics->sum(fn($topic) => $topic->lessons->count()),
                ];
            });

        $activities = $this->recentActivity($user->id);
        $trends = $this->enrollmentTrends($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'stats' => $stats,
                'recent_courses' => $recentCourses,
                'activities' => $activities,
                'enrollment_trends' => $trends,
            ]
        ]);
    }

    private function enrollmentTrends(int $instructorId)
    {
        $days = collect();
        for ($i = 6; $i >= 0; $i--) {
            $days->push(Carbon::today()->subDays($i));
        }

        return $days->map(function ($date) use ($instructorId) {
            $count = Enrollment::query()
                ->whereDate('enrolled_at', $date)
                ->whereIn('status', [
                    Enrollment::STATUS_APPROVED,
                    Enrollment::STATUS_COMPLETED,
                ])
                ->whereHas('course', fn($q) => $q->where('instructor_id', $instructorId))
                ->count();

            return [
                'label' => $date->format('D'),
                'count' => $count,
                'is_today' => $date->isToday(),
            ];
        });
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

    private function totalEnrollments(int $instructorId): int
    {
        return Enrollment::query()
            ->whereIn('status', [
                Enrollment::STATUS_APPROVED,
                Enrollment::STATUS_COMPLETED,
            ])
            ->whereHas('course', fn($q) => $q->where('instructor_id', $instructorId))
            ->count();
    }

    private function availableBalance(int $instructorId): float
    {
        $totalEarned = Payment::query()
            ->where('status', Payment::STATUS_COMPLETED)
            ->whereHas('enrollment.course', fn($q) => $q->where('instructor_id', $instructorId))
            ->sum('instructor_earning');

        $withdrawn = \App\Models\InstructorWithdrawal::query()
            ->where('instructor_id', $instructorId)
            ->whereIn('status', ['completed', 'processing', 'pending'])
            ->sum('amount');

        return max(0, (float) $totalEarned - (float) $withdrawn);
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
                'type' => 'enrollment',
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
                'type' => 'review',
                'title' => 'New review received',
                'description' => sprintf('%s rated "%s" %d stars', $review->user?->name ?? 'A learner', $review->course?->title ?? 'your course', (int) $review->rating),
                'timestamp' => $review->created_at,
            ]);

        $bookings = EventBooking::query()
            ->with(['user:id,name', 'event:id,title'])
            ->where('status', EventBooking::STATUS_CONFIRMED)
            ->whereHas('event.instructors', fn($q) => $q->where('users.id', $instructorId))
            ->latest('booked_at')
            ->take(5)
            ->get()
            ->map(fn($booking) => [
                'type' => 'booking',
                'title' => 'New event booking',
                'description' => sprintf('%s booked a seat for "%s"', $booking->user?->name ?? $booking->full_name, $booking->event?->title ?? 'your event'),
                'timestamp' => $booking->booked_at,
            ]);

        return $enrollments
            ->merge($reviews)
            ->merge($bookings)
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
