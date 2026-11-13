<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Assignment File Model
 *
 * This model represents a file associated with an assignment.
 *
 * @package App\Models
 */
class AssignmentFile extends Model
{
    protected $fillable = [
        'assignment_id',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
    ];

    /**
     * Get the assignment that owns the file.
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * Get the file URL.
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}
