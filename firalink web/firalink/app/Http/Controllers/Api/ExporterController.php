<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoffeeListing;
use App\Models\MarketplaceQuotation;
use App\Models\Organization;
use App\Services\Verification\PublicSellerProfile;
use App\Services\Verification\VerificationReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Exporters tab and exporter profile (S-114), for signed-in users only and rate-limited.
 * Verified seller businesses only, public fields only: never TIN, contacts, documents or people.
 */
class ExporterController extends Controller
{
    private function card(Organization $org): array
    {
        $profile = $org->sellerProfile;

        return [
            'id' => $org->id,
            'name' => $org->name,
            'business_role' => $org->roleKey(),
            'city' => $profile?->city,
            'country' => $org->country,
            'verification_tier' => (int) $org->verification_tier,
            'fira_id' => $org->fira_id,
            'member_since' => (int) $org->created_at?->format('Y'),
            'active_lots' => CoffeeListing::where('organization_id', $org->id)->visibleToBuyers()->count(),
            'logo_url' => $profile?->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($profile->logo_path) : null,
        ];
    }

    /** Median hours from a request being posted to this business's quote, last 90 days (3+ quotes). */
    public static function usualReplyHours(Organization $org): ?int
    {
        $hours = MarketplaceQuotation::query()
            ->join('marketplace_rfqs', 'marketplace_rfqs.id', '=', 'marketplace_quotations.rfq_id')
            ->where('marketplace_quotations.organization_id', $org->id)
            ->where('marketplace_quotations.created_at', '>=', now()->subDays(90))
            ->get(['marketplace_quotations.created_at as quoted_at', 'marketplace_rfqs.created_at as posted_at'])
            ->map(fn ($r) => max(0, \Illuminate\Support\Carbon::parse($r->posted_at)->diffInMinutes(\Illuminate\Support\Carbon::parse($r->quoted_at)) / 60))
            ->sort()->values();

        if ($hours->count() < 3) {
            return null;
        }
        $mid = intdiv($hours->count(), 2);
        $median = $hours->count() % 2 ? $hours[$mid] : ($hours[$mid - 1] + $hours[$mid]) / 2;

        return (int) max(1, round($median));
    }

    /** GET /marketplace/exporters — verified seller businesses (search by name or region of their lots). */
    public function index(Request $request): JsonResponse
    {
        $query = Organization::query()->with('sellerProfile')
            ->where(VerificationReviewService::approvedSellerConstraint());
        if ($q = trim((string) $request->input('search'))) {
            $query->where('name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%');
        }
        if ($region = trim((string) $request->input('region'))) {
            $query->whereHas('coffeeListings', fn ($l) => $l->whereRaw('LOWER(origin_region) = ?', [mb_strtolower($region)])->whereIn('status', CoffeeListing::BUYER_VISIBLE_STATUSES));
        }

        $page = $query->orderByDesc('verification_tier')->orderBy('name')->paginate(20);
        $page->setCollection($page->getCollection()->map(fn ($o) => $this->card($o)));

        return response()->json(['success' => true, 'data' => $page]);
    }

    /** GET /marketplace/exporters/{organization} — S-114. Unverified or unknown businesses: not found. */
    public function show(Organization $organization): JsonResponse
    {
        if (!VerificationReviewService::isApprovedSeller($organization)) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND'], 404);
        }

        $lots = CoffeeListing::where('organization_id', $organization->id)->visibleToBuyers()
            ->with(['primaryImage', 'organization'])->latest('published_at')->limit(20)->get();

        return response()->json([
            'success' => true,
            'data' => $this->card($organization) + [
                'about' => $organization->sellerProfile?->company_bio,
                'confirmed_deals' => PublicSellerProfile::confirmedDeals($organization),
                'usual_reply_hours' => self::usualReplyHours($organization),
                'public_url' => $organization->fira_id ? route('verify.show', $organization->fira_id) : null,
                'lots' => $lots,
            ],
        ]);
    }
}
