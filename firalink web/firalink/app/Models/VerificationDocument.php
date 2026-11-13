<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class VerificationDocument extends Model
{
    use HasFactory;

    public const TYPE_COMMERCIAL_REG = 'commercial_reg';
    public const TYPE_SECTOR_LICENSE = 'sector_license';
    public const TYPE_TIN_CERT = 'tin_cert';
    public const TYPE_REP_ID = 'rep_id';
    public const TYPE_ADDRESS_PROOF = 'address_proof';
    public const TYPE_BROKER_MANDATE = 'broker_mandate';

    protected $fillable = [
        'organization_verification_id',
        'document_type',
        'file_path',
        'file_disk',
        'original_filename',
        'file_size_kb',
        'mime_type',
        'verification_hash',
        'is_valid',
    ];

    protected $casts = [
        'is_valid' => 'boolean',
        'file_size_kb' => 'integer',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(OrganizationVerification::class, 'organization_verification_id');
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('marketplace.verification.download', $this->id);
    }

    public function deletePhysicalFile(): bool
    {
        if (Storage::disk($this->file_disk)->exists($this->file_path)) {
            return Storage::disk($this->file_disk)->delete($this->file_path);
        }

        return false;
    }
}

