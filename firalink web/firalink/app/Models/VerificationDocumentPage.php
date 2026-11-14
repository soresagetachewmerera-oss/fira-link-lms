<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One page (image or PDF) of a verification document, on the private verification disk. */
class VerificationDocumentPage extends Model
{
    protected $fillable = [
        'verification_document_id',
        'page_number',
        'file_path',
        'file_disk',
        'mime_type',
        'file_size_kb',
        'sha256',
    ];

    protected $hidden = ['file_path', 'sha256'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(VerificationDocument::class, 'verification_document_id');
    }
}
