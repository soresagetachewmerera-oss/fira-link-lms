<?php

namespace App\Http\Controllers\Student;

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
 * Student Course Discussion Controller
 *
 * Handles AJAX discussion actions for students in the course player.
 *
 * @package App\Http\Controllers\Student
 */
class CourseDiscussionController extends Controller
{
    protected $discussionService;

    public function __construct(CourseDiscussionService $discussionService)
    {
        $this->discussionService = $discussionService;
    }

    /**
     * Display a listing of course discussions.
     */
    public function index(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        $search = $request->input('search');
        $filter = $request->input('filter', 'all');

        try {
            $discussions = $this->discussionService->getDiscussions($courseId, $search, $filter);
            
            $html = view('student.course-access.partials.forum-list', compact('course', 'discussions', 'filter', 'search'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Store a newly created discussion.
     */
    public function store(StoreCourseDiscussionRequest $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        try {
            $this->discussionService->createDiscussion($courseId, $request->validated());
            
            // Return updated list
            $discussions = $this->discussionService->getDiscussions($courseId);
            $html = view('student.course-access.partials.forum-list', [
                'course' => $course,
                'discussions' => $discussions,
                'filter' => 'all',
                'search' => null
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'Discussion posted successfully!',
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Display the specified discussion thread with replies.
     */
    public function show($courseId, $id)
    {
        $course = Course::findOrFail($courseId);

        try {
            $discussion = $this->discussionService->getDiscussionDetails($id);
            
            $html = view('student.course-access.partials.forum-show', compact('course', 'discussion'))->render();

            return response()->json([
                'success' => true,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Store a reply for a discussion.
     */
    public function storeReply(StoreCourseDiscussionReplyRequest $request, $courseId, $id)
    {
        $course = Course::findOrFail($courseId);

        try {
            $this->discussionService->createReply($id, $request->validated());
            
            // Return updated discussion details view
            $discussion = $this->discussionService->getDiscussionDetails($id);
            $html = view('student.course-access.partials.forum-show', compact('course', 'discussion'))->render();

            return response()->json([
                'success' => true,
                'message' => 'Reply posted successfully!',
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Toggle like status on a discussion.
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
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Toggle like status on a reply.
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
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Delete a discussion thread.
     */
    public function destroy($courseId, $id)
    {
        $course = Course::findOrFail($courseId);

        try {
            $this->discussionService->deleteDiscussion($id);
            
            // Return updated list
            $discussions = $this->discussionService->getDiscussions($courseId);
            $html = view('student.course-access.partials.forum-list', [
                'course' => $course,
                'discussions' => $discussions,
                'filter' => 'all',
                'search' => null
            ])->render();

            return response()->json([
                'success' => true,
                'message' => 'Discussion deleted successfully.',
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 403);
        }
    }

    /**
     * Delete a reply.
     */
    public function destroyReply($courseId, $id, $replyId)
    {
        $course = Course::findOrFail($courseId);

        try {
            $this->discussionService->deleteReply($replyId);

            // Return updated discussion details view
            $discussion = $this->discussionService->getDiscussionDetails($id);
            $html = view('student.course-access.partials.forum-show', compact('course', 'discussion'))->render();

            return response()->json([
                'success' => true,
                'message' => 'Reply deleted successfully.',
                'html' => $html
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 403);
        }
    }
}
