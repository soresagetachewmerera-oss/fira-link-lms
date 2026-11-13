<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform Setting Model
 *
 * This model represents a setting for the platform.
 *
 * @package App\Models
 */
class PlatformSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'group',
        'key',
        'type',
        'payload',
        'is_encrypted',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_encrypted' => 'boolean',
    ];

    public function getValue(string $default = null)
    {
        return $this->payload['value'] ?? $default;
    }
}
