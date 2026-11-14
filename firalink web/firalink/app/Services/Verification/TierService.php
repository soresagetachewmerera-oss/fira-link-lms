<?php

namespace App\Services\Verification;

use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Models\VerificationTier3Grant;
use App\Notifications\VerificationUpdate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Computes a business's verification tier from approved, unexpired documents (and an active
 * Tier 3 grant). The ONLY writer of organizations.verification_tier, verification_valid_until,
 * verification_reviewed_at, tier1_first_granted_at, fira_id and the legacy is_verified /
 * verified_roles flags. Clients and plans never reach it.
 */
class TierService
{
    /** Document types a business needs for one tier, from config (entity type and role aware). */
    public static function requiredDocuments(Organization $org, int $tier): array
    {
        if ($tier === 1) {
            $docs = config('verification.tiers.1.documents', []);
            $licence = config('verification.tiers.1.licence_by_entity_type.' . $org->entity_type);
            if ($licence) {
                $docs = array_map(fn ($t) => $t === 'trade_license' ? $licence : $t, $docs);
            }

            return array_values($docs);
        }
        if ($tier === 2) {
            return array_values(config('verification.tiers.2.documents_by_role.' . $org->roleKey(), []));
        }

        return [];
    }

    /** Highest tier this business can reach: Tier 1 only for roles without Tier 2 documents. */
    public static function maxTier(Organization $org): int
    {
        return self::requiredDocuments($org, 2) === [] ? 1 : 3;
    }

    public static function documentTypeConfig(string $type): ?array
    {
        return config('verification.document_types.' . $type);
    }

    /**
     * @return array{tier:int, valid_until:?Carbon, counted:array<string,VerificationDocument>}
     */
    public function compute(Organization $org): array
    {
        $approved = $org->verificationDocuments()->countsForTier()
            ->orderByDesc('reviewed_at')
            ->get()
            ->unique('document_type')
            ->keyBy('document_type');

        $tier = 0;
        $counted = [];
        foreach ([1, 2] as $t) {
            $required = self::requiredDocuments($org, $t);
            if ($t === 2 && $required === []) {
                break;
            }
            if ($required === [] || array_diff($required, $approved->keys()->all()) !== []) {
                break;
            }
            foreach ($required as $type) {
                $counted[$type] = $approved[$type];
            }
            $tier = $t;
        }

        $grant = null;
        if ($tier === 2) {
            $grant = $org->tier3Grants()->active()->latest('granted_at')->first();
            if ($grant) {
                $tier = 3;
            }
        }

        // Valid until the earliest expiry among the documents (and grant) that make the tier.
        $expiries = collect($counted)->pluck('expires_at')->filter()->map(fn ($d) => Carbon::parse($d)->endOfDay());
        if ($grant) {
            $expiries->push($grant->expires_at);
        }

        return [
            'tier' => $tier,
            'valid_until' => $expiries->isEmpty() ? null : $expiries->min(),
            'counted' => $counted,
        ];
    }

    public function recompute(Organization $org, ?User $actor = null): Organization
    {
        return DB::transaction(function () use ($org, $actor) {
            $org->refresh();
            $result = $this->compute($org);
            $tier = $result['tier'];

            $roles = $tier >= 1 ? [$org->roleKey()] : [];
            $changes = [
                'verification_tier' => $tier,
                'verification_valid_until' => $result['valid_until'],
                'is_verified' => $tier >= 1,
                'verified_roles' => $roles,
            ];

            $before = [
                'verification_tier' => (int) $org->verification_tier,
                'verification_valid_until' => $org->verification_valid_until?->toIso8601String(),
                'is_verified' => (bool) $org->is_verified,
                'verified_roles' => $org->verified_roles ?? [],
            ];
            $after = array_merge($changes, ['verification_valid_until' => $result['valid_until']?->toIso8601String()]);

            if ($before == $after) {
                return $org;
            }

            if ($tier !== (int) $org->verification_tier) {
                $changes['verification_reviewed_at'] = now();
            }
            if ($tier >= 1 && $org->tier1_first_granted_at === null) {
                $changes['tier1_first_granted_at'] = now();
            }
            if ($tier >= 1 && !$org->fira_id) {
                $changes['fira_id'] = FiraId::generateUnique();
            }

            $previousTier = (int) $org->verification_tier;
            $org->forceFill($changes)->save();

            AuditLog::create([
                'user_id' => $actor?->id,
                'organization_id' => $org->id,
                'action' => 'verification_tier_recomputed',
                'auditable_type' => Organization::class,
                'auditable_id' => $org->id,
                'before_state' => $before,
                'after_state' => $after + ['fira_id' => $org->fira_id],
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent() ?? 'system',
            ]);

            if ($tier !== $previousTier) {
                $owner = $org->owner();
                $owner?->notify(new VerificationUpdate('tier_changed', $org, null, ['tier' => $tier, 'previous_tier' => $previousTier]));
            }

            return $org;
        });
    }

    /** Query constraint on an Organization query: Tier 1+, still valid, not suspended. */
    public static function tier1Constraint(): \Closure
    {
        return function ($org) {
            $org->where('status', '!=', 'suspended')
                ->where('verification_tier', '>=', 1)
                ->where(fn ($q) => $q->whereNull('verification_valid_until')->orWhere('verification_valid_until', '>', now()));
        };
    }

    public static function isVisibleToBuyers(?Organization $org): bool
    {
        return $org !== null && $org->status !== 'suspended' && $org->hasActiveTier(1);
    }
}
