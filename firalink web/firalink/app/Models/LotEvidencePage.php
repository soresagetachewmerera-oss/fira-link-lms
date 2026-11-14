<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One page (photo or report page) of lot evidence, on the private verification disk. */
class LotEvidencePage extends Model
{
    protected $fillable = [
        'lot_evidence_id',
        'page_number',
        'file_path',
        'file_disk',
        'mime_type',
        'file_size_kb',
        'sha256',
    ];

    protected $hidden = ['file_path', 'sha256'];

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(LotEvidence::class, 'lot_evidence_id');
    }
}
