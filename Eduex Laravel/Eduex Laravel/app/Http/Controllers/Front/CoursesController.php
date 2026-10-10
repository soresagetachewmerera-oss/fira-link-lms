<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Courses Controller
 *
 * This controller handles the courses functionality.
 *
 * @package App\Http\Controllers\Front
 */
class CoursesController extends Controller
{
    /**
     * Display all published courses
     */
    public function index(Request $request)
    {
        $query = Course::with(['instructor', 'topics.lessons'])
            ->where('status', 'published')
            ->where('visibility', 'public');

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        if ($request->has('language') && $request->language) {
            $query->where('language', $request->language);
        }

        if ($request->has('instructor') && $request->instructor) {
            $query->whereHas('instructor', function ($q) use ($request) {
                $q->where('id', $request->instructor);
            });
        }

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('tags', 'like', "%{$search}%");
            });
        }

        if ($request->has('price') && $request->price) {
            if ($request->price === 'free') {
                $query->where('pricing_model', 'free');
            } elseif ($request->price === 'paid') {
                $query->where('pricing_model', 'paid');
            }
        }

        if ($request->filled('rating')) {
            $desiredRating = (int) $request->rating;

            $query->where(function ($q) use ($desiredRating) {
                $q->whereHas('reviews', function ($reviewQuery) use ($desiredRating) {
                    $reviewQuery->select('course_id')
                        ->groupBy('course_id')
                        ->havingRaw('ROUND(AVG(rating)) = ?', [$desiredRating]);
                });
            });
        }

        $courses = $query->orderBy('created_at', 'desc')->paginate(12);

        $courses->load('reviews');
        $courses->getCollection()->transform(function (Course $course) {
            $reviews = $course->reviews ?? collect();
            $averageRating = $reviews->count() ? round($reviews->avg('rating'), 1) : null;
            $course->setAttribute('average_rating', $averageRating);
            $course->setAttribute('rating_count', $reviews->count());

            return $course;
        });

        $meta = frontend_meta('courses', null, []);

        $categories = Course::where('status', 'published')
            ->whereNotNull('category')
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->get();

        $languages = Course::where('status', 'published')
            ->whereNotNull('language')
            ->selectRaw('language, COUNT(*) as count')
            ->groupBy('language')
            ->get();

        $instructors = User::where('role', 'instructor')
            ->where('is_approved', true)
            ->whereHas('courses', function ($q) {
                $q->where('status', 'published');
            })
            ->select('id', 'name')
            ->get();

        $ratingCounts = Course::where('status', 'published')
            ->where('visibility', 'public')
            ->with('reviews')
            ->get()
            ->map(function (Course $course) {
                $reviews = $course->reviews ?? collect();
                $rounded = round($reviews->avg('rating'));

                return $rounded > 0 ? $rounded : null;
            })
            ->filter()
            ->countBy()
            ->map(function ($count, $rating) {
                return (int) $count;
            })
            ->toArray();

        if ($request->ajax() || $request->expectsJson()) {
            $coursesHtml = view('courses.partials.course-list', compact('courses'))->render();
            $paginationHtml = view('courses.partials.pagination', compact('courses'))->render();

            return response()->json([
                'success' => true,
                'courses_html' => $coursesHtml,
                'pagination_html' => $paginationHtml,
                'total' => $courses->total(),
            ]);
        }

        return view('courses.index', [
            'pageTitle' => $meta['title'] ?? 'Courses',
            'metaDescription' => $meta['description'] ?? null,
            'courses' => $courses,
            'categories' => $categories,
            'languages' => $languages,
            'instructors' => $instructors,
            'ratingCounts' => $ratingCounts,
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
            $courseStartDate = $course->schedule_date;
        }

        $isEnrolled = false;
        $userReview = null;
        $canReview = false;

        if (Auth::check()) {
            $isEnrolled = Enrollment::where('user_id', Auth::id())
                ->where('course_id', $course->id)
                ->whereIn('status', ['approved', 'completed'])
                ->exists();

            if ($isEnrolled) {
                $canReview = true;
                $userReview = Review::where('course_id', $course->id)
                    ->where('user_id', Auth::id())
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

        $metaDescription = $course->meta_description
            ?? Str::limit(strip_tags((string) ($course->description ?? $course->summary ?? '')), 160);

        return view('courses.show', [
            'pageTitle' => $course->title,
            'metaDescription' => $metaDescription,
            'course' => $course,
            'lessonCount' => $lessonCount,
            'totalDuration' => $totalDuration,
            'courseStartDate' => $courseStartDate,
            'isEnrolled' => $isEnrolled,
            'canReview' => $canReview,
            'userReview' => $userReview,
            'reviews' => $reviews,
            'totalReviews' => $totalReviews,
            'averageRating' => $averageRating,
            'ratingDistribution' => $ratingDistribution,
        ]);
    }
}
