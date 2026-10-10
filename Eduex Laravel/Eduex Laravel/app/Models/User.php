<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

use Illuminate\Contracts\Auth\MustVerifyEmail;

/**
 * User Model
 *
 * This model represents a user.
 *
 * @package App\Models
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * Send the email verification notification.
     *
     * @return void
     */
    public function sendEmailVerificationNotification()
    {
        $verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
            'verification.verify',
            \Illuminate\Support\Carbon::now()->addMinutes(config('auth.verification.expire', 60)),
            [
                'id' => $this->getKey(),
                'hash' => sha1($this->getEmailForVerification()),
            ]
        );

        \Illuminate\Support\Facades\Mail::to($this)->send(new \App\Mail\VerifyEmailMail($this, $verificationUrl));
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_approved',
        'bio',
        'phone',
        'specialization',
        'years_experience',
        'previous_experience',
        'highest_degree',
        'field_of_study',
        'certifications',
        'profile_photo',
        'resume',
        'linkedin',
        'twitter',
        'facebook',
        'youtube',
        'approval_reason',
        'rejection_reason',
        'balance',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'balance' => 'decimal:2',
        ];
    }

    protected $appends = [
        'courses_count',
        'average_rating',
    ];

    public function getCoursesCountAttribute()
    {
        return $this->courses()->where('status', 'published')->count();
    }

    public function getAverageRatingAttribute()
    {
        return round($this->courses()->withAvg('reviews', 'rating')->get()->avg('reviews_avg_rating') ?? 0.0, 1);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is instructor
     */
    public function isInstructor(): bool
    {
        return $this->role === 'instructor';
    }

    /**
     * Check if user is student
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if instructor is approved
     */
    public function isApprovedInstructor(): bool
    {
        return $this->isInstructor() && $this->is_approved;
    }

    /**
     * Get the courses for the instructor.
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'instructor_id');
    }

    public function instructedEvents(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_instructor', 'instructor_id', 'event_id')->withTimestamps();
    }

    public function eventBookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    /**
     * Get the enrollments for the user.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the lesson progress records for the user.
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get the quiz attempts for the user.
     */
    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Get the assignment submissions for the user.
     */
    public function assignmentSubmissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    /**
     * Get the reviews written by the user.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function accountDeletionRequests(): HasMany
    {
        return $this->hasMany(AccountDeletionRequest::class);
    }

    public function notificationSettings(): HasOne
    {
        return $this->hasOne(UserNotificationSetting::class);
    }

    public function instructorWithdrawals(): HasMany
    {
        return $this->hasMany(InstructorWithdrawal::class, 'instructor_id');
    }

    public function communityQuestions(): HasMany
    {
        return $this->hasMany(CommunityQuestion::class);
    }

    public function communityAnswers(): HasMany
    {
        return $this->hasMany(CommunityAnswer::class);
    }

    /**
     * Get conversations where user is the student.
     */
    public function studentConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'student_id');
    }

    /**
     * Get conversations where user is the instructor.
     */
    public function instructorConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'instructor_id');
    }

    /**
     * Get all subscriptions for the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get active subscription for the user.
     */
    public function activeSubscription(): ?UserSubscription
    {
        /** @var UserSubscription|null $subscription */
        $subscription = $this->subscriptions()
            ->with(['plan.courses', 'plan.bundles'])
            ->where('status', UserSubscription::STATUS_ACTIVE)
            ->where(function ($query) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', now());
            })
            ->latest('id')
            ->first();

        return $subscription;
    }

    /**
     * Check if user has access to a specific course via an active subscription.
     */
    public function hasAccessToCourseViaSubscription(int $courseId): bool
    {
        $activeSub = $this->activeSubscription();
        if (!$activeSub) {
            return false;
        }

        return $activeSub->includesCourse($courseId);
    }

    /**
     * Check if user has access to a specific bundle via an active subscription.
     */
    public function hasAccessToBundleViaSubscription(int $bundleId): bool
    {
        $activeSub = $this->activeSubscription();
        if (!$activeSub) {
            return false;
        }

        return $activeSub->includesBundle($bundleId);
    }
}
