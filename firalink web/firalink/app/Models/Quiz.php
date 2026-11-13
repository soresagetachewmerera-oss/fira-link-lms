<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quiz Model
 *
 * This model represents a quiz for a course.
 *
 * @package App\Models
 */
class Quiz extends Model
{
    protected $fillable = [
        'course_topic_id',
        'title',
        'description',
        'passing_score',
        'time_limit',
        'order',
    ];

    protected $casts = [
        'passing_score' => 'integer',
        'time_limit' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the topic that owns the quiz.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(CourseTopic::class, 'course_topic_id');
    }

    /**
     * Get the questions for the quiz.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('order');
    }

    /**
     * Get the attempts for the quiz.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
