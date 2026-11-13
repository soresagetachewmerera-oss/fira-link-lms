<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Organization extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * is_verified, verified_roles, status and needs_manual_review are NOT fillable: they change
     * only through App\Services\Verification\VerificationReviewService (with an audit-log entry).
     */
    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'business_role',
        'entity_type',
        'tin_number',
        'logo_path',
        'billing_email',
        'country',
        'created_by',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_roles' => 'array',
        'needs_manual_review' => 'boolean',
    ];

    public function verifications(): HasMany
    {
        return $this->hasMany(OrganizationVerification::class);
    }

    public function activeVerificationForRole(string $role): ?OrganizationVerification
    {
        return $this->verifications()
            ->where('target_role', $role)
            ->where('status', OrganizationVerification::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('license_expires_at')
                  ->orWhere('license_expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    public function isVerifiedAs(string $role): bool
    {
        if ($this->is_verified && is_array($this->verified_roles) && in_array($role, $this->verified_roles)) {
            return true;
        }

        return $this->activeVerificationForRole($role) !== null;
    }

    /** Recompute cached verification flags (delegates to the review service, which audits). */
    public function syncVerifiedRoles(): void
    {
        app(\App\Services\Verification\VerificationReviewService::class)->syncOrganization($this);
    }

    /**
     * Status shown for a workspace in the app, derived only from verification records and the
     * organization's own suspension. Plans, payments and subscriptions never affect it.
     * One of: suspended, verified, in_review, needs_changes, expired, not_started.
     */
    public function workspaceStatus(): string
    {
        if ($this->status === 'suspended') {
            return 'suspended';
        }

        $statuses = $this->verifications()->get(['status', 'license_expires_at'])->map(function ($v) {
            if ($v->status === OrganizationVerification::STATUS_APPROVED && $v->license_expires_at && $v->license_expires_at->isPast()) {
                return OrganizationVerification::STATUS_EXPIRED;
            }

            return $v->status;
        });

        return match (true) {
            $statuses->contains(OrganizationVerification::STATUS_SUSPENDED) => 'suspended',
            $statuses->contains(OrganizationVerification::STATUS_APPROVED) => 'verified',
            $statuses->contains(OrganizationVerification::STATUS_PENDING_REVIEW),
            $statuses->contains(OrganizationVerification::STATUS_UNDER_SCRUTINY) => 'in_review',
            $statuses->contains(OrganizationVerification::STATUS_REJECTED) => 'needs_changes',
            $statuses->contains(OrganizationVerification::STATUS_EXPIRED) => 'expired',
            default => 'not_started',
        };
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(OrganizationMembership::class)->where('is_active', true);
    }

    public function sellerProfile(): HasOne
    {
        return $this->hasOne(MarketplaceSellerProfile::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING, Subscription::STATUS_GRACE])
            ->latestOfMany();
    }

    public function entitlementOverrides(): HasMany
    {
        return $this->hasMany(OrganizationEntitlementOverride::class);
    }

    public function usageCounters(): HasMany
    {
        return $this->hasMany(UsageCounter::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function dealRooms(): HasMany
    {
        return $this->hasMany(DealRoom::class);
    }

    public function exportShipments(): HasMany
    {
        return $this->hasMany(ExportShipment::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function documentPackages(): HasMany
    {
        return $this->hasMany(DocumentPackage::class);
    }

    public function owner(): ?User
    {
        return $this->memberships()->where('role', 'owner')->first()?->user;
    }

    public function isOwner(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->where('is_active', true)
            ->exists();
    }

    public function canManageBilling(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->isOwner($user);
    }

    public function hasMember(User $user): bool
    {
        return $this->memberships()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }
}
