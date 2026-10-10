<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Course Discussion Reply Like Model
 *
 * Represents a like on a course discussion reply.
 *
 * @package App\Models
 */
class CourseDiscussionReplyLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reply_id',
    ];

    /**
     * Get the reply that was liked.
     */
    public function reply(): BelongsTo
    {
        return $this->belongsTo(CourseDiscussionReply::class, 'reply_id');
    }

    /**
     * Get the user who liked the reply.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
