<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoffeeMinimumPriceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'dataset_id',
        'coffee_type',
        'origin',
        'grade',
        'processing_method',
        'minimum_price',
        'price_unit',
        'currency',
        'effective_date',
        'previous_price',
        'price_change',
        'price_change_percentage',
        'price_change_direction',
        'status',
        'raw_data',
        'notes',
    ];

    protected $casts = [
        'minimum_price'           => 'float',
        'previous_price'          => 'float',
        'price_change'            => 'float',
        'price_change_percentage' => 'float',
        'effective_date'          => 'date',
        'raw_data'                => 'array',
    ];

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(CoffeeMinimumPriceDataset::class, 'dataset_id');
    }
}
