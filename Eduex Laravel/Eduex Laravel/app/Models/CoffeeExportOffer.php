<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class CoffeeExportOffer extends Model
{
    protected $table = 'coffee_export_offers';

    protected $fillable = [
        'user_id',
        'reference_no',
        'exporter_name',
        'exporter_contact',
        'origin_region',
        'sub_region',
        'washing_station',
        'grade',
        'variety',
        'processing_method',
        'screen_size',
        'cupping_score',
        'moisture_content',
        'crop_year',
        'quantity_bags',
        'bag_type',
        'price_usd_per_lb',
        'incoterm',
        'port_of_loading',
        'port_of_destination',
        'payment_terms',
        'validity_date',
        'tasting_notes',
        'additional_terms',
        'status',
        'pdf_path',
    ];

    protected $casts = [
        'cupping_score' => 'float',
        'moisture_content' => 'float',
        'price_usd_per_lb' => 'float',
        'quantity_bags' => 'integer',
        'validity_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
