<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Quiz Question Model
 *
 * This model represents a question for a quiz.
 *
 * @package App\Models
 */
class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id',
        'question',
        'type',
        'options',
        'correct_answer',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'correct_answer' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Get the quiz that owns the question.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
