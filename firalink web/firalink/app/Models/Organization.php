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
     * business_category_id is written only by CapabilityService.
     * is_verified, verified_roles, status, needs_manual_review, verification_tier,
     * verification_valid_until, verification_reviewed_at, tier1_first_granted_at and fira_id are
     * NOT fillable: they change only through the verification services (with an audit-log entry).
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

    // Never in JSON: organizations are embedded in public lot and supplier responses.
    protected $hidden = ['tin_number', 'billing_email', 'created_by', 'needs_manual_review'];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_roles' => 'array',
        'needs_manual_review' => 'boolean',
        'verification_tier' => 'integer',
        'verification_valid_until' => 'datetime',
        'verification_reviewed_at' => 'datetime',
        'tier1_first_granted_at' => 'datetime',
    ];

    public function businessCategory(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(BusinessCategory::class);
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(OrganizationVerification::class);
    }

    public function verificationDocuments(): HasMany
    {
        return $this->hasMany(VerificationDocument::class);
    }

    public function tier3Grants(): HasMany
    {
        return $this->hasMany(VerificationTier3Grant::class);
    }

    /** exporter, supplier, broker or buyer (legacy records fall back to business_type). */
    public function roleKey(): string
    {
        $role = $this->business_role ?: $this->business_type;

        if (in_array($role, \App\Support\BusinessRole::values(), true)) {
            return $role;
        }

        return 'exporter'; // legacy seller types (cooperative, commercial_farm, ...)
    }

    /**
     * The only rule for showing a verification badge anywhere: Tier 1 or higher from reviewed documents,
     * still valid, and the business is not suspended. Never a plan, a payment or a legacy flag.
     */
    public function hasActiveBadge(): bool
    {
        return $this->status !== 'suspended' && $this->hasActiveTier(1);
    }

    /** Tier 1 or higher now: valid tier and not past its valid-until date. */
    public function hasActiveTier(int $tier = 1): bool
    {
        return (int) $this->verification_tier >= $tier
            && ($this->verification_valid_until === null || $this->verification_valid_until->isFuture());
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

    /** Verified for a role = Tier 1 or higher in that business role (slice 3). */
    public function isVerifiedAs(string $role): bool
    {
        return $this->status !== 'suspended' && $this->roleKey() === $role && $this->hasActiveTier(1);
    }

    /** Recompute the tier and cached flags (TierService audits every change). */
    public function syncVerifiedRoles(): void
    {
        app(\App\Services\Verification\TierService::class)->recompute($this);
    }

    /**
     * Status shown for a workspace in the app, derived only from the computed tier, the documents
     * and an admin suspension. Plans, payments and subscriptions never affect it.
     * One of: suspended, verified, in_review, needs_changes, expired, not_started.
     */
    public function workspaceStatus(): string
    {
        if ($this->status === 'suspended') {
            return 'suspended';
        }
        if ($this->hasActiveTier(1)) {
            return 'verified';
        }

        $statuses = $this->verificationDocuments()->current()->pluck('status');

        return match (true) {
            $statuses->contains(VerificationDocument::STATUS_IN_REVIEW) => 'in_review',
            $statuses->contains(VerificationDocument::STATUS_NEEDS_CHANGES),
            $statuses->contains(VerificationDocument::STATUS_REJECTED) => 'needs_changes',
            $this->tier1_first_granted_at !== null || $statuses->contains(VerificationDocument::STATUS_EXPIRED),
            $this->verificationDocuments()->where('status', VerificationDocument::STATUS_APPROVED)
                ->whereDate('expires_at', '<', now()->toDateString())->exists() => 'expired',
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

    public function coffeeListings(): HasMany
    {
        return $this->hasMany(CoffeeListing::class);
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
