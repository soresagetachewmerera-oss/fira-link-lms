<?php

namespace App\Support;

use App\Models\Organization;
use App\Models\User;

/**
 * What one trading party may see about the other: the business, its role and its current tier.
 * Never a person's email, phone or account fields.
 */
class BusinessSummary
{
    public static function of(?Organization $org): ?array
    {
        if (!$org) {
            return null;
        }
        $active = $org->status !== 'suspended' && $org->hasActiveTier(1);

        return [
            'id' => $org->id,
            'name' => $org->name,
            'business_role' => $org->roleKey(),
            'verification_tier' => $active ? (int) $org->verification_tier : 0,
            'status' => $org->workspaceStatus(),
            'fira_id' => $org->tier1_first_granted_at ? $org->fira_id : null,
        ];
    }

    /** Fallback for records that still point at a person (before they belonged to a business). */
    public static function forUser(?User $user): ?array
    {
        return $user ? self::of($user->currentOrganization() ?: $user->primaryOrganization()) : null;
    }
}
