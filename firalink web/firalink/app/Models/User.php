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
        'active_organization_id',
        'onboarding_intent',
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
        'phone_verified_at',
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
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_approved' => 'boolean',
            'must_reset_password' => 'boolean',
            'balance' => 'decimal:2',
        ];
    }

    /**
     * Determine if the user has verified their phone number.
     */
    public function hasVerifiedPhone(): bool
    {
        return !is_null($this->phone_verified_at);
    }

    /**
     * Change the phone from a profile or settings form (no code involved).
     * A different number is saved UNVERIFIED, so it cannot be used for phone sign-in until it is
     * verified by code (POST /api/user/phone). Returns an error message if the number belongs to
     * another account, otherwise null. Does not save.
     */
    public function setPhoneFromProfile(?string $phone): ?string
    {
        $phone = $phone !== null ? trim($phone) : null;
        if ($phone === '' ) {
            $phone = null;
        }

        $sms = app(\App\Services\SmsEthiopiaService::class);
        $normalized = $phone ? ($sms->normalizePhoneNumber($phone) ?? $phone) : null;
        $current = $this->phone ? ($sms->normalizePhoneNumber($this->phone) ?? $this->phone) : null;

        if ($normalized === $current) {
            return null; // unchanged
        }

        if ($normalized) {
            $variants = array_unique(array_filter([$phone, $normalized, '0' . substr($normalized, 3), '+' . $normalized]));
            $taken = static::whereIn('phone', $variants)->where('id', '!=', $this->id)->exists();
            if ($taken) {
                return 'This phone number is linked to another account.';
            }
        }

        $this->phone = $normalized;
        $this->phone_verified_at = null;

        return null;
    }

    /**
     * Mark the user's phone number as verified.
     */
    public function markPhoneAsVerified(): bool
    {
        return $this->forceFill([
            'phone_verified_at' => $this->freshTimestamp(),
        ])->save();
    }

    protected $appends = [
        'courses_count',
        'average_rating',
        'profile_photo_url',
    ];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo)) {
            return asset('assets/front/img/home-1/courses/client-01.png');
        }
        if (str_starts_with($this->profile_photo, 'http://') || str_starts_with($this->profile_photo, 'https://')) {
            return $this->profile_photo;
        }
        return asset('storage/' . ltrim($this->profile_photo, '/'));
    }

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
     * Active subscription fallback (subscription system deprecated/removed)
     */
    public function activeSubscription()
    {
        return null;
    }

    /**
     * Subscriptions fallback relation (subscription system deprecated/removed)
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Enrollment::class)->whereRaw('0 = 1');
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
     * Get tour bookings made by the user.
     */
    public function tourBookings(): HasMany
    {
        return $this->hasMany(TourBooking::class);
    }

    /**
     * Get registered push notification device tokens for the user.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(UserDeviceToken::class);
    }

    // ── Coffee B2B Marketplace Relationships ──

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(MarketplaceSellerProfile::class);
    }

    public function coffeeListings(): HasMany
    {
        return $this->hasMany(CoffeeListing::class, 'seller_id');
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class, 'organization_memberships')
            ->withPivot('role', 'is_active', 'joined_at')
            ->withTimestamps();
    }

    public function organizationMemberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function primaryOrganization(): ?Organization
    {
        if ($this->sellerProfile?->organization) {
            return $this->sellerProfile->organization;
        }

        $org = $this->organizations()->wherePivot('is_active', true)->first();
        if ($org) {
            return $org;
        }

        // Auto-provision an organization for the user if none exists
        $name = trim($this->name ?: 'Business Entity');
        $slug = \Illuminate\Support\Str::slug($name);
        if (empty($slug)) {
            $slug = 'business-' . $this->id;
        } elseif (\App\Models\Organization::where('slug', $slug)->exists()) {
            $slug .= '-' . $this->id;
        }

        $org = \App\Models\Organization::create([
            'name' => $name,
            'slug' => $slug,
            'country' => 'Ethiopia',
            'status' => 'active',
            'is_verified' => false,
            'verified_roles' => [],
        ]);

        \App\Models\OrganizationMembership::create([
            'organization_id' => $org->id,
            'user_id' => $this->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        return $org;
    }

    public function activeOrganization(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'active_organization_id');
    }

    public function currentOrganization(): ?Organization
    {
        if ($this->active_organization_id) {
            $activeOrg = Organization::find($this->active_organization_id);
            if ($activeOrg) {
                return $activeOrg;
            }
        }
        return $this->primaryOrganization();
    }

    public function switchOrganization(int $organizationId): bool
    {
        $hasAccess = $this->organizations()->where('organizations.id', $organizationId)->wherePivot('is_active', true)->exists();
        if (!$hasAccess && !$this->isAdmin()) {
            return false;
        }

        $this->active_organization_id = $organizationId;
        return $this->save();
    }

    public function marketplaceRfqs(): HasMany
    {
        return $this->hasMany(MarketplaceRfq::class, 'buyer_id');
    }

    public function marketplaceQuotations(): HasMany
    {
        return $this->hasMany(MarketplaceQuotation::class, 'seller_id');
    }

    public function marketplaceSubscriptions(): HasMany
    {
        return $this->hasMany(UserMarketplaceSubscription::class);
    }

    public function activeMarketplaceSubscription(): HasOne
    {
        return $this->hasOne(UserMarketplaceSubscription::class)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latestOfMany();
    }

    public function savedCoffeeListings(): BelongsToMany
    {
        return $this->belongsToMany(CoffeeListing::class, 'marketplace_saved_listings', 'user_id', 'listing_id')
            ->withTimestamps();
    }

    public function isVerifiedSeller(): bool
    {
        $org = $this->primaryOrganization();
        if ($org && ($org->isVerifiedAs('exporter') || $org->isVerifiedAs('supplier'))) {
            return true;
        }

        return $this->sellerProfile?->isVerified() ?? false;
    }

    public function isVerifiedExporter(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $org = $this->primaryOrganization();
        if ($org && $org->isVerifiedAs('exporter')) {
            return true;
        }

        return $this->sellerProfile?->isVerified() ?? false;
    }

    public function isVerifiedBuyer(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $org = $this->primaryOrganization();
        return $org?->isVerifiedAs('buyer') ?? false;
    }

    public function isVerifiedSupplier(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $org = $this->primaryOrganization();
        return $org?->isVerifiedAs('supplier') ?? false;
    }

    public function isVerifiedBroker(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $org = $this->primaryOrganization();
        return $org?->isVerifiedAs('broker') ?? false;
    }

    public function hasActiveMarketplaceSubscription(): bool
    {
        return $this->activeMarketplaceSubscription !== null;
    }

    public function canPostMarketplaceListing(): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $sub = $this->activeMarketplaceSubscription;
        if (!$sub || !$sub->plan) {
            return $this->coffeeListings()->count() < 1;
        }

        $limit = $sub->plan->active_listings_limit;
        if ($limit === 0) {
            return true;
        }

        return $this->coffeeListings()->where('status', 'active')->count() < $limit;
    }
}
