<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplateVersion extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'schema_definition' => 'array',
        'sample_data' => 'array',
        'layout_config' => 'array',
        'is_current' => 'boolean',
    ];

    public function getDefaultSampleDataAttribute(): ?array
    {
        return $this->sample_data;
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }
}
