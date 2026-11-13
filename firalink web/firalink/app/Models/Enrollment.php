<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Enrollment Model
 *
 * This model represents an enrollment for a course.
 *
 * @package App\Models
 */
class Enrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'payment_id',
        'status',
        'enrolled_at',
    ];

    protected static function booted()
    {
        static::updated(function ($enrollment) {
            if ($enrollment->wasChanged('status') && $enrollment->status === self::STATUS_APPROVED) {
                // Pre-load payment to ensure we have bundle_id
                $enrollment->loadMissing('payment');

                if ($enrollment->payment && $enrollment->payment->bundle_id) {
                    $bundleId = $enrollment->payment->bundle_id;
                    $userId = $enrollment->user_id;
                    $paymentId = $enrollment->payment_id;

                    // Fetch all course IDs in this bundle
                    $courseIds = \Illuminate\Support\Facades\DB::table('bundle_courses')
                        ->where('bundle_id', $bundleId)
                        ->pluck('course_id');

                    // Wrap in withoutEvents to prevent infinite recursion
                    static::withoutEvents(function () use ($userId, $courseIds, $paymentId, $enrollment, $bundleId) {
                        // Activate ALL pending enrollments for this user and bundle
                        // Crucially, we also link them to the same payment_id
                        self::where('user_id', $userId)
                            ->whereIn('course_id', $courseIds)
                            ->where('status', self::STATUS_PENDING)
                            ->where('id', '!=', $enrollment->id)
                            ->update([
                                'status' => self::STATUS_APPROVED,
                                'enrolled_at' => now(),
                                'payment_id' => $paymentId,
                            ]);

                        // Complete the BundleEnrollment record if it exists
                        \App\Models\BundleEnrollment::updateOrCreate(
                            ['user_id' => $userId, 'bundle_id' => $bundleId],
                            [
                                'status' => 'approved',
                                'enrolled_at' => now(),
                                'payment_id' => $paymentId,
                            ]
                        );
                    });
                }
            }
        });
    }

    protected $casts = [
        'enrolled_at' => 'datetime',
    ];

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function getCertificateNumberAttribute(): ?string
    {
        if (! $this->id || ! $this->user_id || ! $this->course_id) {
            return null;
        }

        return sprintf(
            'CERT-%06d-%s',
            $this->id,
            strtoupper(substr(md5($this->user_id . $this->course_id), 0, 6))
        );
    }

    public function getCompletionDateAttribute(): ?string
    {
        return $this->updated_at?->format('M d, Y');
    }

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
