<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseDiscussionRequest;
use App\Http\Requests\StoreCourseDiscussionReplyRequest;
use App\Services\CourseDiscussionService;
use App\Models\Course;
use App\Models\CourseDiscussion;
use App\Models\CourseDiscussionReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API Course Discussion Controller (Instructor)
 *
 * Handles discussions API endpoints for the instructor mobile app.
 *
 * @package App\Http\Controllers\Api\Instructor
 */
class CourseDiscussionController extends Controller
{
    protected $discussionService;

    public function __construct(CourseDiscussionService $discussionService)
    {
        $this->discussionService = $discussionService;
    }

    /**
     * Get discussions across instructor's courses.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Get instructor's course IDs
        $courseIds = Course::where('instructor_id', $user->id)->pluck('id')->toArray();
        
        $selectedCourseId = $request->input('course_id');
        $search = $request->input('search');
        $perPage = $request->input('per_page', 15);

        try {
            $discussions = CourseDiscussion::query()
                ->when($selectedCourseId, function ($query, $courseId) {
                    $query->where('course_id', $courseId);
                }, function ($query) use ($courseIds) {
                    $query->whereIn('course_id', $courseIds);
                })
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('title', 'like', "%{$search}%")
                          ->orWhere('content', 'like', "%{$search}%");
                    });
                })
                ->with(['course:id,title,featured_image', 'user:id,name,profile_photo'])
                ->withCount(['replies', 'likes'])
                ->withExists(['likes as is_liked' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }])
                ->orderByDesc('is_pinned')
                ->orderByDesc('is_announcement')
                ->orderByDesc('created_at')
                ->paginate($perPage);

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
     * Store a new discussion thread (announcement/pin optional).
     */
    public function store(StoreCourseDiscussionRequest $request, $courseId)
    {
        try {
            $discussion = $this->discussionService->createDiscussion($courseId, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Discussion thread posted successfully.',
                'data' => $discussion->load(['user:id,name,profile_photo', 'course:id,title'])
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Get discussion thread details with replies.
     */
    public function show($id)
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
     * Post a reply.
     */
    public function storeReply(StoreCourseDiscussionReplyRequest $request, $id)
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
    public function toggleLike($id)
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
    public function toggleReplyLike($replyId)
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
     * Toggle pinned status.
     */
    public function togglePin($id)
    {
        try {
            $pinned = $this->discussionService->togglePin($id);
            return response()->json([
                'success' => true,
                'is_pinned' => $pinned,
                'message' => $pinned ? 'Thread pinned.' : 'Thread unpinned.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Toggle announcement status.
     */
    public function toggleAnnouncement($id)
    {
        try {
            $announcement = $this->discussionService->toggleAnnouncement($id);
            return response()->json([
                'success' => true,
                'is_announcement' => $announcement,
                'message' => $announcement ? 'Thread set as announcement.' : 'Thread removed from announcements.'
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
    public function destroy($id)
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
    public function destroyReply($id, $replyId)
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
