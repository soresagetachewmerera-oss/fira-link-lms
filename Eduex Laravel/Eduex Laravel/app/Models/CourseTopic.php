<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Course Topic Model
 *
 * This model represents a topic for a course.
 *
 * @package App\Models
 */
class CourseTopic extends Model
{
    protected $fillable = [
        'course_id',
        'title',
        'description',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the course that owns the topic.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lessons for the topic.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('order');
    }

    /**
     * Get the quizzes for the topic.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class)->orderBy('order');
    }

    /**
     * Get the assignments for the topic.
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class)->orderBy('order');
    }

    /**
     * Calculate progress statistics for a user
     */
    public function getProgressStats($userProgress, $userQuizAttempts, $userAssignmentSubmissions): array
    {
        $lessons = $this->lessons;
        $quizzes = $this->quizzes;
        $assignments = $this->assignments;

        $totalItems = $lessons->count() + $quizzes->count() + $assignments->count();

        $completedLessons = $lessons->filter(function($lesson) use ($userProgress) {
            return isset($userProgress[$lesson->id]) && $userProgress[$lesson->id]->is_completed;
        })->count();

        $completedQuizzes = $quizzes->filter(function($quiz) use ($userQuizAttempts) {
            return isset($userQuizAttempts[$quiz->id]) && $userQuizAttempts[$quiz->id]->passed;
        })->count();

        $completedAssignments = $assignments->filter(function($assignment) use ($userAssignmentSubmissions) {
            return isset($userAssignmentSubmissions[$assignment->id]);
        })->count();

        $completedItems = $completedLessons + $completedQuizzes + $completedAssignments;

        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
        ];
    }
}
