<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Course Category Model
 *
 * This model represents a category for courses.
 *
 * @package App\Models
 */
class CourseCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
        'description',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (CourseCategory $category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class, 'course_category_id');
    }
}
