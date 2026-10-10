<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Frontend Setting Model
 *
 * This model represents a setting for the frontend.
 *
 * @package App\Models
 */
class FrontendSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public static function getValue(string $key, $default = null)
    {
        return optional(self::query()->where('key', $key)->first())->value ?? $default;
    }

    public static function setValue(string $key, $value): self
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
