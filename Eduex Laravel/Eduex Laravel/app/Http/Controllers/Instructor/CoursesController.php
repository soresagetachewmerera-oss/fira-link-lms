<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use Illuminate\Support\Facades\Auth;

/**
 * Courses Controller
 *
 * This controller handles the courses functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class CoursesController extends Controller
{
    /**
     * Display all courses for the instructor
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $courses = Course::where('instructor_id', $user->id)
            ->with([
                'topics.lessons:id,course_topic_id',
                'reviews' => fn ($q) => $q->select('id', 'course_id', 'rating', 'is_approved')->where('is_approved', true),
            ])
            ->withCount([
                'enrollments as students_count' => fn ($q) => $q->whereIn('status', [
                    Enrollment::STATUS_APPROVED,
                    Enrollment::STATUS_COMPLETED,
                ]),
            ])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($course) {
                $totalLessons = $course->topics->sum(fn ($topic) => $topic->lessons->count());

                $displayPrice = $course->pricing_model === 'free'
                    ? 0
                    : ($course->sale_price ?? $course->regular_price ?? 0);

                $imageUrl = $course->featured_image
                    ? asset('storage/' . $course->featured_image)
                    : asset('assets/front/img/home-1/courses/courses-01.jpg');

                $averageRating = $course->reviews->avg('rating');
                $ratingValue = $averageRating ? round($averageRating, 1) : null;
                $ratingForStars = $ratingValue ? round($ratingValue * 2) / 2 : 0;

                return [
                    'id' => $course->id,
                    'title' => $course->title,
                    'category' => $course->category ?? 'Uncategorized',
                    'image_url' => $imageUrl,
                    'featured_image' => $course->featured_image,
                    'rating' => $ratingValue,
                    'rating_for_stars' => $ratingForStars,
                    'lessons' => $totalLessons,
                    'students' => (int) $course->students_count,
                    'price' => $displayPrice > 0 ? currency_format($displayPrice) : currency_format(0),
                    'pricing_model' => $course->pricing_model,
                    'status' => $course->status ?? 'draft',
                    'is_live_course' => (bool)$course->is_live_course,
                    'created_at' => $course->created_at,
                    'updated_at' => $course->updated_at,
                ];
            });

        return view('instructor.courses', compact('user', 'courses'));
    }
}
