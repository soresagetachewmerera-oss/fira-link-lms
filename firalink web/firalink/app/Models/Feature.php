<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feature extends Model
{
    use HasFactory;

    const TYPE_LIMIT = 'limit';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_TIER = 'tier';

    protected $fillable = [
        'key',
        'label',
        'type',
        'unit',
        'description',
        'is_available',
    ];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    public function planEntitlements(): HasMany
    {
        return $this->hasMany(PlanEntitlement::class, 'feature_key', 'key');
    }
}
