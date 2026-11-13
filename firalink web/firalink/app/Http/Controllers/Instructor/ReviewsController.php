<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Review;
use App\Models\ReviewReply;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Reviews Controller
 *
 * This controller handles the reviews functionality.
 *
 * @package App\Http\Controllers\Instructor
 */
class ReviewsController extends Controller
{
    /**
     * Display instructor reviews
     */
    public function index()
    {
        $user = Auth::user();
        
        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        $reviewsQuery = Review::with(['user', 'course', 'replies.user'])
            ->whereHas('course', function($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->where('is_approved', true)
            ->orderBy('created_at', 'desc');

        if (request()->has('rating') && request()->rating !== 'all') {
            $reviewsQuery->where('rating', request()->rating);
        }

        $reviews = $reviewsQuery->get();

        $currentUserId = $user->id;
        $processedReviews = $reviews->map(function($review) use ($currentUserId) {
            $hasReplied = false;
            if ($review->replies && $review->replies->count() > 0) {
                foreach ($review->replies as $reply) {
                    if ($reply->is_instructor && $reply->user_id == $currentUserId) {
                        $hasReplied = true;
                        break;
                    }
                }
            }
            $review->hasReplied = $hasReplied;
            return $review;
        });
        $stats = [
            'total_reviews' => $processedReviews->count(),
            'average_rating' => $processedReviews->count() > 0 ? round($processedReviews->avg('rating'), 1) : 0,
            'five_star' => $processedReviews->where('rating', 5)->count(),
            'four_star' => $processedReviews->where('rating', 4)->count(),
            'three_star' => $processedReviews->where('rating', 3)->count(),
            'two_star' => $processedReviews->where('rating', 2)->count(),
            'one_star' => $processedReviews->where('rating', 1)->count(),
            'total_replies' => $processedReviews->sum(function($review) {
                return $review->replies ? $review->replies->count() : 0;
            }),
        ];

        // Pagination
        $perPage = 6;
        $currentPage = request()->get('page', 1);
        $paginatedReviews = $processedReviews->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $total = $processedReviews->count();
        $lastPage = ceil($total / $perPage);

        // Create paginator-like object
        $pagination = new \stdClass();
        $pagination->currentPage = (int)$currentPage;
        $pagination->lastPage = $lastPage;
        $pagination->perPage = $perPage;
        $pagination->total = $total;
        $pagination->from = $total > 0 ? (($currentPage - 1) * $perPage) + 1 : 0;
        $pagination->to = min($currentPage * $perPage, $total);

        return view('instructor.reviews', compact('user', 'paginatedReviews', 'stats', 'pagination'));
    }

    /**
     * Store a reply to a review
     */
    public function reply(Request $request, $reviewId)
    {
        $user = Auth::user();
        
        if (!$user->is_approved) {
            ToastMagic::error('You must be an approved instructor to reply to reviews.');
            return back();
        }

        $review = Review::with('course')
            ->where('id', $reviewId)
            ->whereHas('course', function($query) use ($user) {
                $query->where('instructor_id', $user->id);
            })
            ->firstOrFail();

        $existingReply = ReviewReply::where('review_id', $reviewId)
            ->where('user_id', $user->id)
            ->where('is_instructor', true)
            ->first();

        if ($existingReply) {
            ToastMagic::warning('You have already replied to this review. You can update your existing reply.');
            return back();
        }

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ], [
            'reply.required' => 'Please enter a reply.',
            'reply.max' => 'Reply cannot exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        ReviewReply::create([
            'review_id' => $reviewId,
            'user_id' => $user->id,
            'reply' => $request->reply,
            'is_instructor' => true,
        ]);

        ToastMagic::success('Your reply has been posted successfully!');
        return back();
    }

    /**
     * Update an existing reply
     */
    public function updateReply(Request $request, $reviewId, $replyId)
    {
        $user = Auth::user();
        
        if (!$user->is_approved) {
            ToastMagic::error('You must be an approved instructor to update replies.');
            return back();
        }

        $reply = ReviewReply::where('id', $replyId)
            ->where('review_id', $reviewId)
            ->where('user_id', $user->id)
            ->where('is_instructor', true)
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ], [
            'reply.required' => 'Please enter a reply.',
            'reply.max' => 'Reply cannot exceed 1000 characters.',
        ]);

        if ($validator->fails()) {
            ToastMagic::warning('Please correct the validation errors and try again.');
            return back()->withErrors($validator)->withInput();
        }

        try {
            $reply->update([
                'reply' => $request->reply,
            ]);

            ToastMagic::success('Your reply has been updated successfully!');
            return back();
        } catch (\Exception $e) {
            ToastMagic::error('An error occurred while updating your reply. Please try again.');
            return back()->withInput();
        }
    }
}
