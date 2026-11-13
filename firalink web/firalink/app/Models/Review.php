<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Review Model
 *
 * This model represents a review for a course.
 *
 * @package App\Models
 */
class Review extends Model
{
    protected $fillable = [
        'course_id',
        'user_id',
        'enrollment_id',
        'rating',
        'comment',
        'is_approved',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'rating' => 'integer',
    ];

    /**
     * Get the course that the review belongs to
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the user who wrote the review
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the enrollment associated with the review
     */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    /**
     * Get the replies for the review
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ReviewReply::class)->orderBy('created_at', 'asc');
    }
}
