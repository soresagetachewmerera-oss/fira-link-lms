<?php

namespace App\Support;

/**
 * Legal form of a business workspace. The ONE list used by the API, the app and admin screens.
 * Slice 3 maps verification document requirements to these values, so add, never rename.
 */
enum BusinessEntityType: string
{
    case SoleProprietorship = 'sole_proprietorship';
    case PrivateLimitedCompany = 'private_limited_company';
    case ShareCompany = 'share_company';
    case Cooperative = 'cooperative';
    case CooperativeUnion = 'cooperative_union';
    case Partnership = 'partnership';
    case StateOwnedEnterprise = 'state_owned_enterprise';
    case ForeignCompanyBranch = 'foreign_company_branch';
    case Association = 'association';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SoleProprietorship => 'Sole proprietorship',
            self::PrivateLimitedCompany => 'Private limited company (PLC)',
            self::ShareCompany => 'Share company',
            self::Cooperative => 'Cooperative',
            self::CooperativeUnion => 'Cooperative union',
            self::Partnership => 'Partnership',
            self::StateOwnedEnterprise => 'State-owned enterprise',
            self::ForeignCompanyBranch => 'Foreign company or branch',
            self::Association => 'Association',
            self::Other => 'Other',
        };
    }

    /** "Other" is allowed but an admin must check what the business actually is. */
    public function needsManualReview(): bool
    {
        return $this === self::Other;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return list<array{value:string,label:string,needs_manual_review:bool}> */
    public static function options(): array
    {
        return array_map(fn (self $t) => [
            'value' => $t->value,
            'label' => $t->label(),
            'needs_manual_review' => $t->needsManualReview(),
        ], self::cases());
    }
}
