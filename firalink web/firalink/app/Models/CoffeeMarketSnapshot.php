<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoffeeMarketSnapshot extends Model
{
    protected $table = 'coffee_market_snapshots';

    protected $fillable = [
        'observed_at',
        'source',
        'ico_composite_usc_lb',
        'ico_composite_usd_lb',
        'payload_json',
    ];

    protected $casts = [
        'observed_at' => 'date',
        'ico_composite_usc_lb' => 'float',
        'ico_composite_usd_lb' => 'float',
    ];
}
