<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'feature_key',
        'period_key',
        'count',
    ];

    protected $casts = [
        'count' => 'integer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
