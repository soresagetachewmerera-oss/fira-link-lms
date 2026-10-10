<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Instructor Controller
 *
 * This controller handles the instructor functionality.
 *
 * @package App\Http\Controllers\Front
 */
class InstructorController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->where('role', 'instructor')
            ->where('is_approved', true)
            ->withCount('courses')
            ->with(['courses' => function ($courseQuery) {
                $courseQuery
                    ->withCount(['enrollments as students_count' => function ($enrollmentQuery) {
                        $enrollmentQuery->whereIn('status', [
                            Enrollment::STATUS_APPROVED,
                            Enrollment::STATUS_COMPLETED,
                        ]);
                    }])
                    ->withCount(['reviews as reviews_count' => function ($reviewQuery) {
                        $reviewQuery->where('is_approved', true);
                    }])
                    ->withAvg('reviews as reviews_avg_rating', 'rating');
            }]);

        $instructors = $query->paginate(12)->through(function (User $instructor) {
            $courses = $instructor->courses;

            $students = $courses->sum('students_count');
            $rating = $this->formatRating($courses->avg('reviews_avg_rating'));
            $totalReviews = $courses->sum('reviews_count');

            $instructor->setAttribute('display_avatar', $this->resolveMedia($instructor->profile_photo, 'assets/front/img/home-1/team/team-01.jpg'));
            $instructor->setAttribute('headline', $instructor->specialization ?? 'Instructor');
            $instructor->setAttribute('stats', [
                'courses' => $instructor->courses_count,
                'students' => $students,
                'rating' => $rating,
                'reviews' => $totalReviews,
            ]);

            $featuredCourses = $courses->sortByDesc('students_count')->take(3)->map(function (Course $course) {
                $course->setAttribute('display_image', $this->resolveMedia($course->featured_image, 'assets/front/img/home-1/courses/courses-01.jpg'));
                $course->setAttribute('students_count', $course->students_count ?? 0);
                $course->setAttribute('reviews_count', $course->reviews_count ?? 0);
                $course->setAttribute('average_rating', $this->formatRating($course->reviews_avg_rating));

                return $course;
            });

            $instructor->setAttribute('featured_courses', $featuredCourses);

            return $instructor;
        });

        $meta = frontend_meta('instructors', null, []);

        return view('instructor.index', [
            'pageTitle' => $meta['title'] ?? 'Our Instructors',
            'metaDescription' => $meta['description'] ?? null,
            'instructors' => $instructors,
        ]);
    }

    public function show(Request $request, User $instructor): View
    {
        abort_unless($instructor->role === 'instructor' && $instructor->is_approved, 404);

        $instructor->loadCount('courses');
        $instructor->load(['courses' => function ($courseQuery) {
            $courseQuery
                ->withCount(['enrollments as students_count' => function ($enrollmentQuery) {
                    $enrollmentQuery->whereIn('status', [
                        Enrollment::STATUS_APPROVED,
                        Enrollment::STATUS_COMPLETED,
                    ]);
                }])
                ->withCount(['reviews as reviews_count' => function ($reviewQuery) {
                    $reviewQuery->where('is_approved', true);
                }])
                ->withAvg('reviews as reviews_avg_rating', 'rating');
        }]);

        $courses = $instructor->courses->sortByDesc('students_count')->values()->map(function (Course $course) {
            $course->setAttribute('display_image', $this->resolveMedia($course->featured_image, 'assets/front/img/home-1/courses/courses-01.jpg'));
            $course->setAttribute('students_count', $course->students_count ?? 0);
            $course->setAttribute('reviews_count', $course->reviews_count ?? 0);
            $course->setAttribute('average_rating', $this->formatRating($course->reviews_avg_rating));

            return $course;
        });

        $studentsCount = $courses->sum('students_count');
        $averageRating = $this->formatRating($courses->avg('average_rating'));
        $totalReviews = $courses->sum('reviews_count');

        $recentReviews = Review::query()
            ->with(['user:id,name,profile_photo', 'course:id,title'])
            ->where('is_approved', true)
            ->whereHas('course', function ($query) use ($instructor) {
                $query->where('instructor_id', $instructor->id);
            })
            ->latest()
            ->take(6)
            ->get()
            ->map(function (Review $review) {
                $review->setAttribute('student_avatar', $this->resolveMedia(optional($review->user)->profile_photo, 'assets/front/img/home-2/testimonial/client-01.png'));

                return $review;
            });

        $instructor->setAttribute('display_avatar', $this->resolveMedia($instructor->profile_photo, 'assets/front/img/inner/team-details/01.jpg'));
        $instructor->setAttribute('headline', $instructor->specialization ?? 'Instructor');
        $instructor->setAttribute('contact', [
            'phone' => $instructor->phone,
            'phone_link' => $instructor->phone ? preg_replace('/[^\d\+]/', '', $instructor->phone) : null,
            'email' => $instructor->email,
        ]);
        $instructor->setAttribute('stats', [
            'courses' => $instructor->courses_count,
            'students' => $studentsCount,
            'rating' => $averageRating,
            'reviews' => $totalReviews,
        ]);

        return view('instructor.show', [
            'pageTitle' => $instructor->name,
            'metaDescription' => $instructor->bio ?? 'Explore instructor profile, experience, and courses on ' . site_name() . '.',
            'instructor' => $instructor,
            'courses' => $courses,
            'studentsCount' => $studentsCount,
            'averageRating' => $averageRating,
            'totalReviews' => $totalReviews,
            'recentReviews' => $recentReviews,
        ]);
    }

    private function formatRating(?float $value): ?float
    {
        if ($value === null) {
            return null;
        }

        return round($value, 1);
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

        return asset($fallback);
    }
}
