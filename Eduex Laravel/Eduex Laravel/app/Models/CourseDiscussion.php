<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Course Discussion Model
 *
 * Represents a discussion thread within a specific course.
 *
 * @package App\Models
 */
class CourseDiscussion extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'user_id',
        'title',
        'content',
        'is_pinned',
        'is_announcement',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'is_announcement' => 'boolean',
    ];

    /**
     * Get the course that this discussion belongs to.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user who started the discussion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the replies for this discussion.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(CourseDiscussionReply::class, 'discussion_id');
    }

    /**
     * Get the likes for this discussion.
     */
    public function likes(): HasMany
    {
        return $this->hasMany(CourseDiscussionLike::class, 'discussion_id');
    }

    /**
     * Check if a specific user has liked this discussion.
     */
    public function isLikedByUser(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }
        return $this->likes()->where('user_id', $userId)->exists();
    }
}
