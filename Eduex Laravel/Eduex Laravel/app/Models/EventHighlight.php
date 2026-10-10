<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Event Highlight Model
 *
 * This model represents a highlight for an event.
 *
 * @package App\Models
 */
class EventHighlight extends Model
{
    protected $fillable = [
        'event_id',
        'title',
        'description',
        'position',
    ];

    protected $casts = [
        'position' => 'int',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
