<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessBlock;
use App\Models\CoffeeListing;
use App\Models\LotEvidence;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSample;
use App\Policies\MarketplaceRfqPolicy;
use App\Services\Verification\VerificationReviewService;
use App\Support\BusinessSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Seller home (flow "Seller home (business workspace)"): counts and the to-do list of the active
 * seller business. Lots themselves come from GET /marketplace/seller/lots.
 */
class SellerHomeController extends Controller
{
    public const EXPIRING_DAYS = 30;

    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization();
        if (!$org || MarketplaceRfqPolicy::role($user, $org->id) === null) {
            return response()->json(['success' => false, 'code' => 'ORGANIZATION_REQUIRED', 'message' => __('trade.errors.business_required')], 404);
        }
        if (!\App\Services\Trade\CapabilityService::declares($org, \App\Support\Capability::SELLING)) {
            return response()->json(['success' => false, 'code' => 'SELLERS_ONLY', 'message' => __('trade.errors.sellers_only')], 403);
        }

        $visible = VerificationReviewService::isApprovedSeller($org);

        // Samples paid (or free) and waiting to be sealed and shipped, soonest first.
        $toSend = MarketplaceSample::with(['listing', 'buyerOrganization'])
            ->where('organization_id', $org->id)
            ->where('status', MarketplaceSample::STATUS_FEE_PAID)
            ->orderBy('ship_by')->orderBy('id')->get();

        // Open requests the business may answer and has not quoted yet (verified sellers only).
        $openRequests = collect();
        $openCount = 0;
        if (MarketplaceRfqPolicy::isVerifiedSeller($org)) {
            $blocked = BusinessBlock::where('organization_id', $org->id)->pluck('blocked_organization_id')
                ->merge(BusinessBlock::where('blocked_organization_id', $org->id)->pluck('organization_id'));
            $quoted = MarketplaceQuotation::where('organization_id', $org->id)->pluck('rfq_id');
            $query = MarketplaceRfq::with('organization')->open()
                ->whereNotNull('organization_id')
                ->where('organization_id', '!=', $org->id)
                ->whereNotIn('organization_id', $blocked)
                ->whereNotIn('id', $quoted);
            $openCount = (clone $query)->count();
            $openRequests = $query->orderByRaw('closing_date IS NULL')->orderBy('closing_date')->limit(5)->get();
        }

        // Approved evidence that expires within 30 days: renew it or the lot level drops.
        $expiring = LotEvidence::with('listing')
            ->where('organization_id', $org->id)
            ->where('status', LotEvidence::STATUS_APPROVED)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays(self::EXPIRING_DAYS)->toDateString())
            ->orderBy('expires_at')->limit(10)->get();

        $todo = [];
        foreach ($toSend as $s) {
            $todo[] = [
                'kind' => 'ship_sample',
                'sample_id' => $s->id,
                'buyer' => $s->buyerOrganization?->name,
                'lot' => $s->listing?->title,
                'grams' => (int) $s->quantity_grams,
                'due' => $s->ship_by?->toDateString(),
            ];
        }
        foreach ($openRequests as $r) {
            $todo[] = [
                'kind' => 'answer_request',
                'rfq_id' => $r->id,
                'title' => $r->title,
                'buyer' => BusinessSummary::of($r->organization)['name'] ?? null,
                'volume_value' => $r->volume_value !== null ? (float) $r->volume_value : (float) $r->volume_bags,
                'volume_unit' => $r->volume_value !== null ? $r->volume_unit : 'bags',
                'destination' => $r->destination_port ?: $r->destination_country,
                'closes' => $r->closing_date?->toDateString(),
            ];
        }
        foreach ($expiring as $e) {
            $todo[] = [
                'kind' => 'renew_evidence',
                'lot_id' => $e->listing_id,
                'lot' => $e->listing?->title,
                'evidence_kind' => $e->kind,
                'days' => (int) now()->startOfDay()->diffInDays($e->expires_at->copy()->startOfDay()),
            ];
        }

        return response()->json(['success' => true, 'data' => [
            'business' => BusinessSummary::of($org) + ['visible_to_buyers' => $visible],
            'counts' => [
                'active_lots' => CoffeeListing::where('organization_id', $org->id)->where('status', 'active')->count(),
                'open_requests' => $openCount,
                'samples_to_send' => $toSend->count(),
            ],
            'todo' => $todo,
        ]]);
    }
}
