<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\OrganizationVerification;
use App\Models\User;
use App\Services\Verification\VerificationReviewService;

/**
 * Approve a business for a role the way production does: through the verification-review
 * service (verification fields are not mass-assignable).
 */
trait ApprovesBusinesses
{
    protected function approveBusiness(Organization $org, string $role = 'exporter', ?\DateTimeInterface $expires = null): OrganizationVerification
    {
        $verification = OrganizationVerification::firstOrCreate([
            'organization_id' => $org->id,
            'target_role' => $role,
        ]);

        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['email' => 'reviewer_' . uniqid() . '@example.test', 'role' => 'admin']);

        return app(VerificationReviewService::class)->approve($verification, $admin, 'Approved in test', $expires ?? now()->addYear());
    }
}
