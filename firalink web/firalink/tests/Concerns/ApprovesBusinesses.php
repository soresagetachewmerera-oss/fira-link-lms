<?php

namespace Tests\Concerns;

use App\Models\Organization;
use App\Models\OrganizationVerification;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\Auth\StaffTwoFactor;
use App\Services\Verification\TierService;
use Laravel\Fortify\Fortify;

/**
 * Fixtures for verified businesses and signed-in staff. Tiers come only from approved,
 * unexpired documents, so this writes approved Tier 1 documents and lets TierService compute.
 */
trait ApprovesBusinesses
{
    protected function approveBusiness(Organization $org, string $role = 'exporter', ?\DateTimeInterface $expires = null, int $tier = 1): Organization
    {
        if ($org->business_role !== $role) {
            $org->forceFill(['business_role' => $role])->save();
        }
        $container = OrganizationVerification::firstOrCreate(['organization_id' => $org->id, 'target_role' => $role]);

        $types = TierService::requiredDocuments($org, 1);
        if ($tier >= 2) {
            $types = array_merge($types, TierService::requiredDocuments($org, 2));
        }
        foreach ($types as $type) {
            $config = TierService::documentTypeConfig($type);
            $doc = VerificationDocument::create([
                'organization_verification_id' => $container->id,
                'organization_id' => $org->id,
                'document_type' => $type,
                'file_path' => 'test/' . $type . '.pdf',
                'file_disk' => 'verification',
                'original_filename' => $type . '.pdf',
                'file_size_kb' => 40,
                'mime_type' => 'application/pdf',
                'is_valid' => true,
                'expires_at' => ($config['expires'] ?? false) ? ($expires ?? now()->addYear()) : null,
                'submitted_at' => now(),
            ]);
            $doc->forceFill([
                'tier' => (int) $config['tier'],
                'status' => VerificationDocument::STATUS_APPROVED,
                'reviewed_at' => now(),
            ])->save();
        }

        return app(TierService::class)->recompute($org)->refresh();
    }

    /** A staff user with an enrolled authenticator who passed the code in this session. */
    /**
     * @param bool $fresh also confirmed "it is you" within the last 10 minutes (class D staff actions:
     *                    money, settings, suspension, payout confirmation), as after /staff/confirm
     */
    protected function actingAsStaff(User $user, bool $fresh = false): static
    {
        $this->enrolStaffTwoFactor($user);
        $session = [StaffTwoFactor::SESSION_USER => $user->id];
        if ($fresh) {
            $session['fresh_auth_at'] = now()->timestamp;
        }

        return $this->actingAs($user)->withSession($session);
    }

    /** Returns the plain TOTP secret. */
    protected function enrolStaffTwoFactor(User $user, string $secret = 'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP'): string
    {
        $user->forceFill([
            'two_factor_secret' => Fortify::currentEncrypter()->encrypt($secret),
            'two_factor_recovery_codes' => Fortify::currentEncrypter()->encrypt(json_encode(['recovery-one-1111', 'recovery-two-2222'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $secret;
    }
}
