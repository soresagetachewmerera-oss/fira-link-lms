<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Bundle Model
 *
 * This model represents a course bundle, sold together.
 */
class Bundle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'title',
        'description',
        'featured_image',
        'price',
        'status',          // 'active', 'inactive'
        'approval_status', // 'pending', 'approved', 'rejected'
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    protected $appends = [
        'thumbnail',
        'image',
    ];

    /**
     * Get the vendor (admin or instructor) that created the bundle.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Get the courses that are part of this bundle.
     */
    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(Course::class, 'bundle_courses');
    }

    /**
     * Get the enrollments for this bundle.
     */
    public function bundleEnrollments(): HasMany
    {
        return $this->hasMany(BundleEnrollment::class);
    }

    public function getThumbnailAttribute()
    {
        if (!$this->featured_image) {
            return null;
        }
        return asset('storage/' . $this->featured_image);
    }

    public function getImageAttribute()
    {
        return $this->thumbnail;
    }

    public function subscriptionPlans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'subscription_plan_bundles', 'bundle_id', 'subscription_plan_id')
            ->withTimestamps();
    }
}
