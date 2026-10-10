<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Course Model
 *
 * This model represents a course.
 *
 * @package App\Models
 */
class Course extends Model
{
    protected $fillable = [
        'instructor_id',
        'title',
        'description',
        'featured_image',
        'intro_video',
        'visibility',
        'public_course',
        'max_students',
        'difficulty',
        'pricing_model',
        'regular_price',
        'sale_price',
        'schedule_enabled',
        'schedule_date',
        'course_category_id',
        'course_language_id',
        'category',
        'language',
        'tags',
        'requirements',
        'objectives',
        'status',
        'deleted_date',
        'approval_reason',
        'rejection_reason',
        'is_live_course',
    ];

    protected $casts = [
        'public_course' => 'boolean',
        'schedule_enabled' => 'boolean',
        'schedule_date'    => 'datetime',
        'regular_price'    => 'decimal:2',
        'sale_price'       => 'decimal:2',
        'max_students'     => 'integer',
        'deleted_date'     => 'datetime',
        'is_live_course'   => 'boolean',
    ];


    protected $appends = [
        'thumbnail',
        'price',
        'discounted_price',
        'rating',
        'reviews_count',
        'lessons_count',
        'students_count',
        'intro_video_url',
    ];

    /**
     * Get the instructor that owns the course.
     */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CourseCategory::class, 'course_category_id');
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->category();
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(CourseLanguage::class, 'course_language_id');
    }

    /**
     * Get the topics for the course.
     */
    public function topics(): HasMany
    {
        return $this->hasMany(CourseTopic::class)->orderBy('order');
    }

    /**
     * Get the enrollments for the course.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the reviews for the course.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_approved', true)->orderBy('created_at', 'desc');
    }

    /**
     * Get the lessons for the course directly 
     */
    public function lessons()
    {
        return $this->hasManyThrough(Lesson::class, CourseTopic::class);
    }

    /**
     * Get the bundles that include this course.
     */
    public function bundles()
    {
        return $this->belongsToMany(Bundle::class, 'bundle_courses');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    /**
     * Get live classes for this course.
     */
    public function liveClasses(): HasMany
    {
        return $this->hasMany(LiveClass::class)->orderBy('scheduled_at');
    }

    /**
     * Get the next upcoming live class.
     */
    public function nextLiveClass()
    {
        return $this->liveClasses()->upcoming()->first();
    }


    public function getThumbnailAttribute()
    {
        if (!$this->featured_image) {
            return null;
        }
        return asset('storage/' . $this->featured_image);
    }

    public function getPriceAttribute()
    {
        return $this->regular_price;
    }

    public function getDiscountedPriceAttribute()
    {
        return $this->sale_price;
    }

    public function getRatingAttribute()
    {
        // Calculate average rating from reviews if not cached
        return $this->reviews()->avg('rating') ?? 0.0;
    }

    public function getReviewsCountAttribute()
    {
        if (array_key_exists('reviews_count', $this->attributes)) {
            return $this->attributes['reviews_count'];
        }
        return $this->reviews()->count();
    }

    public function getLessonsCountAttribute()
    {
        if (array_key_exists('lessons_count', $this->attributes)) {
            return $this->attributes['lessons_count'];
        }
        return $this->lessons()->count();
    }

    public function getStudentsCountAttribute()
    {
        // Total students enrolled (approved or completed)
        return $this->enrollments()->whereIn('status', ['approved', 'completed'])->count();
    }

    public function subscriptionPlans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'subscription_plan_courses', 'course_id', 'subscription_plan_id')
            ->withTimestamps();
    }

    public function getIntroVideoUrlAttribute()
    {
        if (!$this->intro_video) {
            return null;
        }

        // Generate streaming URL with the video path
        return url('/api/video/stream?path=' . urlencode($this->intro_video));
    }
}
