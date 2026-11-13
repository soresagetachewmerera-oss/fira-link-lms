<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentPackageItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function package(): BelongsTo
    {
        return $this->belongsTo(DocumentPackage::class, 'document_package_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(GeneratedDocument::class, 'generated_document_id');
    }

    public function generatedDocument(): BelongsTo
    {
        return $this->document();
    }
}
