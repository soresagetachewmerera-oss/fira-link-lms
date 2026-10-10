<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Http\Request;

class InstructorController extends Controller
{
    /**
     * Get list of all instructors.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $instructors = User::where('role', 'instructor')
            ->where('is_approved', true)
            ->withCount(['courses'])
            ->get(['id', 'name', 'profile_photo', 'bio', 'specialization', 'average_rating', 'courses_count']);

        return response()->json([
            'success' => true,
            'data' => $instructors
        ]);
    }

    /**
     * Get details of a single instructor.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $instructor = User::where('role', 'instructor')
            ->where('is_approved', true)
            ->where('id', $id)
            ->withCount(['courses'])
            ->with([
                'courses' => function ($query) {
                    $query->where('status', 'published');
                }
            ])
            ->first();

        if (!$instructor) {
            return response()->json([
                'success' => false,
                'message' => 'Instructor not found'
            ], 404);
        }

        // Get reviews for all instructor courses
        $courseIds = $instructor->courses->pluck('id');
        $reviews = Review::whereIn('course_id', $courseIds)
            ->where('is_approved', true)
            ->with(['user:id,name,profile_photo', 'course:id,title'])
            ->latest()
            ->take(10)
            ->get();

        // Calculate total students across all courses
        $studentsCount = Enrollment::whereIn('course_id', $courseIds)
            ->whereIn('status', ['approved', 'completed'])
            ->count();

        // Calculate total reviews count
        $reviewsTotalCount = Review::whereIn('course_id', $courseIds)
            ->where('is_approved', true)
            ->count();

        $data = $instructor->toArray();
        $data['students_count'] = $studentsCount;
        $data['reviews_count'] = $reviewsTotalCount;
        $data['latest_reviews'] = $reviews;

        // Ensure social links are present
        $data['social_links'] = [
            'linkedin' => $instructor->linkedin,
            'twitter' => $instructor->twitter,
            'facebook' => $instructor->facebook,
            'youtube' => $instructor->youtube,
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
