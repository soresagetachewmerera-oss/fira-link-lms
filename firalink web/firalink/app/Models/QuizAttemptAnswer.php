<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quiz Attempt Answer Model
 *
 * This model represents an answer to a question in a quiz attempt.
 *
 * @package App\Models
 */
class QuizAttemptAnswer extends Model
{
    protected $fillable = [
        'quiz_attempt_id',
        'question_id',
        'selected_answer',
        'is_correct',
    ];

    protected $casts = [
        'selected_answer' => 'integer',
        'is_correct' => 'boolean',
    ];

    public function quizAttempt(): BelongsTo
    {
        return $this->belongsTo(QuizAttempt::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuizQuestion::class, 'question_id');
    }
}
