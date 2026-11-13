<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Review Reply Model
 *
 * This model represents a reply to a review.
 *
 * @package App\Models
 */
class ReviewReply extends Model
{
    protected $fillable = [
        'review_id',
        'user_id',
        'reply',
        'is_instructor',
    ];

    protected $casts = [
        'is_instructor' => 'boolean',
    ];

    /**
     * Get the review that this reply belongs to
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Get the user who wrote the reply
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
