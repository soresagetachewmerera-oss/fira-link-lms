<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * One verification document (one or more pages). Review fields (status, reviewed_*, reason,
 * checks, tier) are NOT fillable: they change only through
 * App\Services\Verification\DocumentReviewService, which writes the audit log.
 */
class VerificationDocument extends Model
{
    use HasFactory;

    // Legacy types (before slice 3).
    public const TYPE_COMMERCIAL_REG = 'commercial_reg';
    public const TYPE_SECTOR_LICENSE = 'sector_license';
    public const TYPE_TIN_CERT = 'tin_cert';
    public const TYPE_REP_ID = 'rep_id';
    public const TYPE_ADDRESS_PROOF = 'address_proof';
    public const TYPE_BROKER_MANDATE = 'broker_mandate';

    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_NEEDS_CHANGES = 'needs_changes';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUPERSEDED = 'superseded';
    public const STATUS_LEGACY = 'legacy';

    protected $fillable = [
        'organization_verification_id',
        'organization_id',
        'document_type',
        'file_path',
        'file_disk',
        'original_filename',
        'file_size_kb',
        'mime_type',
        'verification_hash',
        'is_valid',
        'document_number',
        'issued_at',
        'expires_at',
        'details',
        'submitted_by',
        'submitted_at',
    ];

    protected $hidden = ['document_number', 'document_number_hash', 'file_path', 'verification_hash'];

    protected $casts = [
        'is_valid' => 'boolean',
        'file_size_kb' => 'integer',
        'tier' => 'integer',
        'document_number' => 'encrypted',
        'issued_at' => 'date',
        'expires_at' => 'date',
        'details' => 'array',
        'checks' => 'array',
        'reminders_sent' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function verification(): BelongsTo
    {
        return $this->belongsTo(OrganizationVerification::class, 'organization_verification_id');
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(VerificationDocumentPage::class)->orderBy('page_number');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /** Approved and not past its expiry date. */
    public function scopeCountsForTier(Builder $q): Builder
    {
        return $q->where('status', self::STATUS_APPROVED)
            ->where(fn ($e) => $e->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString()));
    }

    /** The document shown for its type: the newest that was not replaced. */
    public function scopeCurrent(Builder $q): Builder
    {
        return $q->whereNotIn('status', [self::STATUS_SUPERSEDED, self::STATUS_LEGACY]);
    }

    public function isExpiredNow(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(now()->startOfDay());
    }

    public function getDownloadUrlAttribute(): string
    {
        return route('marketplace.verification.download', $this->id);
    }

    public function deletePhysicalFile(): bool
    {
        foreach ($this->pages as $page) {
            Storage::disk($page->file_disk)->delete($page->file_path);
        }
        if ($this->file_path && Storage::disk($this->file_disk)->exists($this->file_path)) {
            return Storage::disk($this->file_disk)->delete($this->file_path);
        }

        return false;
    }
}
