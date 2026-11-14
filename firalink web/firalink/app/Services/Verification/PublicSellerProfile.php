<?php

namespace App\Services\Verification;

use App\Models\DealRoom;
use App\Models\Organization;
use App\Models\OrganizationMembership;
use App\Models\User;
use App\Models\VerificationDocument;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * The public seller page data, shared by the API and the web page. Built only from reviewed
 * records. Never contains TIN, ID or document numbers, phone, bank details, files, reports or
 * disputes. Exists only from the first Tier 1 grant; anything else is "not found".
 */
class PublicSellerProfile
{
    public function __construct(private TierService $tiers)
    {
    }

    public function find(string $input): ?Organization
    {
        $id = FiraId::normalize($input);
        if ($id === null) {
            return null;
        }

        return Organization::where('fira_id', $id)->whereNotNull('tier1_first_granted_at')->first();
    }

    public static function status(Organization $org): string
    {
        if ($org->status === 'suspended') {
            return 'suspended';
        }

        return $org->hasActiveTier(1) ? 'verified' : 'expired';
    }

    public function build(Organization $org): array
    {
        $status = self::status($org);
        $checked = [];
        if ($status === 'verified') {
            foreach ($this->tiers->compute($org)['counted'] as $type => $doc) {
                $checks = array_keys(array_filter($doc->checks ?? []));
                $checked[] = [
                    'document_type' => $type,
                    // The bank letter is shown as the check we made, never as the letter itself.
                    'check' => $checks[0] ?? null,
                    'tier' => (int) $doc->tier,
                    'checked_at' => $doc->reviewed_at?->toDateString(),
                    'valid_until' => $doc->expires_at?->toDateString(),
                ];
            }
        }

        $deals = self::confirmedDeals($org);

        return [
            'fira_id' => $org->fira_id,
            'name' => $org->name,
            'business_role' => $org->roleKey(),
            'city' => $org->sellerProfile?->city,
            'country' => $org->country,
            'status' => $status,
            'tier' => $status === 'verified' ? (int) $org->verification_tier : null,
            'vetted' => $status === 'verified' && (int) $org->verification_tier === 3,
            'reviewed_at' => $status === 'verified' ? $org->verification_reviewed_at?->toDateString() : null,
            'valid_until' => $status === 'verified' ? $org->verification_valid_until?->toDateString() : null,
            'checked' => $checked,
            'confirmed_deals' => $deals,
            'member_since' => (int) $org->created_at?->format('Y'),
            'public_url' => route('verify.show', $org->fira_id),
            'qr_svg_url' => route('verify.qr', $org->fira_id),
        ];
    }

    /**
     * Completed deal rooms where this business is the seller and the buyer is a different owner:
     * different account (never a member), different phone, and no shared owner-ID hash.
     */
    public static function confirmedDeals(Organization $org): int
    {
        $memberIds = OrganizationMembership::where('organization_id', $org->id)->pluck('user_id');
        $ownerPhones = User::whereIn('id', OrganizationMembership::where('organization_id', $org->id)->where('role', 'owner')->pluck('user_id'))
            ->whereNotNull('phone')->pluck('phone');
        $sellerHashes = $org->verificationDocuments()->whereNotNull('document_number_hash')->pluck('document_number_hash');

        $deals = DealRoom::with('buyer:id,phone')
            ->where('organization_id', $org->id)
            ->where('status', DealRoom::STATUS_COMPLETED)
            ->whereNotNull('buyer_id')
            ->whereNotIn('buyer_id', $memberIds)
            ->get();

        return $deals->filter(function (DealRoom $deal) use ($ownerPhones, $sellerHashes) {
            if ($deal->buyer?->phone && $ownerPhones->contains($deal->buyer->phone)) {
                return false;
            }
            if ($sellerHashes->isNotEmpty()) {
                $buyerOrgs = OrganizationMembership::where('user_id', $deal->buyer_id)->pluck('organization_id');
                if (VerificationDocument::whereIn('organization_id', $buyerOrgs)->whereIn('document_number_hash', $sellerHashes)->exists()) {
                    return false;
                }
            }

            return true;
        })->count();
    }

    public static function qrSvg(string $url, int $size = 240): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd()));

        return $writer->writeString($url);
    }
}
