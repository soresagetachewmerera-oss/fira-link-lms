<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Event Speaker Model
 *
 * This model represents a speaker for an event.
 *
 * @package App\Models
 */
class EventSpeaker extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'title',
        'image_path',
        'email',
        'phone',
        'social_links',
        'bio',
        'position',
    ];

    protected $casts = [
        'social_links' => 'array',
        'position' => 'int',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
