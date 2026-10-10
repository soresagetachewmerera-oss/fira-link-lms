<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * Blog Post Model
 *
 * This model represents a blog post.
 *
 * @package App\Models
 */
class BlogPost extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'category',
        'blog_category_id',
        'author_name',
        'excerpt',
        'content',
        'featured_image',
        'tags',
        'is_published',
        'published_at',
        'meta',
        'reading_time_minutes',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_published' => 'bool',
        'published_at' => 'datetime',
        'meta' => 'array',
    ];

    protected $attributes = [
        'tags' => '[]',
        'meta' => '{}',
    ];

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function categoryRelation(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'blog_category_id');
    }

    public function getCategoryLabelAttribute(): ?string
    {
        return $this->categoryRelation?->name ?? $this->category;
    }

    public function getFeaturedImageUrlAttribute(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        if (preg_match('#^https?://#i', $this->featured_image)) {
            return $this->featured_image;
        }

        return asset($this->featured_image);
    }

    public function getMetaTitleAttribute(): ?string
    {
        return Arr::get($this->meta, 'title');
    }

    public function getMetaDescriptionAttribute(): ?string
    {
        return Arr::get($this->meta, 'description');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(BlogComment::class);
    }

    public function approvedComments(): HasMany
    {
        return $this->comments()->approved()->orderBy('created_at');
    }
}
