<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Payment Gateway Setting Model
 *
 * This model represents a setting for a payment gateway.
 *
 * @package App\Models
 */
class PaymentGatewaySetting extends Model
{
    protected $fillable = [
        'identifier',
        'name',
        'type',
        'credentials',
        'is_enabled',
        'offline_instructions',
    ];

    protected $casts = [
        'credentials' => 'array',
        'is_enabled' => 'bool',
    ];
}
