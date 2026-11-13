<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Course;
use App\Models\User;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use App\Models\ReviewReply;
use Illuminate\Http\Request;
use App\Services\NotificationPreferenceService;
use Illuminate\Support\Facades\Mail;
use App\Mail\InstructorReviewNotificationMail;

/**
 * Review Controller
 *
 * This controller handles the management of reviews.
 *
 * @package App\Http\Controllers\Admin
 */
class ReviewController extends Controller
{
    public function __construct(private NotificationPreferenceService $notificationPreferenceService) {}

    /**
     * Display all reviews
     */
    public function index()
    {
        $viewData = $this->getIndexData();
        return view('admin.reviews.index', $viewData);
    }

    /**
     * Display pending reviews
     */
    public function pending()
    {
        request()->merge(['status' => 'pending']);
        $viewData = $this->getIndexData();
        return view('admin.reviews.pending', $viewData);
    }

    /**
     * Get index data (shared between index, pending)
     */
    private function getIndexData()
    {
        $query = Review::with(['user', 'course.instructor', 'replies.user']);

        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('course', function ($courseQuery) use ($search) {
                        $courseQuery->where('title', 'like', "%{$search}%");
                    });
            });
        }

        if (request()->has('status') && request()->status) {
            if (request()->status === 'approved') {
                $query->where('is_approved', true);
            } elseif (request()->status === 'pending') {
                $query->where('is_approved', false);
            }
        }

        if (request()->has('rating') && request()->rating && request()->rating !== 'all') {
            $query->where('rating', request()->rating);
        }

        if (request()->has('course_id') && request()->course_id) {
            $query->where('course_id', request()->course_id);
        }

        if (request()->has('student_id') && request()->student_id) {
            $query->where('user_id', request()->student_id);
        }

        if (request()->has('instructor_id') && request()->instructor_id) {
            $query->whereHas('course', function ($q) {
                $q->where('instructor_id', request()->instructor_id);
            });
        }

        $reviews = $query->latest()->paginate(20)->withQueryString();

        $totalReviews = Review::count();
        $approvedReviews = Review::where('is_approved', true)->count();
        $pendingReviews = Review::where('is_approved', false)->count();

        $fiveStar = Review::where('rating', 5)->where('is_approved', true)->count();
        $fourStar = Review::where('rating', 4)->where('is_approved', true)->count();
        $threeStar = Review::where('rating', 3)->where('is_approved', true)->count();
        $twoStar = Review::where('rating', 2)->where('is_approved', true)->count();
        $oneStar = Review::where('rating', 1)->where('is_approved', true)->count();

        $averageRating = Review::where('is_approved', true)->avg('rating');
        $averageRating = $averageRating ? round($averageRating, 1) : 0;

        $courses = Course::where('status', 'published')->orderBy('title')->get();
        $instructors = User::where('role', 'instructor')->where('is_approved', true)->orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get();

        return compact(
            'reviews',
            'totalReviews',
            'approvedReviews',
            'pendingReviews',
            'fiveStar',
            'fourStar',
            'threeStar',
            'twoStar',
            'oneStar',
            'averageRating',
            'courses',
            'instructors',
            'students'
        );
    }

    /**
     * Display review details
     *
     * @param int $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $review = Review::with([
            'user',
            'course.instructor',
            'enrollment',
            'replies.user'
        ])->findOrFail($id);

        return view('admin.reviews.show', compact('review'));
    }

    /**
     * Approve a review
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function approve($id)
    {
        $review = Review::findOrFail($id);

        if ($review->is_approved) {
            ToastMagic::info('This review is already approved.');
            return back();
        }

        $review->update(['is_approved' => true]);

        $this->notifyInstructorAboutReview($review);

        ToastMagic::success('Review approved successfully.');
        return back();
    }

    /**
     * Reject/Unapprove a review
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reject($id)
    {
        $review = Review::findOrFail($id);

        if (!$review->is_approved) {
            ToastMagic::info('This review is already pending.');
            return back();
        }

        $review->update(['is_approved' => false]);

        ToastMagic::success('Review has been set to pending status.');
        return back();
    }

    /**
     * Delete a review
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $review = Review::findOrFail($id);

        $review->delete();

        ToastMagic::success('Review deleted successfully.');
        return redirect()->route('admin.reviews.index');
    }

    /**
     * Delete a review reply
     *
     * @param int $reviewId
     * @param int $replyId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteReply($reviewId, $replyId)
    {
        $reply = ReviewReply::where('review_id', $reviewId)
            ->findOrFail($replyId);

        $reply->delete();

        ToastMagic::success('Reply deleted successfully.');
        return back();
    }

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
