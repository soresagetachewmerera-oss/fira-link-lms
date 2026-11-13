<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DocumentTemplate extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'supported_formats' => 'array',
        'is_free' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['template_code'];

    public function getTemplateCodeAttribute(): ?string
    {
        return $this->attributes['code'] ?? null;
    }

    public function setTemplateCodeAttribute($value): void
    {
        $this->attributes['code'] = $value;
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentTemplateVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentTemplateVersion::class)->where('is_current', true)->latestOfMany();
    }

    public function activeVersion(): HasOne
    {
        return $this->hasOne(DocumentTemplateVersion::class)->where('is_current', true)->latestOfMany();
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
