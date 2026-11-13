<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoffeeMarketBenchmark extends Model
{
    protected $table = 'coffee_market_benchmarks';

    protected $fillable = [
        'benchmark_name',
        'grade_description',
        'arabica_c_cents_lb',
        'differential_cents_lb',
        'indicative_fob_usd_lb',
        'ecx_reference_etb_per_kg',
        'trend',
        'source',
        'market_date',
    ];

    protected $casts = [
        'arabica_c_cents_lb' => 'float',
        'differential_cents_lb' => 'float',
        'indicative_fob_usd_lb' => 'float',
        'ecx_reference_etb_per_kg' => 'float',
        'market_date' => 'date',
    ];
}
