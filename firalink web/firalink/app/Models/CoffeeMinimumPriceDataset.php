<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoffeeMinimumPriceDataset extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'source',
        'file_name',
        'file_path',
        'effective_date',
        'version',
        'total_records',
        'valid_records',
        'duplicate_records',
        'review_required_records',
        'status',
        'column_mapping',
        'ai_analysis',
        'ai_analyzed_at',
        'created_by',
    ];

    protected $casts = [
        'effective_date'   => 'date',
        'column_mapping'   => 'array',
        'ai_analysis'      => 'array',
        'ai_analyzed_at'   => 'datetime',
        'total_records'    => 'integer',
        'valid_records'    => 'integer',
        'duplicate_records'=> 'integer',
        'review_required_records' => 'integer',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(CoffeeMinimumPriceRecord::class, 'dataset_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Compute real statistics from actual stored records
     */
    public function computeSnapshot(): array
    {
        $validRecords = $this->records()->where('status', 'valid')->get();

        if ($validRecords->isEmpty()) {
            return [
                'records_count'    => 0,
                'highest_price'    => null,
                'lowest_price'     => null,
                'average_price'    => null,
                'currency'         => 'USD',
                'unit'             => 'USD/lb',
                'effective_date'   => $this->effective_date?->toDateString(),
                'latest_update'    => $this->updated_at?->toDateString(),
            ];
        }

        $highestRecord = $validRecords->sortByDesc('minimum_price')->first();
        $lowestRecord  = $validRecords->sortBy('minimum_price')->first();
        $avgPrice      = $validRecords->avg('minimum_price');
        $highestPrice  = (float) $highestRecord->minimum_price;
        $lowestPrice   = (float) $lowestRecord->minimum_price;

        return [
            'records_count'        => $validRecords->count(),
            'records_analyzed'     => $validRecords->count(),
            'highest_price'        => $highestPrice,
            'highest_record'       => [
                'coffee_type' => $highestRecord->coffee_type,
                'origin'      => $highestRecord->origin,
                'grade'       => $highestRecord->grade,
                'price'       => $highestPrice,
            ],
            'lowest_price'         => $lowestPrice,
            'lowest_record'        => [
                'coffee_type' => $lowestRecord->coffee_type,
                'origin'      => $lowestRecord->origin,
                'grade'       => $lowestRecord->grade,
                'price'       => $lowestPrice,
            ],
            'average_price'        => round((float) $avgPrice, 4),
            'price_spread'         => round($highestPrice - $lowestPrice, 4),
            'currency'             => $validRecords->first()->currency ?? 'USD',
            'unit'                 => $validRecords->first()->price_unit ?? 'USD/lb',
            'effective_date'       => $this->effective_date?->toDateString(),
            'latest_update'        => $this->updated_at?->toDateString(),
        ];
    }
}
