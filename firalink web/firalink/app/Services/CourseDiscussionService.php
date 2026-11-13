<?php

namespace App\Services;

use App\Repositories\CourseDiscussionRepository;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Models\CourseDiscussion;
use App\Models\CourseDiscussionReply;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;

/**
 * Course Discussion Service
 *
 * Handles the business logic for course discussions and replies,
 * including permission validations for students, instructors, and admins.
 *
 * @package App\Services
 */
class CourseDiscussionService
{
    protected $repository;

    public function __construct(CourseDiscussionRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Check if a user has access to a course's discussions.
     *
     * @param User $user
     * @param int $courseId
     * @return bool
     */
    public function hasCourseAccess(User $user, int $courseId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return Course::where('id', $courseId)
                ->where('instructor_id', $user->id)
                ->exists();
        }

        // Student active enrollment check
        return Enrollment::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->whereIn('status', [Enrollment::STATUS_APPROVED, Enrollment::STATUS_COMPLETED])
            ->exists();
    }

    /**
     * Check if a user is the instructor of a course or an admin.
     *
     * @param User $user
     * @param int $courseId
     * @return bool
     */
    public function isCourseModerator(User $user, int $courseId): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isInstructor()) {
            return Course::where('id', $courseId)
                ->where('instructor_id', $user->id)
                ->exists();
        }

        return false;
    }

    /**
     * Fetch discussions for a course with permissions check.
     *
     * @param int $courseId
     * @param string|null $search
     * @param string|null $filter
     * @param int $perPage
     * @return LengthAwarePaginator
     * @throws AuthorizationException
     */
    public function getDiscussions(
        int $courseId,
        ?string $search = null,
        ?string $filter = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $user = Auth::user();
        if (!$user || !$this->hasCourseAccess($user, $courseId)) {
            throw new AuthorizationException('You are not authorized to access discussions for this course.');
        }

        return $this->repository->getDiscussionsForCourse($courseId, $search, $filter, $perPage);
    }

    /**
     * Fetch discussion details with permissions check.
     *
     * @param int $discussionId
     * @return CourseDiscussion
     * @throws AuthorizationException
     */
    public function getDiscussionDetails(int $discussionId): CourseDiscussion
    {
        $discussion = CourseDiscussion::findOrFail($discussionId);
        $user = Auth::user();

        if (!$user || !$this->hasCourseAccess($user, $discussion->course_id)) {
            throw new AuthorizationException('You are not authorized to view this discussion.');
        }

        return $this->repository->findDiscussion($discussionId);
    }

    /**
     * Create a new discussion thread.
     *
     * @param int $courseId
     * @param array $data
     * @return CourseDiscussion
     * @throws AuthorizationException
     */
    public function createDiscussion(int $courseId, array $data): CourseDiscussion
    {
        $user = Auth::user();
        if (!$user || !$this->hasCourseAccess($user, $courseId)) {
            throw new AuthorizationException('You are not authorized to post in this course forum.');
        }

        $isModerator = $this->isCourseModerator($user, $courseId);

        $discussionData = [
            'course_id' => $courseId,
            'user_id' => $user->id,
            'title' => $data['title'],
            'content' => $data['content'],
            'is_pinned' => $isModerator ? ($data['is_pinned'] ?? false) : false,
            'is_announcement' => $isModerator ? ($data['is_announcement'] ?? false) : false,
        ];

        return $this->repository->createDiscussion($discussionData);
    }

    /**
     * Post a reply to a discussion thread.
     *
     * @param int $discussionId
     * @param array $data
     * @return CourseDiscussionReply
     * @throws AuthorizationException
     */
    public function createReply(int $discussionId, array $data): CourseDiscussionReply
    {
        $discussion = CourseDiscussion::findOrFail($discussionId);
        $user = Auth::user();

        if (!$user || !$this->hasCourseAccess($user, $discussion->course_id)) {
            throw new AuthorizationException('You are not authorized to reply to this discussion.');
        }

        $replyData = [
            'discussion_id' => $discussionId,
            'user_id' => $user->id,
            'content' => $data['content'],
        ];

        return $this->repository->createReply($replyData);
    }

    /**
     * Toggle like on a discussion thread.
     *
     * @param int $discussionId
     * @return bool
     * @throws AuthorizationException
     */
    public function toggleLikeDiscussion(int $discussionId): bool
    {
        $discussion = CourseDiscussion::findOrFail($discussionId);
        $user = Auth::user();

        if (!$user || !$this->hasCourseAccess($user, $discussion->course_id)) {
            throw new AuthorizationException('You are not authorized to like this discussion.');
        }

        return $this->repository->toggleLikeDiscussion($discussionId, $user->id);
    }

    /**
     * Toggle like on a reply.
     *
     * @param int $replyId
     * @return bool
     * @throws AuthorizationException
     */
    public function toggleLikeReply(int $replyId): bool
    {
        $reply = CourseDiscussionReply::with('discussion')->findOrFail($replyId);
        $user = Auth::user();

        if (!$user || !$this->hasCourseAccess($user, $reply->discussion->course_id)) {
            throw new AuthorizationException('You are not authorized to like this reply.');
        }

        return $this->repository->toggleLikeReply($replyId, $user->id);
    }

    /**
     * Toggle pinned status (moderators only).
     *
     * @param int $discussionId
     * @return bool
     * @throws AuthorizationException
     */
    public function togglePin(int $discussionId): bool
    {
        $discussion = CourseDiscussion::findOrFail($discussionId);
        $user = Auth::user();

        if (!$user || !$this->isCourseModerator($user, $discussion->course_id)) {
            throw new AuthorizationException('Only instructors or administrators can pin threads.');
        }

        return $this->repository->togglePin($discussionId);
    }

    /**
     * Toggle announcement status (moderators only).
     *
     * @param int $discussionId
     * @return bool
     * @throws AuthorizationException
     */
    public function toggleAnnouncement(int $discussionId): bool
    {
        $discussion = CourseDiscussion::findOrFail($discussionId);
        $user = Auth::user();

        if (!$user || !$this->isCourseModerator($user, $discussion->course_id)) {
            throw new AuthorizationException('Only instructors or administrators can make announcements.');
        }

        return $this->repository->toggleAnnouncement($discussionId);
    }

    /**
     * Delete a discussion thread.
     *
     * @param int $discussionId
     * @return bool
     * @throws AuthorizationException
     */
    public function deleteDiscussion(int $discussionId): bool
    {
        $discussion = CourseDiscussion::findOrFail($discussionId);
        $user = Auth::user();

        if (!$user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        // Author can delete their own thread, moderator can delete any
        $isAuthor = $discussion->user_id === $user->id;
        $isModerator = $this->isCourseModerator($user, $discussion->course_id);

        if (!$isAuthor && !$isModerator) {
            throw new AuthorizationException('You are not authorized to delete this discussion.');
        }

        return $this->repository->deleteDiscussion($discussionId);
    }

    /**
     * Delete a reply.
     *
     * @param int $replyId
     * @return bool
     * @throws AuthorizationException
     */
    public function deleteReply(int $replyId): bool
    {
        $reply = CourseDiscussionReply::with('discussion')->findOrFail($replyId);
        $user = Auth::user();

        if (!$user) {
            throw new AuthorizationException('Unauthenticated.');
        }

        $isAuthor = $reply->user_id === $user->id;
        $isModerator = $this->isCourseModerator($user, $reply->discussion->course_id);

        if (!$isAuthor && !$isModerator) {
            throw new AuthorizationException('You are not authorized to delete this reply.');
        }

        return $this->repository->deleteReply($replyId);
    }
}
