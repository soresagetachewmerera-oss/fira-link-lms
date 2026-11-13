<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationPreferenceService;
use Illuminate\Support\Facades\Mail;
use App\Mail\InstructorReviewNotificationMail;
use Illuminate\Support\Facades\Auth;

/**
 * Review Controller
 *
 * This controller handles the review functionality.
 *
 * @package App\Http\Controllers\Front
 */
class ReviewController extends Controller
{
    public function __construct(private NotificationPreferenceService $notificationPreferenceService)
    {
    }

    /**
     * Store a new review
     */
    public function store(Request $request, $courseId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user || !$user->isStudent()) {
            ToastMagic::error('Only enrolled students can write reviews.');
            return back();
        }

        $enrollment = Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->first();

        if (!$enrollment) {
            ToastMagic::error('You must be enrolled in this course to write a review.');
            return back();
        }

        $existingReview = Review::where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingReview) {
            ToastMagic::warning('You have already reviewed this course. You can update your existing review.');
            return back();
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Please select a rating.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        if ($request->rating == 0 || !$request->rating) {
            ToastMagic::warning('Please select a rating before submitting.');
            return back()->withInput();
        }

        try {
            $review = Review::create([
                'course_id' => $courseId,
                'user_id' => $user->id,
                'enrollment_id' => $enrollment->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
                'is_approved' => true,
            ]);

            $this->notifyInstructorAboutReview($review);

            ToastMagic::success('Thank you for your review!');
            return back();
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred while submitting your review. Please try again.');
            return back()->withInput();
        }
    }

    /**
     * Update an existing review
     */
    public function update(Request $request, $courseId, $reviewId)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $review = Review::where('id', $reviewId)
            ->where('course_id', $courseId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ], [
            'rating.required' => 'Please select a rating.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        if ($request->rating == 0 || !$request->rating) {
            ToastMagic::warning('Please select a rating before submitting.');
            return back()->withInput();
        }

        try {
            $review->update([
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            ToastMagic::success('Your review has been updated successfully!');
            return back();
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred while updating your review. Please try again.');
            return back()->withInput();
        }
    }

    /**
     * Notify the instructor about the review
     *
     * @param \App\Models\Review $review
     * @return void
     */
    protected function notifyInstructorAboutReview(Review $review): void
    {
        $review->loadMissing('course.instructor.notificationSettings', 'user');

        $instructor = $review->course?->instructor;

        if (! $instructor || empty($instructor->email)) {
            return;
        }

        if (! $this->notificationPreferenceService->prefers($instructor, 'review_notifications')) {
            return;
        }

        try {
            Mail::to($instructor->email)->send(new InstructorReviewNotificationMail($review));
        } catch (\Throwable $e) {
            \Log::error('Instructor review notification email failed: ' . $e->getMessage());
        }
    }
}
