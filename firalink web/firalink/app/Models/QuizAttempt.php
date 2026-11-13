<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Quiz Attempt Model
 *
 * This model represents an attempt at a quiz.
 *
 * @package App\Models
 */
class QuizAttempt extends Model
{
    protected $fillable = [
        'user_id',
        'quiz_id',
        'enrollment_id',
        'score',
        'passed',
        'time_taken',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'passed' => 'boolean',
        'time_taken' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(QuizAttemptAnswer::class);
    }

    public function calculateScore(): int
    {
        $totalQuestions = $this->quiz->questions()->count();
        if ($totalQuestions === 0) {
            return 0;
        }

        $correctAnswers = $this->answers()->where('is_correct', true)->count();
        return (int) round(($correctAnswers / $totalQuestions) * 100);
    }

    public function isPassed(): bool
    {
        return $this->score >= $this->quiz->passing_score;
    }
}
