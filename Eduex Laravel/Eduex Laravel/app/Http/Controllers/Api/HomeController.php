<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Course;
use App\Models\CourseCategory;
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
        if ($user) {
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

        return response()->json([
            'success' => true,
            'data' => [
                'banners' => $banners,
                'categories' => $categories,
                'popular_courses' => $popularCourses,
                'ai_suggestions' => $aiSuggestions,
                'recent_courses' => $recentCourses,
                'upcoming_live_class' => $upcomingLiveData,
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
