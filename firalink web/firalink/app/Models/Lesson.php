<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Lesson Model
 *
 * This model represents a lesson for a course.
 *
 * @package App\Models
 */
class Lesson extends Model
{
    protected $fillable = [
        'course_topic_id',
        'title',
        'description',
        'video_type',
        'video_url',
        'video_path',
        'duration',
        'is_preview',
        'is_live',
        'live_class_id',
        'order',
    ];

    protected $appends = [
        'video_url',
    ];

    protected $casts = [
        'duration'      => 'integer',
        'is_preview'    => 'boolean',
        'is_live'       => 'boolean',
        'live_class_id' => 'integer',
        'order'         => 'integer',
    ];

    /**
     * Get the topic that owns the lesson.
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(CourseTopic::class, 'course_topic_id');
    }

    /**
     * Get the live class linked to this lesson.
     */
    public function liveClass(): BelongsTo
    {
        return $this->belongsTo(LiveClass::class, 'live_class_id');
    }

    /**
     * Get the progress records for the lesson.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function getVideoUrlAttribute(): ?string
    {
        // If it's an external URL (YouTube/Vimeo), return the raw URL from attributes
        if ($this->video_type === 'url' || $this->video_type === 'youtube' || $this->video_type === 'vimeo') {
            return $this->attributes['video_url'] ?? null;
        }

        // If it's a local path, generate the streaming URL
        if ($this->video_path) {
            $path = ltrim($this->video_path, '/');
            return url('/api/video/stream?path=' . urlencode($path));
        }

        return null;
    }

    /**
     * Get formatted duration as hours:minutes
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration || $this->duration <= 0) {
            return '';
        }

        $hours = floor($this->duration / 60);
        $minutes = $this->duration % 60;

        if ($hours > 0) {
            return $hours . ':' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
        }

        return '0:' . str_pad($minutes, 2, '0', STR_PAD_LEFT);
    }
}
