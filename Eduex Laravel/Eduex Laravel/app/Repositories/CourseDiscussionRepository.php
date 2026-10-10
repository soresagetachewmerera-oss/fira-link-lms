<?php

namespace App\Repositories;

use App\Models\CourseDiscussion;
use App\Models\CourseDiscussionReply;
use App\Models\CourseDiscussionLike;
use App\Models\CourseDiscussionReplyLike;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Course Discussion Repository
 *
 * Handles database operations for course discussions and replies.
 *
 * @package App\Repositories
 */
class CourseDiscussionRepository
{
    /**
     * Get discussions for a specific course with filters and search.
     *
     * @param int $courseId
     * @param string|null $search
     * @param string|null $filter
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getDiscussionsForCourse(
        int $courseId,
        ?string $search = null,
        ?string $filter = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $userId = Auth::id();

        return CourseDiscussion::query()
            ->where('course_id', $courseId)
            ->with(['user:id,name,profile_photo'])
            ->withCount(['replies', 'likes'])
            // Check if authenticated user liked the thread
            ->when($userId, function (Builder $query) use ($userId) {
                $query->withExists(['likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }]);
            })
            // Apply search
            ->when($search, function (Builder $query, string $search) {
                $query->where(function (Builder $q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                });
            })
            // Apply filters
            ->when($filter, function (Builder $query, string $filter) use ($userId) {
                switch ($filter) {
                    case 'pinned':
                        $query->where('is_pinned', true);
                        break;
                    case 'announcements':
                        $query->where('is_announcement', true);
                        break;
                    case 'my_questions':
                        if ($userId) {
                            $query->where('user_id', $userId);
                        }
                        break;
                }
            })
            // Sorting: Pinned first, then announcements, then newest first
            ->orderByDesc('is_pinned')
            ->orderByDesc('is_announcement')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Find a discussion thread by ID with eager loaded replies and like statuses.
     *
     * @param int $discussionId
     * @return CourseDiscussion
     */
    public function findDiscussion(int $discussionId): CourseDiscussion
    {
        $userId = Auth::id();

        return CourseDiscussion::query()
            ->with([
                'course:id,title,instructor_id',
                'user:id,name,profile_photo',
                'replies' => function ($q) use ($userId) {
                    $q->withCount('likes')
                      ->when($userId, function ($query) use ($userId) {
                          $query->withExists(['likes as is_liked' => function ($sub) use ($userId) {
                              $sub->where('user_id', $userId);
                          }]);
                      })
                      ->orderBy('created_at', 'asc');
                },
                'replies.user:id,name,profile_photo',
            ])
            ->withCount('likes')
            ->when($userId, function (Builder $query) use ($userId) {
                $query->withExists(['likes as is_liked' => function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }]);
            })
            ->findOrFail($discussionId);
    }

    /**
     * Create a new discussion thread.
     *
     * @param array $data
     * @return CourseDiscussion
     */
    public function createDiscussion(array $data): CourseDiscussion
    {
        return CourseDiscussion::create($data);
    }

    /**
     * Create a reply to a discussion.
     *
     * @param array $data
     * @return CourseDiscussionReply
     */
    public function createReply(array $data): CourseDiscussionReply
    {
        return CourseDiscussionReply::create($data);
    }

    /**
     * Find reply by ID.
     *
     * @param int $replyId
     * @return CourseDiscussionReply
     */
    public function findReply(int $replyId): CourseDiscussionReply
    {
        return CourseDiscussionReply::findOrFail($replyId);
    }

    /**
     * Toggle like status on a discussion.
     *
     * @param int $discussionId
     * @param int $userId
     * @return bool True if liked, false if unliked
     */
    public function toggleLikeDiscussion(int $discussionId, int $userId): bool
    {
        $existing = CourseDiscussionLike::where('discussion_id', $discussionId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        CourseDiscussionLike::create([
            'discussion_id' => $discussionId,
            'user_id' => $userId,
        ]);
        return true;
    }

    /**
     * Toggle like status on a reply.
     *
     * @param int $replyId
     * @param int $userId
     * @return bool True if liked, false if unliked
     */
    public function toggleLikeReply(int $replyId, int $userId): bool
    {
        $existing = CourseDiscussionReplyLike::where('reply_id', $replyId)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            return false;
        }

        CourseDiscussionReplyLike::create([
            'reply_id' => $replyId,
            'user_id' => $userId,
        ]);
        return true;
    }

    /**
     * Toggle pinned status of a discussion.
     *
     * @param int $discussionId
     * @return bool New pin status
     */
    public function togglePin(int $discussionId): bool
    {
        $discussion = CourseDiscussion::findOrFail($discussionId);
        $discussion->is_pinned = !$discussion->is_pinned;
        $discussion->save();
        return $discussion->is_pinned;
    }

    /**
     * Toggle announcement status of a discussion.
     *
     * @param int $discussionId
     * @return bool New announcement status
     */
    public function toggleAnnouncement(int $discussionId): bool
    {
        $discussion = CourseDiscussion::findOrFail($discussionId);
        $discussion->is_announcement = !$discussion->is_announcement;
        $discussion->save();
        return $discussion->is_announcement;
    }

    /**
     * Delete a discussion.
     *
     * @param int $discussionId
     * @return bool|null
     */
    public function deleteDiscussion(int $discussionId): ?bool
    {
        return CourseDiscussion::findOrFail($discussionId)->delete();
    }

    /**
     * Delete a reply.
     *
     * @param int $replyId
     * @return bool|null
     */
    public function deleteReply(int $replyId): ?bool
    {
        return CourseDiscussionReply::findOrFail($replyId)->delete();
    }
}
