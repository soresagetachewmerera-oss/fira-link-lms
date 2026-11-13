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
        $val = optional(self::query()->where('key', $key)->first())->value ?? $default;
        return self::sanitizeUrls($val);
    }

    public static function setValue(string $key, $value): self
    {
        return self::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Recursively replace local development URLs (127.0.0.1 / localhost)
     * with the appropriate current dynamic application URL.
     */
    public static function sanitizeUrls($data)
    {
        if (is_string($data)) {
            if (preg_match('#^https?://(?:127\.0\.0\.1|localhost)(?::\d+)?(/.*)?$#i', $data, $matches)) {
                $path = $matches[1] ?? '/';
                return url($path);
            }
            return $data;
        }

        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = self::sanitizeUrls($v);
            }
            return $data;
        }

        return $data;
    }
}
