<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportShipment extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'contract_date' => 'date',
        'lc_date' => 'date',
        'lc_expiry_date' => 'date',
        'shipped_on_board_date' => 'date',
        'total_bags' => 'integer',
        'bag_weight_kg' => 'decimal:2',
        'tare_weight_per_bag_kg' => 'decimal:2',
        'total_net_weight_kg' => 'decimal:2',
        'total_gross_weight_kg' => 'decimal:2',
        'total_net_weight_lbs' => 'decimal:2',
        'unit_price_usd_per_lb' => 'decimal:4',
        'unit_price_usd_per_mt' => 'decimal:2',
        'total_value_usd' => 'decimal:2',
    ];

    protected $appends = ['reference_number'];

    public function getReferenceNumberAttribute(): ?string
    {
        return $this->attributes['shipment_reference'] ?? null;
    }

    public function setReferenceNumberAttribute($value): void
    {
        $this->attributes['shipment_reference'] = $value;
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExportShipmentItem::class)->orderBy('item_number');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function documents(): HasMany
    {
        return $this->generatedDocuments();
    }

    public function documentPackages(): HasMany
    {
        return $this->hasMany(DocumentPackage::class);
    }

    /**
     * Recompute totals deterministically from bags, weights, and unit prices.
     */
    public function recalculateTotals(): void
    {
        $kgToLbs = 2.20462262;

        if ($this->items()->exists()) {
            $totalBags = $this->items()->sum('bag_count');
            $netKg = $this->items()->sum('net_weight_kg');
            $grossKg = $this->items()->sum('gross_weight_kg');
            $netLbs = $netKg * $kgToLbs;
            $totalVal = $this->items()->sum('total_price');

            $this->update([
                'total_bags' => $totalBags,
                'total_net_weight_kg' => round($netKg, 2),
                'total_gross_weight_kg' => round($grossKg, 2),
                'total_net_weight_lbs' => round($netLbs, 2),
                'total_value_usd' => round($totalVal, 2),
            ]);
        } else {
            $bags = max(1, (int)$this->total_bags);
            $bagWeight = (float)($this->bag_weight_kg ?: 60.0);
            $tare = (float)($this->tare_weight_per_bag_kg ?: 0.80);

            $netKg = $bags * $bagWeight;
            $grossKg = $netKg + ($bags * $tare);
            $netLbs = $netKg * $kgToLbs;

            $val = 0.0;
            if ($this->unit_price_usd_per_lb && $this->unit_price_usd_per_lb > 0) {
                $val = $netLbs * (float)$this->unit_price_usd_per_lb;
            } elseif ($this->unit_price_usd_per_mt && $this->unit_price_usd_per_mt > 0) {
                $val = ($netKg / 1000.0) * (float)$this->unit_price_usd_per_mt;
            }

            $this->update([
                'total_net_weight_kg' => round($netKg, 2),
                'total_gross_weight_kg' => round($grossKg, 2),
                'total_net_weight_lbs' => round($netLbs, 2),
                'total_value_usd' => round($val, 2),
            ]);
        }
    }
}
