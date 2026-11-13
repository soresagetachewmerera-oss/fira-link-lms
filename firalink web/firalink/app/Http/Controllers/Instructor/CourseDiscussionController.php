<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseDiscussionReplyRequest;
use App\Http\Requests\StoreCourseDiscussionRequest;
use App\Services\CourseDiscussionService;
use App\Models\Course;
use App\Models\CourseDiscussion;
use App\Models\CourseDiscussionReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Instructor Course Discussion Controller
 *
 * Handles discussions management in the instructor panel.
 *
 * @package App\Http\Controllers\Instructor
 */
class CourseDiscussionController extends Controller
{
    protected $discussionService;

    public function __construct(CourseDiscussionService $discussionService)
    {
        $this->discussionService = $discussionService;
    }

    /**
     * Display a listing of discussions for instructor's courses.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->is_approved) {
            return redirect()->route('instructor.pending');
        }

        // Get instructor's course IDs
        $courses = Course::where('instructor_id', $user->id)->orderBy('title')->get();
        $courseIds = $courses->pluck('id')->toArray();

        $selectedCourseId = $request->input('course_id');
        $search = $request->input('search');

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
            ->with(['course', 'user'])
            ->withCount(['replies', 'likes'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('is_announcement')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('instructor.discussions.index', compact('user', 'courses', 'discussions', 'selectedCourseId', 'search'));
    }

    /**
     * Store a newly created discussion (announcement) by the instructor.
     */
    public function store(StoreCourseDiscussionRequest $request, $courseId)
    {
        $user = Auth::user();
        $course = Course::where('id', $courseId)->where('instructor_id', $user->id)->firstOrFail();

        try {
            $data = $request->validated();
            $data['is_pinned'] = $request->has('is_pinned');
            $data['is_announcement'] = $request->has('is_announcement');

            $this->discussionService->createDiscussion($course->id, $data);

            return redirect()->back()->with('success', 'Discussion/Announcement posted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Display a single discussion thread.
     */
    public function show($id)
    {
        $user = Auth::user();
        $discussion = $this->discussionService->getDiscussionDetails($id);

        // Verify the instructor owns this course
        if ($discussion->course->instructor_id !== $user->id && !$user->isAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        return view('instructor.discussions.show', compact('user', 'discussion'));
    }

    /**
     * Reply to a thread.
     */
    public function storeReply(StoreCourseDiscussionReplyRequest $request, $id)
    {
        try {
            $this->discussionService->createReply($id, $request->validated());
            return redirect()->back()->with('success', 'Reply posted successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle pinned status.
     */
    public function togglePin($id)
    {
        try {
            $pinned = $this->discussionService->togglePin($id);
            $msg = $pinned ? 'Thread pinned successfully.' : 'Thread unpinned successfully.';
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Toggle announcement status.
     */
    public function toggleAnnouncement($id)
    {
        try {
            $announcement = $this->discussionService->toggleAnnouncement($id);
            $msg = $announcement ? 'Thread set as announcement.' : 'Thread removed from announcements.';
            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete discussion.
     */
    public function destroy($id)
    {
        try {
            $this->discussionService->deleteDiscussion($id);
            return redirect()->route('instructor.discussions.index')->with('success', 'Discussion thread deleted.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Delete reply.
     */
    public function destroyReply($id, $replyId)
    {
        try {
            $this->discussionService->deleteReply($replyId);
            return redirect()->back()->with('success', 'Reply deleted.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
