<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Tour extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'destination',
        'meeting_point',
        'duration_days',
        'duration_hours',
        'start_date',
        'end_date',
        'departure_time',
        'total_seats',
        'price',
        'currency',
        'category',
        'difficulty_level',
        'featured_image',
        'gallery',
        'itinerary',
        'included_services',
        'exclusions',
        'requirements',
        'cancellation_policy',
        'available_schedules',
        'is_published',
        'is_featured',
        'metadata',
    ];

    protected $casts = [
        'duration_days' => 'int',
        'duration_hours' => 'int',
        'total_seats' => 'int',
        'price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_published' => 'bool',
        'is_featured' => 'bool',
        'gallery' => 'array',
        'itinerary' => 'array',
        'included_services' => 'array',
        'exclusions' => 'array',
        'requirements' => 'array',
        'available_schedules' => 'array',
        'metadata' => 'array',
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(TourBooking::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function remainingSeats(): int
    {
        if (is_null($this->total_seats) || $this->total_seats <= 0) {
            return 0;
        }

        $reserved = $this->bookings()
            ->whereIn('status', [TourBooking::STATUS_PENDING, TourBooking::STATUS_CONFIRMED])
            ->sum('participants');

        return max(0, $this->total_seats - (int)$reserved);
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (!$this->featured_image) {
            return asset('assets/front/img/event/event-1.jpg');
        }

        if (str_starts_with($this->featured_image, 'http')) {
            return $this->featured_image;
        }

        return asset('storage/' . $this->featured_image);
    }
}
