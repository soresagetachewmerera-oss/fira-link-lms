<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentPackage extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(ExportShipment::class, 'export_shipment_id');
    }

    public function packageItems(): HasMany
    {
        return $this->hasMany(DocumentPackageItem::class)->orderBy('sort_order');
    }

    public function items(): HasMany
    {
        return $this->packageItems();
    }
}
