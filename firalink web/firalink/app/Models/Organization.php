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

    protected $fillable = [
        'name',
        'slug',
        'business_type',
        'status',
        'logo_path',
        'billing_email',
        'country',
        'is_verified',
        'verified_roles',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'verified_roles' => 'array',
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

    public function syncVerifiedRoles(): void
    {
        $approvedRoles = $this->verifications()
            ->where('status', OrganizationVerification::STATUS_APPROVED)
            ->where(function ($q) {
                $q->whereNull('license_expires_at')
                  ->orWhere('license_expires_at', '>', now());
            })
            ->pluck('target_role')
            ->unique()
            ->values()
            ->toArray();

        $this->update([
            'is_verified' => count($approvedRoles) > 0,
            'verified_roles' => $approvedRoles,
        ]);
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
