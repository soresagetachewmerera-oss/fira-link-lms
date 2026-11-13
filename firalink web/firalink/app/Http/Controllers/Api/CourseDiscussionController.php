<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseDiscussionRequest;
use App\Http\Requests\StoreCourseDiscussionReplyRequest;
use App\Services\CourseDiscussionService;
use App\Models\CourseDiscussion;
use App\Models\CourseDiscussionReply;
use Illuminate\Http\Request;

/**
 * API Course Discussion Controller (Student)
 *
 * Handles discussions API endpoints for students in the mobile app.
 *
 * @package App\Http\Controllers\Api
 */
class CourseDiscussionController extends Controller
{
    protected $discussionService;

    public function __construct(CourseDiscussionService $discussionService)
    {
        $this->discussionService = $discussionService;
    }

    /**
     * Get a paginated list of course discussions.
     */
    public function index(Request $request, $courseId)
    {
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');
        $perPage = $request->input('per_page', 15);

        try {
            $discussions = $this->discussionService->getDiscussions($courseId, $search, $filter, $perPage);
            return response()->json([
                'success' => true,
                'data' => $discussions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Store a new discussion thread.
     */
    public function store(StoreCourseDiscussionRequest $request, $courseId)
    {
        try {
            $discussion = $this->discussionService->createDiscussion($courseId, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Discussion posted successfully.',
                'data' => $discussion->load('user:id,name,profile_photo')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Get detailed discussion thread with replies.
     */
    public function show($courseId, $id)
    {
        try {
            $discussion = $this->discussionService->getDiscussionDetails($id);
            return response()->json([
                'success' => true,
                'data' => $discussion
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Store a reply.
     */
    public function storeReply(StoreCourseDiscussionReplyRequest $request, $courseId, $id)
    {
        try {
            $reply = $this->discussionService->createReply($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully.',
                'data' => $reply->load('user:id,name,profile_photo')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Toggle like status on discussion.
     */
    public function toggleLike($courseId, $id)
    {
        try {
            $liked = $this->discussionService->toggleLikeDiscussion($id);
            $discussion = CourseDiscussion::findOrFail($id);
            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $discussion->likes()->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Toggle like status on reply.
     */
    public function toggleReplyLike($courseId, $replyId)
    {
        try {
            $liked = $this->discussionService->toggleLikeReply($replyId);
            $reply = CourseDiscussionReply::findOrFail($replyId);
            return response()->json([
                'success' => true,
                'liked' => $liked,
                'likes_count' => $reply->likes()->count()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Delete discussion.
     */
    public function destroy($courseId, $id)
    {
        try {
            $this->discussionService->deleteDiscussion($id);
            return response()->json([
                'success' => true,
                'message' => 'Discussion deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Delete reply.
     */
    public function destroyReply($courseId, $id, $replyId)
    {
        try {
            $this->discussionService->deleteReply($replyId);
            return response()->json([
                'success' => true,
                'message' => 'Reply deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }
}
