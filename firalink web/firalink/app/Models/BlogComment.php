<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Blog Comment Model
 *
 * This model represents a comment on a blog post.
 *
 * @package App\Models
 */
class BlogComment extends Model
{
    protected $fillable = [
        'blog_post_id',
        'parent_id',
        'author_name',
        'author_email',
        'author_website',
        'body',
        'is_approved',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'is_approved' => 'bool',
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('created_at');
    }
}
