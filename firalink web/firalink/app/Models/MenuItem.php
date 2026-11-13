<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Menu Item Model
 *
 * This model represents a menu item.
 *
 * @package App\Models
 */
class MenuItem extends Model
{
    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'url',
        'route_name',
        'route_parameters',
        'target',
        'is_active',
        'order',
    ];

    protected $casts = [
        'route_parameters' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    protected $appends = ['resolved_url'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getResolvedUrlAttribute(): string
    {
        if ($this->url) {
            $url = trim($this->url);

            // Strip localhost / 127.0.0.1 and any port, resolving with dynamic application URL
            if (preg_match('#^https?://(?:127\.0\.0\.1|localhost)(?::\d+)?(/.*)?$#i', $url, $matches)) {
                $path = $matches[1] ?? '/';
                return url($path);
            }

            // If it starts with '/', format with dynamic url()
            if (str_starts_with($url, '/')) {
                return url($url);
            }

            return $url;
        }

        if ($this->route_name) {
            try {
                return route($this->route_name, $this->route_parameters ?? []);
            } catch (\Throwable $exception) {
                return '#';
            }
        }

        return '#';
    }
}
