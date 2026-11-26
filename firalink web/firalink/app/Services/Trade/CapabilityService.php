<?php

namespace App\Services\Trade;

use App\Models\AuditLog;
use App\Models\BusinessCategory;
use App\Models\Organization;
use App\Models\User;
use App\Models\VerificationDocument;
use App\Services\Verification\TierService;
use App\Services\Verification\VerificationException;
use App\Support\Capability;
use Illuminate\Support\Facades\DB;

/**
 * Activities of a business (release 2a). The only writer of organization_capabilities and of
 * organizations.business_category_id.
 *
 *  declared  the owner says the business does this (a business without a category: from its role)
 *  active    declared, Tier 1 valid, not suspended, and every required document approved and unexpired
 *
 * Plans and payments never change either. Required documents: config/verification.php → capability_documents.
 */
class CapabilityService
{
    /** @return list<string> */
    public static function requiredDocuments(string $capability): array
    {
        return array_values((array) config('verification.capability_documents.' . $capability, []));
    }

    /** @return list<string> */
    public static function allowed(Organization $org): array
    {
        $category = $org->businessCategory;

        return $category ? $category->allowedCapabilities() : Capability::forLegacyRole($org->roleKey());
    }

    /** @return list<string> */
    public static function declared(Organization $org): array
    {
        if (!$org->business_category_id) {
            return Capability::forLegacyRole($org->roleKey());
        }

        return array_values(array_intersect(
            self::allowed($org),
            DB::table('organization_capabilities')->where('organization_id', $org->id)->pluck('capability')->all()
        ));
    }

    /** @param string|list<string> $capabilities any of them */
    public static function declares(?Organization $org, string|array $capabilities): bool
    {
        return $org !== null && array_intersect((array) $capabilities, self::declared($org)) !== [];
    }

    /** @return list<string> required document types that are not approved and unexpired yet */
    public static function missingDocuments(Organization $org, string $capability): array
    {
        $required = self::requiredDocuments($capability);
        if ($required === []) {
            return [];
        }
        $approved = $org->verificationDocuments()->whereIn('document_type', $required)->countsForTier()->pluck('document_type')->all();

        return array_values(array_diff($required, $approved));
    }

    /** off, suspended, needs_verification, needs_documents or active */
    public static function status(Organization $org, string $capability): string
    {
        return match (true) {
            !in_array($capability, self::declared($org), true) => 'off',
            $org->status === 'suspended' => 'suspended',
            !$org->hasActiveTier(1) => 'needs_verification',
            self::missingDocuments($org, $capability) !== [] => 'needs_documents',
            default => 'active',
        };
    }

    public static function isActive(?Organization $org, string $capability): bool
    {
        return $org !== null && self::status($org, $capability) === 'active';
    }

    /**
     * Constraint on an Organization query: the capability is active. Mirrors status() in SQL
     * (Tier 1 and suspension come from TierService::tier1Constraint()).
     */
    public static function activeConstraint(string $capability): \Closure
    {
        $tier1 = TierService::tier1Constraint();
        $legacyRoles = array_keys(array_filter(
            ['exporter' => 1, 'supplier' => 1, 'broker' => 1, 'buyer' => 1],
            fn ($_, $role) => in_array($capability, Capability::forLegacyRole($role), true),
            ARRAY_FILTER_USE_BOTH
        ));
        $documents = self::requiredDocuments($capability);

        return function ($org) use ($tier1, $legacyRoles, $capability, $documents) {
            $tier1($org);
            $org->where(function ($q) use ($legacyRoles, $capability) {
                $q->where(fn ($l) => $l->whereNull('business_category_id')->where(function ($r) use ($legacyRoles) {
                    // Same rule as Organization::roleKey(): no role and not typed buyer counts as exporter.
                    $r->whereIn('business_role', $legacyRoles);
                    if (in_array('exporter', $legacyRoles, true)) {
                        $r->orWhere(fn ($n) => $n->whereNull('business_role')->where(fn ($t) => $t->whereNull('business_type')->orWhere('business_type', '!=', 'buyer')));
                    }
                }))->orWhere(fn ($c) => $c->whereNotNull('business_category_id')
                    ->whereExists(fn ($e) => $e->selectRaw('1')->from('organization_capabilities')
                        ->whereColumn('organization_capabilities.organization_id', 'organizations.id')
                        ->where('organization_capabilities.capability', $capability)));
                // Rows always match the category's allowed list: admin edits of a category remove the rest.
            });
            foreach ($documents as $type) {
                $org->whereHas('verificationDocuments', fn ($d) => $d->where('document_type', $type)->countsForTier());
            }
        };
    }

