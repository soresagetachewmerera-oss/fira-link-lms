<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportShipmentItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'item_number' => 'integer',
        'bag_count' => 'integer',
        'unit_weight_kg' => 'decimal:2',
        'net_weight_kg' => 'decimal:2',
        'gross_weight_kg' => 'decimal:2',
        'net_weight_lbs' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:2',
    ];

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ExportShipment::class, 'export_shipment_id');
    }
}

