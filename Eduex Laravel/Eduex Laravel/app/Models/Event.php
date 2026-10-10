<?php

namespace App\Models;

use App\Models\EventBooking;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collections\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Event Model
 *
 * This model represents an event.
 *
 * @package App\Models
 */
class Event extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'description',
        'location_description',
        'location',
        'contact_phone',
        'contact_email',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'total_seats',
        'price',
        'is_published',
        'metadata',
        'featured_image',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'total_seats' => 'int',
        'price' => 'decimal:2',
        'is_published' => 'bool',
        'metadata' => 'array',
    ];

    public function highlights(): HasMany
    {
        return $this->hasMany(EventHighlight::class)->orderBy('position');
    }

    public function speakers(): HasMany
    {
        return $this->hasMany(EventSpeaker::class)->orderBy('position');
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'event_instructor', 'event_id', 'instructor_id')->withTimestamps();
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(EventBooking::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function remainingSeats(): ?int
    {
        if (is_null($this->total_seats)) {
            return null;
        }

        $booked = $this->bookings()->whereIn('status', [EventBooking::STATUS_PENDING, EventBooking::STATUS_CONFIRMED])->sum('seats');

        return max($this->total_seats - $booked, 0);
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if ($this->featured_image) {
            return asset('storage/' . $this->featured_image);
        }

        return null;
    }
}
