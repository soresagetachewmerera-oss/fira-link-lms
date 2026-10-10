<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Menu Model
 *
 * This model represents a menu.
 *
 * @package App\Models
 */
class Menu extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Menu $menu) {
            if (empty($menu->slug)) {
                $menu->slug = Str::slug($menu->name);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id');
    }

    public function allItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
