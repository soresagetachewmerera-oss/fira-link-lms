<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * An admin-managed kind of coffee business (Alami, Akrabi Laki, union, roaster, ...). A label only:
 * rights come from the activities it allows (App\Support\Capability). The code is never renamed.
 */
class BusinessCategory extends Model
{
    protected $fillable = [
        'label_en', 'label_am', 'description_en', 'description_am', 'business_role',
        'capabilities', 'legally_confirmed', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'legally_confirmed' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function organizations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Organization::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** @return list<string> */
    public function allowedCapabilities(): array
    {
        return array_values(array_intersect(\App\Support\Capability::values(), (array) $this->capabilities));
    }

    public function label(?string $locale = null): string
    {
        return ($locale ?? app()->getLocale()) === 'am' && $this->label_am ? $this->label_am : $this->label_en;
    }

    public function toOption(): array
    {
        return [
            'code' => $this->code,
            'label_en' => $this->label_en,
            'label_am' => $this->label_am,
            'description_en' => $this->description_en,
            'description_am' => $this->description_am,
            'business_role' => $this->business_role,
            'capabilities' => $this->allowedCapabilities(),
            'legally_confirmed' => (bool) $this->legally_confirmed,
        ];
    }
}
