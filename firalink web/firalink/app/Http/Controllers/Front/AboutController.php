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

        $brandName = config('seo.brand_name', 'Fira Link Academy');
        $parentName = config('seo.parent_organization', 'Fira Link Business Solution');

        $heroSettings = array_merge([
            'subheading' => 'About ' . $brandName,
            'heading' => 'Practical International Trade & Business Education for East Africa',
            'description' => "{$brandName} is the specialized digital learning and capacity-building initiative of {$parentName}, headquartered in Addis Ababa, Ethiopia. We empower entrepreneurs, commercial farmers, exporters, and logistics managers with real-world trade competencies—from Ethiopian Electronic Single Window (eSW) filings and customs tariff classification to coffee origin sourcing and multimodal transit via the Port of Djibouti.",
            'author_name' => $parentName,
            'author_title' => 'Trade Advisory & Business Development',
            'cta_url' => route('programs'),
            'cta_label' => 'Explore Programs',
        ], frontend_setting('pages.about.hero', []));

        $featureSettings = array_merge([
            'title' => 'Why Learn With Us',
            'subtitle' => 'Bridging Classroom Theory With Operational Execution',
            'description' => 'Our curriculum is designed alongside licensed customs clearing agents, freight forwarders, and commercial bankers to ensure every lesson translates directly into successful import-export operations in Ethiopia.',
            'items' => [
                ['icon' => 'fa-solid fa-check', 'text' => 'Direct alignment with National Bank of Ethiopia (NBE) & Ministry of Trade regulations.'],
                ['icon' => 'fa-solid fa-check', 'text' => 'Hands-on training in Electronic Single Window (eSW) and HS tariff code lookup.'],
                ['icon' => 'fa-solid fa-check', 'text' => 'Physical trade tours to coffee washing stations and logistics dry ports.'],
                ['icon' => 'fa-solid fa-check', 'text' => 'Free export calculation terminal with live ICO benchmark market data.'],
            ],
        ], frontend_setting('pages.about.features', []));

        $highlightSettings = frontend_setting('pages.about.highlights', []);
        $ctaSettings = array_merge([
            'heading' => 'Ready to Launch or Expand Your Trade Business?',
            'description' => 'Gain the practical tools, regulatory frameworks, and mentor guidance needed to trade confidently across Ethiopian borders.',
            'primary_url' => route('courses'),
            'primary_label' => 'View Available Courses',
            'secondary_url' => route('contact'),
            'secondary_label' => 'Contact Admissions',
        ], frontend_setting('pages.about.cta', []));

        $breadcrumbs = [
            ['name' => 'Home', 'url' => url('/')],
            ['name' => 'About', 'url' => url('/about')],
        ];

        return view('about.index', [
            'pageTitle' => 'About ' . $brandName . ' | Practical Trade & Business Education',
            'metaDescription' => 'Discover ' . $brandName . ', the digital learning initiative of ' . $parentName . ' providing practical import-export training, customs compliance, and business tools in Ethiopia.',
            'breadcrumbs' => $breadcrumbs,
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
