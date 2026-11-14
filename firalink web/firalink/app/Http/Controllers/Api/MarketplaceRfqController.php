<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Services\MarketplaceSubscriptionService;
use App\Support\BusinessSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarketplaceRfqController extends Controller
{
    protected MarketplaceSubscriptionService $subscriptionService;

    public function __construct(MarketplaceSubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * GET /api/marketplace/rfqs
     * Public / open tenders board.
     */
    /** An RFQ with its buyer as a business only: never a person's email, phone or account fields. */
    private function rfqJson(MarketplaceRfq $rfq): array
    {
        $data = $rfq->makeHidden(['buyer'])->toArray();
        $data['buyer_business'] = BusinessSummary::forUser($rfq->buyer);

        return $data;
    }

    public function index(Request $request): JsonResponse
    {
        $query = MarketplaceRfq::query()->with('buyer')->open();

        if ($origin = $request->input('origin')) {
            $query->where('target_origin_region', 'LIKE', "%{$origin}%");
        }

        if ($grade = $request->input('grade')) {
            $query->where('target_grade', $grade);
        }

        if ($country = $request->input('destination_country')) {
            $query->where('destination_country', 'LIKE', "%{$country}%");
        }

        $rfqs = $query->latest('created_at')->paginate(15);
        $rfqs->setCollection($rfqs->getCollection()->map(fn ($r) => $this->rfqJson($r)));

        return response()->json([
            'success' => true,
            'data' => $rfqs,
        ]);
    }

    /**
     * GET /api/marketplace/rfqs/{id}
     * Show single RFQ tender details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user('sanctum');
        $rfq = MarketplaceRfq::with('buyer')->findOrFail($id);

        $hasQuoted = false;
        $myQuote = null;
        if ($user) {
            $myQuote = MarketplaceQuotation::where('rfq_id', $rfq->id)
                ->where('seller_id', $user->id)
                ->first();
            $hasQuoted = $myQuote !== null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'rfq' => $this->rfqJson($rfq),
                'has_quoted' => $hasQuoted,
                'my_quote' => $myQuote,
            ],
        ]);
    }

    /**
     * POST /api/marketplace/rfqs
     * Create new buyer RFQ tender.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'target_origin_region' => 'nullable|string|max:100',
            'target_grade' => 'nullable|string|max:50',
            'target_processing_method' => 'nullable|string|max:50',
            'target_cupping_score_min' => 'nullable|numeric|min:70|max:100',
            'volume_bags' => 'required|integer|min:1',
            'bag_type_preference' => 'nullable|string|max:50',
            'target_price_usd_per_lb' => 'nullable|numeric|min:0.5',
            'destination_country' => 'required|string|max:100',
            'destination_port' => 'required|string|max:100',
            'preferred_incoterm' => 'nullable|string|max:20',
            'preferred_payment_terms' => 'nullable|string|max:150',
            'target_delivery_date' => 'nullable|date|after:today',
            'closing_date' => 'nullable|date|after:today',
            'specifications' => 'nullable|string|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rfq = MarketplaceRfq::create([
            'buyer_id' => $user->id,
            'title' => $request->input('title'),
            'target_origin_region' => $request->input('target_origin_region'),
            'target_grade' => $request->input('target_grade'),
            'target_processing_method' => $request->input('target_processing_method'),
            'target_cupping_score_min' => $request->input('target_cupping_score_min'),
            'volume_bags' => $request->input('volume_bags'),
            'bag_type_preference' => $request->input('bag_type_preference', 'jute_with_grainpro'),
            'target_price_usd_per_lb' => $request->input('target_price_usd_per_lb'),
            'destination_country' => $request->input('destination_country'),
            'destination_port' => $request->input('destination_port'),
            'preferred_incoterm' => $request->input('preferred_incoterm', 'FOB'),
            'preferred_payment_terms' => $request->input('preferred_payment_terms'),
            'target_delivery_date' => $request->input('target_delivery_date'),
            'closing_date' => $request->input('closing_date', now()->addDays(30)),
            'specifications' => $request->input('specifications'),
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'RFQ tender published successfully. Ethiopian verified exporters can now submit competitive bids.',
            'data' => $rfq,
        ], 201);
    }

    /**
     * GET /api/marketplace/my-rfqs
     * Buyer's own published tenders.
     */
    public function myRfqs(Request $request): JsonResponse
    {
        $rfqs = $request->user()->marketplaceRfqs()
            ->withCount('quotations')
            ->latest('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $rfqs,
        ]);
    }

    /**
     * POST /api/marketplace/rfqs/{id}/quote
     * Submit an exporter quotation to an RFQ tender.
     */
    public function submitQuote(Request $request, int $id): JsonResponse
    {
        $seller = $request->user();
        $rfq = MarketplaceRfq::findOrFail($id);

        if ($rfq->status !== 'open') {
            return response()->json([
                'success' => false,
                'message' => 'This RFQ tender is no longer open for bidding.',
            ], 400);
        }

        // Verification Gate (Trust is never for sale)
        if (!$seller->isVerifiedExporter()) {
            return response()->json([
                'success' => false,
                'code' => 'VERIFICATION_REQUIRED',
                'required_role' => 'exporter',
                'message' => 'Only verified exporters may submit quotations to buyer RFQ tenders.',
            ], 403);
        }

        // Check subscription allowance
        $eligibility = $this->subscriptionService->canSubmitQuote($seller);
        if (!$eligibility['allowed']) {
            $org = $seller->primaryOrganization();
            $errorData = $org ? app(\App\Services\EntitlementService::class)->limitReachedResponse($org, 'rfq_responses_per_month') : [
                'code' => 'limit_reached',
                'feature' => 'rfq_responses_per_month',
                'message' => $eligibility['message'],
            ];

            return response()->json(array_merge(['success' => false], $errorData), 403);
        }

        $validator = Validator::make($request->all(), [
            'offered_price_usd_per_lb' => 'required|numeric|min:0.5',
            'quantity_bags' => 'required|integer|min:1',
            'listing_id' => 'nullable|exists:coffee_listings,id',
            'incoterm' => 'nullable|string|max:50',
            'port_of_loading' => 'nullable|string|max:50',
            'port_of_destination' => 'nullable|string|max:100',
            'payment_terms' => 'nullable|string|max:150',
            'estimated_shipping_date' => 'nullable|date',
            'validity_date' => 'required|date|after:today',
            'terms_and_conditions' => 'nullable|string|max:2000',
            'sample_available' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bags = (int) $request->input('quantity_bags');
        $pricePerLb = (float) $request->input('offered_price_usd_per_lb');
        // Total price = bags * 60kg * 2.20462 lbs * pricePerLb
        $totalPrice = round($bags * 60 * 2.20462262 * $pricePerLb, 2);

        $quotation = MarketplaceQuotation::create([
            'rfq_id' => $rfq->id,
            'listing_id' => $request->input('listing_id'),
            'seller_id' => $seller->id,
            'offered_price_usd_per_lb' => $pricePerLb,
            'total_price_usd' => $totalPrice,
            'quantity_bags' => $bags,
            'incoterm' => $request->input('incoterm', 'FOB Djibouti'),
            'port_of_loading' => $request->input('port_of_loading', 'Djibouti'),
            'port_of_destination' => $request->input('port_of_destination', $rfq->destination_port),
            'payment_terms' => $request->input('payment_terms', 'Irrevocable LC at sight'),
            'estimated_shipping_date' => $request->input('estimated_shipping_date'),
            'validity_date' => $request->input('validity_date'),
            'terms_and_conditions' => $request->input('terms_and_conditions'),
            'sample_available' => $request->boolean('sample_available', true),
            'status' => 'submitted',
        ]);

        $org = $seller->primaryOrganization();
        if ($org) {
            app(\App\Services\EntitlementService::class)->consume($org, 'rfq_responses_per_month', 1);
        }

        return response()->json([
            'success' => true,
            'message' => 'Your quotation was delivered to the buyer successfully.',
            'data' => $quotation,
        ], 201);
    }

    /**
     * GET /api/marketplace/rfqs/{id}/quotes
     * List all quotes received for an RFQ (only buyer owner can view).
     */
    public function rfqQuotes(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $rfq = MarketplaceRfq::where('id', $id)->where('buyer_id', $user->id)->firstOrFail();

        $quotes = $rfq->quotations()
            ->with(['seller', 'listing.primaryImage', 'listing.organization'])
            ->latest('created_at')
            ->get()
            ->map(function ($q) {
                // The seller as a business only: never a person's email or phone.
                $data = $q->makeHidden(['seller'])->toArray();
                $data['seller_business'] = BusinessSummary::of($q->listing?->organization) ?? BusinessSummary::forUser($q->seller);

                return $data;
            });

        return response()->json([
            'success' => true,
            'data' => [
                'rfq' => $this->rfqJson($rfq),
                'quotes' => $quotes,
            ],
        ]);
    }

    /**
     * GET /api/marketplace/my-quotes
     * Exporter's submitted quotations across all RFQs.
     */
    public function myQuotes(Request $request): JsonResponse
    {
        $quotes = $request->user()->marketplaceQuotations()
            ->with(['rfq', 'listing.primaryImage'])
            ->latest('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $quotes,
        ]);
    }

    /**
     * PUT /api/marketplace/quotes/{id}/status
     * Buyer accepts or declines a quotation.
     */
    public function updateQuoteStatus(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $quote = MarketplaceQuotation::with('rfq')->findOrFail($id);

        if ($quote->rfq->buyer_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the RFQ owner can accept or decline quotes.',
            ], 403);
        }

        $status = $request->input('status');
        if (!in_array($status, ['accepted', 'rejected'])) {
            return response()->json([
                'success' => false,
                'message' => "Invalid status. Must be 'accepted' or 'rejected'.",
            ], 422);
        }

        $quote->update([
            'status' => $status,
            'accepted_at' => $status === 'accepted' ? now() : null,
        ]);

        if ($status === 'accepted') {
            $quote->rfq->update(['status' => 'fulfilled']);
        }

        return response()->json([
            'success' => true,
            'message' => "Quotation marked as {$status}.",
            'data' => $quote,
        ]);
    }
}

