<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Course Discussion Like Model
 *
 * Represents a like on a course discussion thread.
 *
 * @package App\Models
 */
class CourseDiscussionLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'discussion_id',
    ];

    /**
     * Get the discussion thread that was liked.
     */
    public function discussion(): BelongsTo
    {
        return $this->belongsTo(CourseDiscussion::class, 'discussion_id');
    }

    /**
     * Get the user who liked the discussion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