    private function audit(User $by, Organization $org, string $action, array $before, array $after): void
    {
        AuditLog::create([
            'user_id' => $by->id,
            'organization_id' => $org->id,
            'action' => $action,
            'auditable_type' => Organization::class,
            'auditable_id' => $org->id,
            'before_state' => $before,
            'after_state' => $after,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent() ?? 'system',
        ]);
    }

    /**
     * Give the business a category. A verified business keeps its role (its documents were reviewed for
     * it): it may only pick a category of the same role. All the category's activities start declared.
     */
    public function setCategory(Organization $org, BusinessCategory $category, User $by): Organization
    {
        if (!$category->is_active) {
            throw new VerificationException('CATEGORY_INACTIVE', __('trade.errors.category_inactive'), 422);
        }
        $verified = (int) $org->verification_tier >= 1 || $org->tier1_first_granted_at !== null;
        if ($verified && $category->business_role !== $org->roleKey()) {
            throw new VerificationException('CATEGORY_ROLE_LOCKED', __('trade.errors.category_role_locked'), 422);
        }

        return DB::transaction(function () use ($org, $category, $by) {
            $before = ['business_category' => $org->businessCategory?->code, 'business_role' => $org->roleKey(), 'capabilities' => self::declared($org)];
            $org->forceFill(['business_category_id' => $category->id, 'business_role' => $category->business_role, 'business_type' => $category->business_role])->save();
            $org->unsetRelation('businessCategory');
            $this->writeDeclared($org, $category->allowedCapabilities(), $by);
            $this->audit($by, $org, 'business_category_set', $before, ['business_category' => $category->code, 'business_role' => $category->business_role, 'capabilities' => self::declared($org)]);

            return $org->refresh();
        });
    }

    /** @param list<string> $capabilities the activities the business does; must be allowed by its category */
    public function setDeclared(Organization $org, array $capabilities, User $by): Organization
    {
        if (!$org->business_category_id) {
            throw new VerificationException('CATEGORY_REQUIRED', __('trade.errors.category_required'), 422);
        }
        $capabilities = array_values(array_unique($capabilities));
        if (array_diff($capabilities, self::allowed($org)) !== []) {
            throw new VerificationException('CAPABILITY_NOT_ALLOWED', __('trade.errors.capability_not_allowed'), 422);
        }

        return DB::transaction(function () use ($org, $capabilities, $by) {
            $before = self::declared($org);
            $this->writeDeclared($org, $capabilities, $by);
            $this->audit($by, $org, 'business_capabilities_changed', ['capabilities' => $before], ['capabilities' => self::declared($org)]);

            return $org;
        });
    }

    private function writeDeclared(Organization $org, array $capabilities, User $by): void
    {
        DB::table('organization_capabilities')->where('organization_id', $org->id)->whereNotIn('capability', $capabilities)->delete();
        foreach ($capabilities as $c) {
            DB::table('organization_capabilities')->insertOrIgnore([
                'organization_id' => $org->id, 'capability' => $c, 'declared_by' => $by->id,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /** What the business's own members see on the Activities screen. */
    public static function summary(Organization $org): array
    {
        $category = $org->businessCategory;

        return [
            'business_category' => $category?->toOption(),
            'can_change_category' => (int) $org->verification_tier < 1 && $org->tier1_first_granted_at === null,
            'capabilities' => array_map(fn (string $c) => [
                'code' => $c,
                'declared' => in_array($c, self::declared($org), true),
                'status' => self::status($org, $c),
                'required_documents' => self::requiredDocuments($c),
                'missing_documents' => self::missingDocuments($org, $c),
            ], self::allowed($org)),
        ];
    }
}
