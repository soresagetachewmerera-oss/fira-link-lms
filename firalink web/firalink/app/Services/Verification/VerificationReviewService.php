<?php

namespace App\Services\Verification;

use App\Models\AuditLog;
use App\Models\MarketplaceSellerProfile;
use App\Models\Organization;
use App\Models\OrganizationVerification;
use App\Models\User;
use App\Support\BusinessRole;
use Illuminate\Support\Facades\DB;

/**
 * The ONLY place that changes verification and status fields:
 *   organizations.is_verified, organizations.verified_roles, organizations.status,
 *   organization_verifications.status / reviewed_by / reviewed_at / admin_notes / rejection_reason,
 *   marketplace_seller_profiles.verification_status / verified_at / verification_notes.
 *
 * None of these fields is mass-assignable; this service writes them with forceFill() and
 * records an audit-log entry for every change. Plans, payments and subscriptions never call it.
 */
class VerificationReviewService
{
    private function audit(?User $actor, ?int $orgId, string $action, string $type, int $id, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $actor?->id,
            'organization_id' => $orgId,
            'action' => $action,
            'auditable_type' => $type,
            'auditable_id' => $id,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    private const VERIFICATION_REVIEW_FIELDS = ['status', 'reviewed_by', 'reviewed_at', 'admin_notes', 'rejection_reason'];

    private function changeVerification(OrganizationVerification $v, array $changes, ?User $actor, string $action): OrganizationVerification
    {
        return DB::transaction(function () use ($v, $changes, $actor, $action) {
            $before = $v->only(array_keys($changes));
            $v->forceFill($changes)->save();
            $this->audit($actor, $v->organization_id, $action, OrganizationVerification::class, $v->id, $before, $v->only(array_keys($changes)));

            if ($v->organization) {
                $this->syncOrganization($v->organization, $actor);
            }

            return $v;
        });
    }

    /** A business submitted its dossier: it now waits for an admin. */
    public function submitForReview(OrganizationVerification $v, User $submittedBy): OrganizationVerification
    {
        return $this->changeVerification($v, [
            'status' => OrganizationVerification::STATUS_PENDING_REVIEW,
            'rejection_reason' => null,
        ], $submittedBy, 'verification_submitted_for_review');
    }

    public function approve(OrganizationVerification $v, User $admin, ?string $notes = null, $licenseExpiresAt = null): OrganizationVerification
    {
        $changes = [
            'status' => OrganizationVerification::STATUS_APPROVED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => $notes ?: 'Dossier audited and approved by Compliance Team.',
            'rejection_reason' => null,
        ];
        $v->forceFill(['license_expires_at' => $licenseExpiresAt ?? $v->license_expires_at ?? now()->addYear()]);

        return $this->changeVerification($v, $changes, $admin, 'organization_role_verified');
    }

    public function reject(OrganizationVerification $v, User $admin, string $reason): OrganizationVerification
    {
        return $this->changeVerification($v, [
            'status' => OrganizationVerification::STATUS_REJECTED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ], $admin, 'organization_role_rejected');
    }

    public function suspend(OrganizationVerification $v, User $admin, string $reason): OrganizationVerification
    {
        return $this->changeVerification($v, [
            'status' => OrganizationVerification::STATUS_SUSPENDED,
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'admin_notes' => 'Suspended: ' . $reason,
        ], $admin, 'organization_role_suspended');
    }

    /** Nightly scheduler: a licence passed its expiry date. */
    public function expire(OrganizationVerification $v, string $note = 'Auto-expired by nightly compliance scheduler.'): OrganizationVerification
    {
        return $this->changeVerification($v, [
            'status' => OrganizationVerification::STATUS_EXPIRED,
            'admin_notes' => $note,
        ], null, 'organization_role_expired');
    }

    /**
     * Recompute the organization's tier and cached flags. Since slice 3 only approved,
     * unexpired documents count (TierService); role-level records no longer verify anyone.
     */
    public function syncOrganization(Organization $org, ?User $actor = null): Organization
    {
        return app(TierService::class)->recompute($org, $actor);
    }

    public function setOrganizationStatus(Organization $org, string $status, User $admin, string $reason): Organization
    {
        abort_unless(in_array($status, ['active', 'suspended'], true), 422, 'Unknown organization status.');
        $before = ['status' => $org->status];
        $org->forceFill(['status' => $status])->save();
        $this->audit($admin, $org->id, 'organization_status_changed', Organization::class, $org->id, $before, ['status' => $status, 'reason' => $reason]);

        return $org;
    }

    // ── Legacy seller profiles (pre-organization verification) ───────────────

    private function changeSellerProfile(MarketplaceSellerProfile $p, array $changes, ?User $actor, string $action): MarketplaceSellerProfile
    {
        $before = $p->only(array_keys($changes));
        $p->forceFill($changes)->save();
        $this->audit($actor, $p->organization_id, $action, MarketplaceSellerProfile::class, $p->id, $before, $p->only(array_keys($changes)));

        if ($p->organization) {
            $this->syncOrganization($p->organization, $actor);
        }

        return $p;
    }

    public function markSellerProfileUnderReview(MarketplaceSellerProfile $p, User $by): MarketplaceSellerProfile
    {
        if ($p->verification_status === 'under_review') {
            return $p;
        }

        return $this->changeSellerProfile($p, ['verification_status' => 'under_review'], $by, 'seller_profile_submitted');
    }

    public function verifySellerProfile(MarketplaceSellerProfile $p, User $admin, ?string $notes = null): MarketplaceSellerProfile
    {
        return $this->changeSellerProfile($p, [
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_notes' => $notes ?: 'Verified by Admin.',
        ], $admin, 'seller_profile_verified');
    }

    public function rejectSellerProfile(MarketplaceSellerProfile $p, User $admin, string $notes): MarketplaceSellerProfile
    {
        return $this->changeSellerProfile($p, [
            'verification_status' => 'rejected',
            'verification_notes' => $notes,
        ], $admin, 'seller_profile_rejected');
    }

    /**
     * Hidden until Tier 1: query constraint on an Organization query (Tier 1+, still valid, not
     * suspended, seller role). Used by the listing and supplier scopes so API and web share one rule.
     */
    public static function approvedSellerConstraint(): \Closure
    {
        $tier1 = TierService::tier1Constraint();

        return function ($org) use ($tier1) {
            $tier1($org);
            // Same rule as Organization::roleKey(): legacy records without business_role count as sellers unless typed buyer.
            $org->where(fn ($q) => $q->whereIn('business_role', BusinessRole::SELLER_ROLES)
                ->orWhere(fn ($l) => $l->whereNull('business_role')->where(fn ($t) => $t->whereNull('business_type')->orWhere('business_type', '!=', 'buyer'))));
        };
    }

    /** Visible to buyers: Tier 1 or higher, still valid, not suspended, a seller role. */
    public static function isApprovedSeller(?Organization $org): bool
    {
        return TierService::isVisibleToBuyers($org) && in_array($org->roleKey(), BusinessRole::SELLER_ROLES, true);
    }
}
