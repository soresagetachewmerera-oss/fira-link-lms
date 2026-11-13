<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationVerification extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_REVIEW = 'pending_review';
    public const STATUS_UNDER_SCRUTINY = 'under_scrutiny';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_SUSPENDED = 'suspended';

    public const ROLE_EXPORTER = 'exporter';
    public const ROLE_SUPPLIER = 'supplier';
    public const ROLE_BUYER = 'buyer';
    public const ROLE_BROKER = 'broker';

    /**
     * status, reviewed_by, reviewed_at, admin_notes and rejection_reason are NOT fillable:
     * they change only through App\Services\Verification\VerificationReviewService.
     */
    protected $fillable = [
        'organization_id',
        'target_role',
        'tin_number',
        'license_number',
        'license_issued_at',
        'license_expires_at',
        'issuing_authority',
        'verified_attributes',
        'broker_principal_org_id',
        'broker_mandate_expires_at',
    ];

    protected $casts = [
        'license_issued_at' => 'date',
        'license_expires_at' => 'date',
        'broker_mandate_expires_at' => 'date',
        'reviewed_at' => 'datetime',
        'verified_attributes' => 'array',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function brokerPrincipal(): BelongsTo
    {
        return $this->belongsTo(Organization::class, 'broker_principal_org_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public function isApproved(): bool
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        if ($this->license_expires_at && $this->license_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || ($this->license_expires_at && $this->license_expires_at->isPast());
    }

    public function getDocumentByType(string $type): ?VerificationDocument
    {
        return $this->documents()->where('document_type', $type)->where('is_valid', true)->latest()->first();
    }

    public function hasRequiredDocuments(): bool
    {
        $types = $this->documents()->where('is_valid', true)->pluck('document_type')->toArray();

        return match ($this->target_role) {
            self::ROLE_EXPORTER => in_array('commercial_reg', $types) && in_array('sector_license', $types) && in_array('tin_cert', $types),
            self::ROLE_SUPPLIER => in_array('commercial_reg', $types) && in_array('sector_license', $types),
            self::ROLE_BUYER => in_array('commercial_reg', $types) && in_array('tin_cert', $types),
            self::ROLE_BROKER => in_array('commercial_reg', $types) && in_array('broker_mandate', $types),
            default => false,
        };
    }
}

