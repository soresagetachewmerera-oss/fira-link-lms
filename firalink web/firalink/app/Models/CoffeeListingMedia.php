<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * CoffeeListingMedia Model
 *
 * Represents photos, SCA cupping forms, origin certificates, and lab reports for a coffee listing.
 */
class CoffeeListingMedia extends Model
{
    use HasFactory;

    protected $table = 'coffee_listing_media';

    protected $fillable = [
        'listing_id',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'media_type',
        'is_primary',
        'sort_order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'file_size' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['url'];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(CoffeeListing::class, 'listing_id');
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path;
        }

        return Storage::disk('public')->url($this->file_path);
    }
}

