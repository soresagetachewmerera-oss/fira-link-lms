<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BannedIdentity extends Model
{
    use HasFactory;

    protected $fillable = [
        'tin_number',
        'license_number',
        'company_name',
        'reason',
        'banned_by',
        'banned_at',
    ];

    protected $casts = [
        'banned_at' => 'datetime',
    ];

    public function banner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public static function isBanned(?string $tin, ?string $license): bool
    {
        if (empty($tin) && empty($license)) {
            return false;
        }

        return self::where(function ($q) use ($tin, $license) {
            if ($tin) {
                $q->where('tin_number', trim($tin));
            }
            if ($license) {
                $q->orWhere('license_number', trim($license));
            }
        })->exists();
    }
}
