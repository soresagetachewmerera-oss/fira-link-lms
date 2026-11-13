<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider',
        'provider_ref',
        'idempotency_key',
        'payload',
        'headers',
        'status',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'headers' => 'array',
    ];
}
