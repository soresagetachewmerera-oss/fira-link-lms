<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Custom Page Model
 *
 * This model represents a custom page.
 *
 * @package App\Models
 */
class CustomPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'content',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopeSlug($query, string $slug)
    {
        return $query->where('slug', $slug);
    }
}

