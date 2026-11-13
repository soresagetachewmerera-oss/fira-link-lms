<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoffeeListing;
use App\Models\MarketplaceInquiry;
use App\Services\MarketplaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MarketplaceController extends Controller
{
    protected MarketplaceService $marketplaceService;

    public function __construct(MarketplaceService $marketplaceService)
    {
        $this->marketplaceService = $marketplaceService;
    }

    /**
     * GET /api/marketplace/listings
     * Public list of green coffee listings.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 50);
        $listings = $this->marketplaceService->getListings($request, $perPage);

        return response()->json([
            'success' => true,
            'message' => 'Coffee listings retrieved successfully',
            'data' => $listings,
        ]);
    }

    /**
     * GET /api/marketplace/listings/{idOrSlug}
     * Public details of a single coffee lot.
     */
    public function show(Request $request, string $idOrSlug): JsonResponse
    {
        $user = $request->user('sanctum');
        $detail = $this->marketplaceService->getListingDetail($idOrSlug, $user);

        return response()->json([
            'success' => true,
            'message' => 'Listing retrieved successfully',
            'data' => $detail,
        ]);
    }

    /**
     * GET /api/marketplace/filters
     * Taxonomy filters for search dropdowns.
     */
    public function filters(): JsonResponse
    {
        $taxonomies = $this->marketplaceService->getFilterTaxonomies();

        return response()->json([
            'success' => true,
            'data' => $taxonomies,
        ]);
    }

    /**
     * POST /api/marketplace/listings/{id}/save
     * Bookmark/save a listing.
     */
    public function toggleSave(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        // Hidden lots cannot be saved (404, as if they did not exist).
        CoffeeListing::visibleToBuyers()->findOrFail($id);
        $isSaved = $this->marketplaceService->toggleSaveListing($user, $id);

        return response()->json([
            'success' => true,
            'message' => $isSaved ? 'Listing saved to your favorites' : 'Listing removed from favorites',
            'data' => ['is_saved' => $isSaved],
        ]);
    }

    /**
     * GET /api/marketplace/saved-listings
     * List user's bookmarked listings.
     */
    public function savedListings(Request $request): JsonResponse
    {
        $listings = $request->user()->savedCoffeeListings()
            ->visibleToBuyers()
            ->with(['primaryImage', 'seller.sellerProfile'])
            ->latest('marketplace_saved_listings.created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * POST /api/marketplace/listings/{id}/inquire
     * Send direct commercial trade inquiry or green coffee sample request.
     */
    public function inquire(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        // Hidden lots cannot receive inquiries.
        $listing = CoffeeListing::visibleToBuyers()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'inquiry_type' => 'required|in:general,sample_request,price_quote,visit_request',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'quantity_interested_bags' => 'nullable|integer|min:1',
            'shipping_address' => 'nullable|string|max:1000',
            'courier_account' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $inquiry = MarketplaceInquiry::create([
            'listing_id' => $listing->id,
            'seller_id' => $listing->seller_id,
            'buyer_id' => $user->id,
            'inquiry_type' => $request->input('inquiry_type'),
            'subject' => $request->input('subject'),
            'message' => $request->input('message'),
            'quantity_interested_bags' => $request->input('quantity_interested_bags'),
            'shipping_address' => $request->input('shipping_address'),
            'courier_account' => $request->input('courier_account'),
            'sample_status' => $request->input('inquiry_type') === 'sample_request' ? 'requested' : 'none',
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Inquiry submitted successfully. The exporter will receive your request immediately.',
            'data' => $inquiry,
        ], 201);
    }

    /**
     * GET /api/marketplace/inquiries
     * User's inquiries (sent as buyer or received as seller).
     */
    public function myInquiries(Request $request): JsonResponse
    {
        $user = $request->user();
        $role = $request->input('view', 'all'); // 'buyer', 'seller', or 'all'

        $query = MarketplaceInquiry::query()
            ->with(['listing.primaryImage', 'seller.sellerProfile', 'buyer']);

        if ($role === 'seller') {
            $query->where('seller_id', $user->id);
        } elseif ($role === 'buyer') {
            $query->where('buyer_id', $user->id);
        } else {
            $query->where(function ($q) use ($user) {
                $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id);
            });
        }

        $inquiries = $query->latest()->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $inquiries,
        ]);
    }
}

