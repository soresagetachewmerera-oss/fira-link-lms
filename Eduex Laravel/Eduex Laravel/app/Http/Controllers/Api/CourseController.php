<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

use App\Models\CourseCategory;
use Illuminate\Support\Facades\DB;

class CourseController extends Controller
{
    /**
     * Display a listing of the courses.
     */
    public function index(Request $request)
    {
        $query = Course::with(['instructor', 'categoryRelation'])
            ->where('status', 'published')
            ->where('visibility', 'public');

        // Search
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where('title', 'like', "%{$search}%");
        }

        // Category Filter
        if ($request->has('categories')) {
            $categories = is_array($request->categories) ? $request->categories : explode(',', $request->categories);
            if (!empty($categories)) {
                $query->whereIn('course_category_id', $categories);
            }
        }

        // Price Filter
        if ($request->has('min_price')) {
            $query->where('regular_price', '>=', $request->min_price);
        }
        if ($request->has('max_price')) {
            $query->where('regular_price', '<=', $request->max_price);
        }

        // Duration Filter
        // We use withSum to get total duration.
        // Assuming duration is in minutes in the DB.
        if ($request->has('min_duration') || $request->has('max_duration')) {
            $query->withSum('lessons as total_duration', 'duration');

            if ($request->has('min_duration')) {
                // Input is hours, DB is minutes (assumed)
                $minMinutes = $request->min_duration * 60;
                $query->having('total_duration', '>=', $minMinutes);
            }
            if ($request->has('max_duration')) {
                $maxMinutes = $request->max_duration * 60;
                $query->having('total_duration', '<=', $maxMinutes);
            }
        }

        $courses = $query->paginate(10);
        return response()->json($courses);
    }

    public function categories()
    {
        $categories = CourseCategory::select('id', 'name', 'slug', 'icon')->get();
        return response()->json([
            'success' => true,
            'data' => $categories
        ]);
    }
    /**
     * Display course details
     */
    public function show($id)
    {
        $course = Course::with([
            'instructor',
            'topics.lessons',
            'topics.quizzes.questions',
            'topics.assignments.files',
            'reviews.user',
            'reviews.replies.user'
        ])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->findOrFail($id);

        $lessonCount = $course->topics->sum(function ($topic) {
            return $topic->lessons->count();
        });

        $totalDuration = $course->topics->sum(function ($topic) {
            return $topic->lessons->sum(function ($lesson) {
                return $lesson->duration ?? 0;
            });
        });

        $courseStartDate = null;
        if ($course->schedule_enabled && $course->schedule_date && $course->schedule_date->isFuture()) {
            $courseStartDate = $course->schedule_date->format('Y-m-d H:i:s');
        }

        $isEnrolled = false;
        $userReview = null;
        $canReview = false;

        if (Auth::guard('sanctum')->check()) {
            $userId = Auth::guard('sanctum')->id();
            $isEnrolled = Enrollment::where('user_id', $userId)
                ->where('course_id', $course->id)
                ->whereIn('status', ['approved', 'completed'])
                ->exists();

            if ($isEnrolled) {
                $canReview = true;
                $userReview = Review::where('course_id', $course->id)
                    ->where('user_id', $userId)
                    ->first();
            }
        }

        $reviews = $course->reviews;
        $totalReviews = $reviews->count();
        $averageRating = $totalReviews > 0 ? round($reviews->avg('rating'), 1) : 0;

        $ratingDistribution = [
            5 => $reviews->where('rating', 5)->count(),
            4 => $reviews->where('rating', 4)->count(),
            3 => $reviews->where('rating', 3)->count(),
            2 => $reviews->where('rating', 2)->count(),
            1 => $reviews->where('rating', 1)->count(),
        ];

        $relatedCourses = Course::with(['instructor'])
            ->where('course_category_id', $course->course_category_id)
            ->where('id', '!=', $course->id)
            ->where('status', 'published')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'course' => $course,
                'lesson_count' => $lessonCount,
                'total_duration' => $totalDuration,
                'course_start_date' => $courseStartDate,
                'is_enrolled' => $isEnrolled,
                'can_review' => $canReview,
                'user_review' => $userReview,
                'total_reviews' => $totalReviews,
                'average_rating' => $averageRating,
                'rating_distribution' => $ratingDistribution,
                'related_courses' => $relatedCourses,
            ]
        ]);
    }
    /**
     * Get logged in user's enrolled courses
     */
    public function myCourses(Request $request)
    {
        $user = $request->user();
        $status = $request->input('status'); // 'inProgress' (mapped to 'approved') or 'completed'

        $query = Enrollment::where('user_id', $user->id)
            ->with([
                'course' => function ($q) {
                    $q->select('id', 'title', 'featured_image', 'regular_price', 'sale_price', 'is_live_course');
                    $q->withCount('lessons');
                    $q->withSum('lessons as total_duration', 'duration');
                },
                'lessonProgress' => function ($q) {
                    $q->where('is_completed', true);
                }
            ]);

        if ($status && $status === 'completed') {
            $query->where('status', 'completed');
        } elseif ($status && $status === 'inProgress') {
            // In our system 'approved' means actively enrolled/in-progress
            $query->where('status', 'approved');
        } else {
            // Default to showing all active/completed enrollments if no status specified
            $query->whereIn('status', ['approved', 'completed']);
        }

        $enrollments = $query->latest()->paginate(10);
        $items = $enrollments->getCollection();

        $data = $items->map(function ($enrollment) {
            $course = $enrollment->course;
            if (!$course)
                return null;

            $totalLessons = $course->lessons_count ?: 0;
            $completedLessons = $enrollment->lessonProgress->count();

            // Avoid division by zero
            $progress = $totalLessons > 0 ? ($completedLessons / $totalLessons) : 0;
            // Cap progress at 1.0
            $progress = min($progress, 1.0);

            // If enrollment is marked completed, force progress to 100%
            if ($enrollment->status === 'completed') {
                $progress = 1.0;
                $completedLessons = $totalLessons;
            }

            // Calculate duration
            $durationMinutes = $course->total_duration ?? 0;
            $hours = floor($durationMinutes / 60);
            $minutes = $durationMinutes % 60;
            $durationString = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";

            return [
                'id' => $course->id,
                'title' => $course->title,
                'progress' => $progress, // 0.0 to 1.0
                'status' => $enrollment->status === 'approved' ? 'inProgress' : 'completed',
                'lessonsCompleted' => $completedLessons,
                'totalLessons' => $totalLessons,
                'duration' => $durationString,
                'imageAsset' => $course->thumbnail, // The frontend currently expects 'assets/...' but should handle URLs
                'is_live_course' => (bool)$course->is_live_course,
                'enrollment_date' => $enrollment->created_at->format('Y-m-d'),
            ];
        })->filter()->values();

        // Reconstruct the paginated response with the transformed data
        $paginationData = $enrollments->toArray();
        $paginationData['data'] = $data;

        return response()->json([
            'success' => true,
            'data' => $paginationData
        ]);
    }
}
