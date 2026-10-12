<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CoffeeExportCalculation extends Model
{
    protected $table = 'coffee_export_calculations';

    protected $fillable = [
        'user_id',
        'deal_title',
        'coffee_type',
        'quantity_bags',
        'bag_weight_kg',
        'net_weight_kg',
        'net_weight_lbs',
        'selling_price_usd_per_lb',
        'exchange_rate',
        'purchase_cost_etb',
        'processing_cost_etb',
        'packaging_cost_etb',
        'transport_corridor_etb',
        'handling_customs_etb',
        'freight_insurance_usd',
        'total_cost_etb',
        'gross_revenue_usd',
        'gross_revenue_etb',
        'net_profit_etb',
        'net_profit_usd',
        'margin_percentage',
        'break_even_price_usd_per_lb',
        'incoterm',
        'destination_port',
        'assumptions_json',
    ];

    protected $casts = [
        'assumptions_json' => 'array',
        'bag_weight_kg' => 'float',
        'net_weight_kg' => 'float',
        'net_weight_lbs' => 'float',
        'selling_price_usd_per_lb' => 'float',
        'exchange_rate' => 'float',
        'purchase_cost_etb' => 'float',
        'processing_cost_etb' => 'float',
        'packaging_cost_etb' => 'float',
        'transport_corridor_etb' => 'float',
        'handling_customs_etb' => 'float',
        'freight_insurance_usd' => 'float',
        'total_cost_etb' => 'float',
        'gross_revenue_usd' => 'float',
        'gross_revenue_etb' => 'float',
        'net_profit_etb' => 'float',
        'net_profit_usd' => 'float',
        'margin_percentage' => 'float',
        'break_even_price_usd_per_lb' => 'float',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
