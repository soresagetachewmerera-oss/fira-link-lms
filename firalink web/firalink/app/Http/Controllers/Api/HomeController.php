<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Get all homepage data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // 1. Active Banners
        $banners = Banner::active()->ordered()->get();

        // 2. Top Categories (e.g., active, ordered by ID or custom order if available)
        $categories = CourseCategory::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->take(8)
            ->get();

        // 3. Popular Courses (most enrolled)
        $popularCourses = Course::where('status', 'published')
            ->withCount(['enrollments', 'lessons', 'reviews'])
            ->orderBy('enrollments_count', 'desc')
            ->take(5)
            ->with(['instructor:id,name', 'categoryRelation:id,name'])
            ->get();

        // 4. AI Suggestions
        $aiSuggestions = Course::where('status', 'published')
            ->where(function ($q) {
                $q->where('title', 'like', '%AI%')
                    ->orWhere('title', 'like', '%Artificial Intelligence%')
                    ->orWhere('tags', 'like', '%AI%')
                    ->orWhere('tags', 'like', '%Artificial Intelligence%');
            })
            ->withCount(['enrollments', 'lessons', 'reviews'])
            ->take(5)
            ->with(['instructor:id,name', 'categoryRelation:id,name'])
            ->get();

        // Fallback if no specific AI courses found
        if ($aiSuggestions->isEmpty()) {
            $aiSuggestions = Course::where('status', 'published')
                ->withCount(['reviews', 'enrollments', 'lessons'])
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->with(['instructor:id,name', 'categoryRelation:id,name'])
                ->get();
        }

        // 5. Most Recent Courses
        $recentCourses = Course::where('status', 'published')
            ->withCount(['enrollments', 'lessons', 'reviews'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->with(['instructor:id,name', 'categoryRelation:id,name'])
            ->get();

        $upcomingLiveData = null;
        $user = $request->user('sanctum');
        $continueLearning = null;
        $userContext = [
            'authenticated' => false,
            'role' => 'visitor',
            'persona' => 'visitor',
            'capabilities' => [],
        ];
        if ($user) {
            $hasSellerProfile = $user->sellerProfile()->exists();
            $hasBuyerActivity = $user->marketplaceRfqs()->exists();
            $persona = $user->isAdmin()
                ? 'admin'
                : ($user->isInstructor()
                    ? 'instructor'
                    : ($hasSellerProfile
                        ? 'seller'
                        : ($hasBuyerActivity ? 'buyer' : 'learner')));

            $userContext = [
                'authenticated' => true,
                'role' => $user->role,
                'persona' => $persona,
                'capabilities' => [
                    'learn' => $user->isStudent() || $user->isAdmin(),
                    'teach' => $user->isInstructor() || $user->isAdmin(),
                    'manage_platform' => $user->isAdmin(),
                    'sell_coffee' => $hasSellerProfile || $user->isAdmin(),
                    'post_listings' => $user->canPostMarketplaceListing(),
                    'buy_coffee' => true,
                ],
            ];

            if ($user->isStudent()) {
                $enrollment = Enrollment::query()
                    ->where('user_id', $user->id)
                    ->where('status', Enrollment::STATUS_APPROVED)
                    ->with(['course' => fn ($query) => $query->withCount('lessons')])
                    ->withCount([
                        'lessonProgress as completed_lessons_count' => fn ($query) =>
                            $query->where('is_completed', true),
                    ])
                    ->latest('updated_at')
                    ->first();

                if ($enrollment?->course) {
                    $totalLessons = max(1, (int) $enrollment->course->lessons_count);
                    $completedLessons = min(
                        $totalLessons,
                        (int) $enrollment->completed_lessons_count
                    );
                    $continueLearning = [
                        'course_id' => $enrollment->course_id,
                        'title' => $enrollment->course->title,
                        'current_lesson' => min($totalLessons, $completedLessons + 1),
                        'total_lessons' => $totalLessons,
                        'progress' => $completedLessons / $totalLessons,
                    ];
                }
            }

            $liveClassService = app(\App\Services\LiveClassService::class);
            $upcomingLiveClass = $liveClassService->getUpcomingForStudent($user);
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
        }

        // The old "Market pulse" strip read coffee_market_benchmarks, whose seeded rows are not sourced.
        // Prices now come only from /api/market/overview (source, source time, freshness per value).
        // Always empty, so older app builds hide the strip instead of showing those rows.
        $marketItems = [];

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
                'categories' => $categories,
                'popular_courses' => $popularCourses,
                'ai_suggestions' => $aiSuggestions,
                'recent_courses' => $recentCourses,
                'upcoming_live_class' => $upcomingLiveData,
                'user_context' => $userContext,
                'continue_learning' => $continueLearning,
                'market_items' => $marketItems,
            ]
        ]);
    }

    /**
     * Get paginated recent courses.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentCourses(Request $request)
    {
        $courses = Course::where('status', 'published')
            ->withCount(['enrollments', 'lessons', 'reviews'])
            ->orderBy('created_at', 'desc')
            ->with(['instructor:id,name', 'categoryRelation:id,name'])
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $courses
        ]);
    }
}
