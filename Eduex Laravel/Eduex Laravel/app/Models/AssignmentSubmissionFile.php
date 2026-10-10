<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Assignment Submission File Model
 *
 * This model represents a file associated with an assignment submission.
 *
 * @package App\Models
 */
class AssignmentSubmissionFile extends Model
{
    protected $fillable = [
        'assignment_submission_id',
        'file_path',
        'original_filename',
        'file_size',
        'mime_type',
    ];

    public function assignmentSubmission(): BelongsTo
    {
        return $this->belongsTo(AssignmentSubmission::class);
    }

    public function getFileUrlAttribute(): string
    {
        $path = $this->file_path;

        if (empty($path)) {
            return '#';
        }

        if (Str::startsWith($path, ['http://', 'https://', '//'])) {
            return $path;
        }

        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if ($disk->exists($path)) {
            return $disk->url($path);
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
