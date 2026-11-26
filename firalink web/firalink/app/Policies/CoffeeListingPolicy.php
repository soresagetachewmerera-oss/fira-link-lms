<?php

namespace App\Policies;

use App\Models\CoffeeListing;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Services\Verification\VerificationReviewService;
use Illuminate\Auth\Access\Response;

/**
 * Lots belong to a business workspace. Owners and managers create and change them; staff can
 * view; admins review evidence in the review queue, they do not edit sellers' lots here.
 */
class CoffeeListingPolicy
{
    private function role(User $user, ?int $orgId): ?string
    {
        if (!$orgId) {
            return null;
        }

        return OrganizationMembership::where('organization_id', $orgId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->value('role');
    }

    private function manages(User $user, ?int $orgId): bool
    {
        return in_array($this->role($user, $orgId), [OrganizationMembership::ROLE_OWNER, OrganizationMembership::ROLE_MANAGER], true);
    }

    /** Create a lot in the user's active business (a draft until the business is Tier 1). */
    public function create(User $user, ?Organization $org = null): Response
    {
        $org ??= $user->currentOrganization();
        if (!$org) {
            return Response::deny(__('lots.errors.business_required'), 'ORGANIZATION_REQUIRED');
        }
        if (!$this->manages($user, $org->id)) {
            return Response::deny(__('lots.errors.not_allowed'), 'NOT_ALLOWED');
        }
        // Lots are export offers until local trade exists: the business must sell for export.
        if (!\App\Services\Trade\CapabilityService::declares($org, \App\Support\Capability::SellExport->value)) {
            return Response::deny(__('lots.errors.seller_role'), 'SELLER_ROLE_REQUIRED');
        }
        if ($org->status === 'suspended') {
            return Response::deny(__('verification.public.suspended'), 'BUSINESS_SUSPENDED');
        }

        return Response::allow();
    }

    public function view(User $user, CoffeeListing $lot): bool
    {
        return $this->role($user, $lot->organization_id) !== null;
    }

    public function update(User $user, CoffeeListing $lot): Response
    {
        if (!$this->manages($user, $lot->organization_id)) {
            return Response::deny(__('lots.errors.not_allowed'), 'NOT_ALLOWED');
        }
        if ($lot->status === 'archived') {
            return Response::deny(__('lots.errors.not_editable'), 'LOT_ARCHIVED');
        }

        return Response::allow();
    }

    public function delete(User $user, CoffeeListing $lot): Response
    {
        return $this->update($user, $lot);
    }

    /** Add or withdraw evidence: same people as editing the lot. */
    public function manageEvidence(User $user, CoffeeListing $lot): Response
    {
        return $this->update($user, $lot);
    }

    /** Publishing needs a Tier 1+ seller business; until then the lot stays a draft. */
    public function publish(User $user, CoffeeListing $lot): Response
    {
        $edit = $this->update($user, $lot);
        if ($edit->denied()) {
            return $edit;
        }
        if (!VerificationReviewService::isApprovedSeller($lot->organization)) {
            // Tier 1 is in place but the ECTA exporter certificate is not (Q2a): say which one is missing.
            if (\App\Services\Verification\TierService::isVisibleToBuyers($lot->organization) && !VerificationReviewService::hasExportLicence($lot->organization)) {
                return Response::deny(__('lots.errors.export_licence_required'), 'EXPORT_LICENCE_REQUIRED');
            }

            return Response::deny(__('lots.errors.tier_required'), 'VERIFICATION_REQUIRED');
        }

        return Response::allow();
    }
}
