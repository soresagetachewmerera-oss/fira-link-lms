<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\BlogPost;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Home Controller
 *
 * This controller handles the home page functionality.
 *
 * @package App\Http\Controllers\Front
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        $meta = frontend_meta('home', null, []);

        $stats = [
            'courses' => Course::query()
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->count(),
            'students' => User::query()
                ->where('role', 'student')
                ->count(),
            'instructors' => User::query()
                ->where('role', 'instructor')
                ->where('is_approved', true)
                ->count(),
            'enrollments' => Enrollment::query()
                ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
                ->count(),
        ];

        $defaultCategoryIcons = [
            'fa-solid fa-graduation-cap',
            'fa-solid fa-palette',
            'fa-solid fa-laptop-code',
            'fa-solid fa-chart-line',
            'fa-solid fa-language',
            'fa-solid fa-microchip',
        ];

        $topCategories = CourseCategory::query()
            ->where('is_active', true)
            ->withCount(['courses' => function ($query) {
                $query->where('status', 'published')
                    ->where('visibility', 'public');
            }])
            ->orderByDesc('courses_count')
            ->take(6)
            ->get()
            ->map(function (CourseCategory $category, int $index) use ($defaultCategoryIcons) {
                $iconClass = $category->icon;
                if (blank($iconClass)) {
                    $iconClass = $defaultCategoryIcons[$index % count($defaultCategoryIcons)] ?? 'fa-solid fa-book';
                }
                return [
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description ?? 'Discover curated courses created by industry experts.',
                    'courses_count' => $category->courses_count,
                    'icon_class' => $iconClass,
                ];
            });

        $featuredCourses = Course::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->with([
                'instructor:id,name,profile_photo',
                'categoryRelation:id,name,slug',
            ])
            ->withAvg('reviews as average_rating', 'rating')
            ->withCount([
                'reviews',
                'enrollments as students_count' => function ($query) {
                    $query->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED]);
                },
            ])
            ->latest()
            ->take(6)
            ->get()
            ->map(function (Course $course) {
                $course->setAttribute('display_image', $this->resolveMedia($course->featured_image, 'assets/front/img/home-1/courses/courses-01.jpg'));
                $course->setAttribute('instructor_avatar', $this->resolveMedia(optional($course->instructor)->profile_photo, 'assets/front/img/home-1/courses/client-01.png'));
                $course->setAttribute('display_price', $this->resolvePrice($course));
                $course->setAttribute('is_free', $course->pricing_model === 'free' || $course->getAttribute('display_price') <= 0);
                $course->setAttribute('average_rating', $this->formatRating($course->average_rating));
                $course->setAttribute('display_category', $course->categoryRelation?->name ?? $course->category ?? 'General');

                return $course;
            });

        $featuredInstructors = User::query()
            ->where('role', 'instructor')
            ->where('is_approved', true)
            ->withCount(['courses' => function ($query) {
                $query->where('status', 'published')->where('visibility', 'public');
            }])
            ->with(['courses' => function ($courseQuery) {
                $courseQuery
                    ->where('status', 'published')
                    ->where('visibility', 'public')
                    ->withCount(['enrollments as students_count' => function ($enrollmentQuery) {
                        $enrollmentQuery->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED]);
                    }])
                    ->withAvg('reviews as reviews_avg_rating', 'rating');
            }])
            ->orderByDesc('courses_count')
            ->take(4)
            ->get()
            ->map(function (User $instructor) {
                $students = $instructor->courses->sum('students_count');
                $rating = $this->formatRating($instructor->courses->avg('reviews_avg_rating'));

                return [
                    'id' => $instructor->id,
                    'name' => $instructor->name,
                    'headline' => $instructor->specialization ?? 'Instructor',
                    'avatar' => $this->resolveMedia($instructor->profile_photo, 'assets/front/img/home-1/team/team-01.jpg'),
                    'courses_count' => $instructor->courses_count,
                    'students_count' => $students,
                    'rating' => $rating,
                ];
            });

        $testimonials = Review::query()
            ->with(['user:id,name,profile_photo', 'course:id,title'])
            ->where('is_approved', true)
            ->latest()
            ->take(8)
            ->get()
            ->map(function (Review $review) {
                $comment = $review->comment ?? $review->body ?? $review->title ?? '';
                return [
                    'rating' => (int) ($review->rating ?? 0),
                    'comment' => Str::limit(strip_tags($comment), 260),
                    'student_name' => optional($review->user)->name ?? 'Learner',
                    'student_avatar' => $this->resolveMedia(optional($review->user)->profile_photo, 'assets/front/img/home-1/testimonial/client-01.png'),
                    'course_title' => optional($review->course)->title,
                    'date' => optional($review->created_at)->format('M d, Y'),
                ];
            });

        $blogPosts = BlogPost::published()
            ->with(['categoryRelation'])
            ->withCount('approvedComments')
            ->latest('published_at')
            ->take(4)
            ->get()
            ->map(function (BlogPost $post) {
                return [
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'category' => $post->categoryRelation?->name ?? $post->category ?? 'Blog',
                    'published_at' => optional($post->published_at)->format('d M, Y'),
                    'comments_count' => $post->approved_comments_count ?? 0,
                    'image' => $this->resolveMedia($post->featured_image, 'assets/front/img/home-1/news/news-01.jpg'),
                    'excerpt' => Str::limit(strip_tags($post->excerpt ?? $post->content ?? ''), 120),
                ];
            });

        $upcomingEvents = Event::query()
            ->published()
            ->orderBy('start_date')
            ->take(3)
            ->get();

        $featuredBundles = \App\Models\Bundle::where('status', 'active')
            ->where('approval_status', 'approved')
            ->with(['vendor:id,name,profile_photo', 'courses'])
            ->latest()
            ->take(3)
            ->get();

        $heroSettings = frontend_setting('pages.home.hero', []);
        $hero = [
            'preheading' => $heroSettings['preheading'] ?? branding_settings('tagline', 'Welcome to ' . site_name()),
            'heading' => $heroSettings['heading'] ?? 'Learn without limits from top instructors',
            'description' => $heroSettings['description'] ?? 'Join thousands of learners advancing their skills with curated courses, hands-on projects, and expert mentorship.',
            'cta_url' => $heroSettings['cta_url'] ?? route('courses'),
            'cta_label' => $heroSettings['cta_label'] ?? 'Browse Courses',
        ];

        $testimonialSettings = frontend_setting('pages.home.testimonial', []);
        $blogSettings = frontend_setting('pages.home.blog', []);
        $aboutSettings = frontend_setting('pages.home.about', []);
        $discountSettings = frontend_setting('pages.home.discount', []);
        $featureSettings = frontend_setting('pages.home.features', []);
        $ctaSettings = frontend_setting('pages.home.cta', []);

        $themeService = app(\App\Services\ThemeService::class);

        return view($themeService->resolveHomeView(), [
            'pageTitle' => $meta['title'] ?? site_name(),
            'metaDescription' => $meta['description'] ?? site_name() . ' - ' . site_tagline(),
            'hero' => $hero,
            'stats' => $stats,
            'topCategories' => $topCategories,
            'featuredCourses' => $featuredCourses,
            'featuredInstructors' => $featuredInstructors,
            'featuredBundles' => $featuredBundles,
            'testimonials' => $testimonials,
            'testimonialSettings' => $testimonialSettings,
            'blogPosts' => $blogPosts,
            'blogSettings' => $blogSettings,
            'aboutSettings' => $aboutSettings,
            'discountSettings' => $discountSettings,
            'featureSettings' => $featureSettings,
            'ctaSettings' => $ctaSettings,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    private function resolveMedia(?string $path, string $fallback): string
    {
        if (blank($path)) {
            return asset($fallback);
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $normalized = ltrim(str_replace(['storage/', 'public/'], '', $path), '/');

        if (Storage::disk('public')->exists($normalized)) {
            return Storage::url($normalized);
        }

        if (Str::startsWith($path, 'assets/')) {
            return asset($path);
        }

        return asset($fallback);
    }

    private function resolvePrice(Course $course): float
    {
        if ($course->pricing_model === 'free') {
            return 0.0;
        }

        if ($course->sale_price && $course->sale_price > 0) {
            return (float) $course->sale_price;
        }

        return (float) ($course->regular_price ?? 0);
    }

    private function formatRating(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return round($value, 1);
    }
}
