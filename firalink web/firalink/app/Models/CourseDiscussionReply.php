<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Course Discussion Reply Model
 *
 * Represents a reply to a course discussion thread.
 *
 * @package App\Models
 */
class CourseDiscussionReply extends Model
{
    use HasFactory;

    protected $table = 'course_discussion_replies';

    protected $fillable = [
        'discussion_id',
        'user_id',
        'content',
    ];

    /**
     * Get the discussion thread that this reply belongs to.
     */
    public function discussion(): BelongsTo
    {
        return $this->belongsTo(CourseDiscussion::class, 'discussion_id');
    }

    /**
     * Get the user who posted the reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the likes for this reply.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(CourseDiscussionReplyLike::class, 'reply_id');
    }

    /**
     * Check if a specific user has liked this reply.
     */
    public function isLikedByUser(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        return $this->likes()->where('user_id', $userId)->exists();
    }
}
