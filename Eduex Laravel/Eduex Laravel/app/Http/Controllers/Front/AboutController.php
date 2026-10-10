<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

/**
 * About Controller
 *
 * This controller handles the about page functionality.
 *
 * @package App\Http\Controllers\Front
 */
class AboutController extends Controller
{
    /**
     * Display the about page
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(): View
    {
        $meta = frontend_meta('about', null, []);

        $totalCourses = Course::count();
        $approvedInstructors = User::where('role', 'instructor')->where('is_approved', true)->count();
        $totalStudents = User::where('role', 'student')->count();
        $totalEnrollments = Enrollment::whereIn('status', [
            Enrollment::STATUS_APPROVED,
            Enrollment::STATUS_COMPLETED,
        ])->count();
        $totalReviews = Review::where('is_approved', true)->count();

        $topCategories = CourseCategory::query()
            ->where('is_active', true)
            ->withCount('courses')
            ->orderByDesc('courses_count')
            ->take(6)
            ->get()
            ->transform(function (CourseCategory $category) {
                $category->setAttribute('description_excerpt', Str::limit((string) $category->description, 90));

                return $category;
            });

        $featuredInstructors = User::query()
            ->where('role', 'instructor')
            ->where('is_approved', true)
            ->withCount('courses')
            ->with(['courses' => function ($query) {
                $query->withCount(['enrollments as students_count' => function ($enrollmentQuery) {
                    $enrollmentQuery->whereIn('status', [
                        Enrollment::STATUS_APPROVED,
                        Enrollment::STATUS_COMPLETED,
                    ]);
                }]);
            }])
            ->orderByDesc('courses_count')
            ->take(4)
            ->get()
            ->transform(function (User $instructor) {
                $instructor->setAttribute('students_count', $instructor->courses->sum('students_count'));

                return $instructor;
            });

        $recentReviews = Review::query()
            ->with(['user:id,name,profile_photo', 'course:id,title'])
            ->where('is_approved', true)
            ->latest()
            ->take(3)
            ->get()
            ->transform(function (Review $review) {
                $review->setAttribute('comment_excerpt', Str::limit((string) $review->comment, 180));

                return $review;
            });

        $heroSettings = frontend_setting('pages.about.hero', []);
        $featureSettings = frontend_setting('pages.about.features', []);
        $highlightSettings = frontend_setting('pages.about.highlights', []);
        $ctaSettings = frontend_setting('pages.about.cta', []);

        return view('about.index', [
            'pageTitle' => $meta['title'] ?? 'About ' . site_name(),
            'metaDescription' => $meta['description'] ?? null,
            'stats' => [
                'courses' => $totalCourses,
                'instructors' => $approvedInstructors,
                'students' => $totalStudents,
                'enrollments' => $totalEnrollments,
                'reviews' => $totalReviews,
            ],
            'topCategories' => $topCategories,
            'featuredInstructors' => $featuredInstructors,
            'recentReviews' => $recentReviews,
            'heroSettings' => $heroSettings,
            'featureSettings' => $featureSettings,
            'highlightSettings' => $highlightSettings,
            'ctaSettings' => $ctaSettings,
        ]);
    }
}
