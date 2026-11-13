<?php
/**
 * Instructor Review API Controller
 * 
 * Handles listing, viewing, and replying to reviews for instructor courses.
 */

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Review;
use App\Models\ReviewReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display a listing of the reviews.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending approval.',
            ], 403);
        }

        $query = Review::with(['user:id,name,profile_photo', 'course:id,title,featured_image', 'replies.user:id,name,profile_photo'])
            ->whereHas('course', function($q) use ($user) {
                $q->where('instructor_id', $user->id);
            })
            ->where('is_approved', true);

        // Filter by rating
        if ($request->has('rating') && $request->rating !== 'all') {
            $query->where('rating', $request->rating);
        }

        // Filter by course
        if ($request->has('course_id') && $request->course_id !== 'all') {
            $query->where('course_id', $request->course_id);
        }

        $reviews = $query->latest()->paginate($request->get('per_page', 10));

        // Transform results to include full URLs
        $reviews->getCollection()->transform(function($review) {
            if ($review->user) {
                $review->user->profile_photo = $review->user->profile_photo 
                    ? asset('storage/' . $review->user->profile_photo) 
                    : 'https://ui-avatars.com/api/?name=' . urlencode($review->user->name);
            }
            if ($review->course) {
                $review->course->thumbnail = $review->course->thumbnail; // Accessor handles this
            }
            foreach ($review->replies as $reply) {
                if ($reply->user) {
                    $reply->user->profile_photo = $reply->user->profile_photo 
                        ? asset('storage/' . $reply->user->profile_photo) 
                        : 'https://ui-avatars.com/api/?name=' . urlencode($reply->user->name);
                }
            }
            return $review;
        });

        // Stats
        $allReviews = Review::whereHas('course', function($q) use ($user) {
                $q->where('instructor_id', $user->id);
            })
            ->where('is_approved', true)
            ->get();

        $stats = [
            'total_reviews' => $allReviews->count(),
            'average_rating' => $allReviews->count() > 0 ? round($allReviews->avg('rating'), 1) : 0,
            'rating_breakdown' => [
                '5' => $allReviews->where('rating', 5)->count(),
                '4' => $allReviews->where('rating', 4)->count(),
                '3' => $allReviews->where('rating', 3)->count(),
                '2' => $allReviews->where('rating', 2)->count(),
                '1' => $allReviews->where('rating', 1)->count(),
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'reviews' => $reviews,
                'stats' => $stats
            ]
        ]);
    }

    /**
     * Store a reply to a review.
     */
    public function reply(Request $request, $id)
    {
        $user = $request->user();

        if (!$user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending approval.',
            ], 403);
        }

        $review = Review::where('id', $id)
            ->whereHas('course', function($q) use ($user) {
                $q->where('instructor_id', $user->id);
            })
            ->first();

        if (!$review) {
            return response()->json([
                'success' => false,
                'message' => 'Review not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $reply = ReviewReply::create([
            'review_id' => $id,
            'user_id' => $user->id,
            'reply' => $request->reply,
            'is_instructor' => true,
        ]);

        $reply->load('user:id,name,profile_photo');
        if ($reply->user) {
            $reply->user->profile_photo = $reply->user->profile_photo 
                ? asset('storage/' . $reply->user->profile_photo) 
                : 'https://ui-avatars.com/api/?name=' . urlencode($reply->user->name);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply posted successfully.',
            'data' => $reply
        ]);
    }

    /**
     * Update a reply.
     */
    public function updateReply(Request $request, $id, $replyId)
    {
        $user = $request->user();

        $reply = ReviewReply::where('id', $replyId)
            ->where('review_id', $id)
            ->where('user_id', $user->id)
            ->where('is_instructor', true)
            ->first();

        if (!$reply) {
            return response()->json([
                'success' => false,
                'message' => 'Reply not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'reply' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $reply->update([
            'reply' => $request->reply,
        ]);

        $reply->load('user:id,name,profile_photo');
        if ($reply->user) {
            $reply->user->profile_photo = $reply->user->profile_photo 
                ? asset('storage/' . $reply->user->profile_photo) 
                : 'https://ui-avatars.com/api/?name=' . urlencode($reply->user->name);
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply updated successfully.',
            'data' => $reply
        ]);
    }

    /**
     * Delete a reply.
     */
    public function deleteReply(Request $request, $id, $replyId)
    {
        $user = $request->user();

        $reply = ReviewReply::where('id', $replyId)
            ->where('review_id', $id)
            ->where('user_id', $user->id)
            ->where('is_instructor', true)
            ->first();

        if (!$reply) {
            return response()->json([
                'success' => false,
                'message' => 'Reply not found.',
            ], 404);
        }

        $reply->delete();

        return response()->json([
            'success' => true,
            'message' => 'Reply deleted successfully.'
        ]);
    }
}
