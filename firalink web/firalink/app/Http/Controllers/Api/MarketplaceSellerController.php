<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CoffeeListing;
use App\Models\CoffeeListingMedia;
use App\Models\MarketplaceSellerProfile;
use App\Models\MarketplaceSubscriptionPlan;
use App\Models\Payment;
use App\Models\UserMarketplaceSubscription;
use App\Services\MarketplaceSubscriptionService;
use App\Services\PaymentGatewayService;
use App\Services\PrivateDocumentStore;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class MarketplaceSellerController extends Controller
{
    protected MarketplaceSubscriptionService $subscriptionService;
    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(
        MarketplaceSubscriptionService $subscriptionService,
        PaymentGatewayService $paymentGatewayService
    ) {
        $this->subscriptionService = $subscriptionService;
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * GET /api/marketplace/seller/profile
     * Fetch seller company profile and verification badge status.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->sellerProfile;
        $activeSub = $user->activeMarketplaceSubscription?->load('plan');
        $eligibility = $this->subscriptionService->canPostListing($user);

        $org = $user->primaryOrganization();
        $entitlementsSnapshot = null;
        if ($org) {
            $entitlementsSnapshot = app(\App\Services\EntitlementService::class)->snapshot($org);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $profile,
                'organization' => $org ? [
                    'id' => $org->id,
                    'name' => $org->name,
                    'legal_name' => $org->legal_name,
                    'slug' => $org->slug,
                    'business_type' => $org->business_type,
                    'verification_tier' => $org->verification_tier,
                    'is_verified' => (bool) $org->is_verified,
                    'my_role' => $org->memberships()->where('user_id', $user->id)->value('role') ?? 'owner',
                ] : null,
                'active_subscription' => $activeSub,
                'listing_quota' => $eligibility,
                'entitlements_snapshot' => $entitlementsSnapshot,
            ],
        ]);
    }

    /**
     * POST /api/marketplace/seller/profile
     * Create or update seller profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|max:255',
            'business_type' => 'required|in:exporter,cooperative,commercial_farm,union,broker,roaster',
            'export_license_no' => 'nullable|string|max:100',
            'tin_number' => 'nullable|string|max:100',
            'origin_regions' => 'nullable|array',
            'washing_stations' => 'nullable|array',
            'washing_station_count' => 'nullable|integer|min:0',
            'annual_export_volume_mt' => 'nullable|numeric|min:0',
            'certifications' => 'nullable|array',
            'company_bio' => 'nullable|string|max:2000',
            'website_url' => 'nullable|url|max:255',
            'contact_email' => 'nullable|email|max:255',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'logo' => 'nullable|image|max:5120', // 5MB
            'banner' => 'nullable|image|max:10240', // 10MB
            'license_document' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $documents = app(PrivateDocumentStore::class);
        if ($request->hasFile('license_document')) {
            $reason = $documents->rejectionReason($request->file('license_document'));
            if ($reason) {
                return response()->json($documents->errorResponse('license_document', $reason), 422);
            }
        }

        $profile = $user->sellerProfile ?? new MarketplaceSellerProfile(['user_id' => $user->id]);

        $profile->company_name = $request->input('company_name');
        $profile->business_type = $request->input('business_type');
        $profile->export_license_no = $request->input('export_license_no');
        $profile->tin_number = $request->input('tin_number');
        $profile->origin_regions = $request->input('origin_regions', []);
        $profile->washing_stations = $request->input('washing_stations', []);
        $profile->washing_station_count = (int) $request->input('washing_station_count', 0);
        $profile->annual_export_volume_mt = $request->input('annual_export_volume_mt');
        $profile->certifications = $request->input('certifications', []);
        $profile->company_bio = $request->input('company_bio');
        $profile->website_url = $request->input('website_url');
        $profile->contact_email = $request->input('contact_email', $user->email);
        $profile->contact_phone = $request->input('contact_phone', $user->phone);
        $profile->address = $request->input('address');
        $profile->city = $request->input('city');

        // Handle File Uploads
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('marketplace/sellers/logos', 'public');
            $profile->logo_path = $path;
        }

        if ($request->hasFile('banner')) {
            $path = $request->file('banner')->store('marketplace/sellers/banners', 'public');
            $profile->banner_path = $path;
        }

        if ($request->hasFile('license_document')) {
            $profile->license_document_path = $documents->store(
                $request->file('license_document'),
                'marketplace/sellers/documents'
            );
        }

        $profile->save();

        // A new licence document puts the profile back in review (audited, via the review service).
        if ($request->hasFile('license_document')) {
            app(\App\Services\Verification\VerificationReviewService::class)->markSellerProfileUnderReview($profile, $user);
        }

        return response()->json([
            'success' => true,
            'message' => 'Seller business profile updated successfully.',
            'data' => $profile,
        ]);
    }

    /**
     * GET /api/marketplace/seller/listings
     * Manage seller's lots.
     */
    public function myListings(Request $request): JsonResponse
    {
        $listings = $request->user()->coffeeListings()
            ->with(['primaryImage', 'media'])
            ->latest('created_at')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $listings,
        ]);
    }

    /**
     * POST /api/marketplace/seller/listings
     * Create new coffee export lot.
     */
    public function storeListing(Request $request): JsonResponse
    {
        $user = $request->user();

        // Verification Gate (Trust is never for sale)
        if (!$user->isVerifiedExporter()) {
            return response()->json([
                'success' => false,
                'code' => 'VERIFICATION_REQUIRED',
                'required_role' => 'exporter',
                'message' => 'Trust is Never For Sale: Only verified exporters may publish coffee lots. Please complete your compliance verification dossier.',
            ], 403);
        }

        // Check subscription allowance
        $eligibility = $this->subscriptionService->canPostListing($user);
        if (!$eligibility['allowed']) {
            $org = $user->primaryOrganization();
            $errorData = $org ? app(\App\Services\EntitlementService::class)->limitReachedResponse($org, 'active_lots') : [
                'code' => 'limit_reached',
                'feature' => 'active_lots',
                'message' => $eligibility['message'],
            ];

            return response()->json(array_merge(['success' => false], $errorData), 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'origin_region' => 'required|string|max:100',
            'sub_region' => 'nullable|string|max:100',
            'washing_station' => 'nullable|string|max:150',
            'coffee_type' => 'nullable|string|max:50',
            'variety' => 'nullable|string|max:100',
            'processing_method' => 'required|in:washed,natural,anaerobic,honey,experimental',
            'grade' => 'required|in:grade_1,grade_2,grade_3,grade_4,grade_5,undergrade',
            'screen_size' => 'nullable|string|max:50',
            'cupping_score' => 'nullable|numeric|min:70|max:100',
            'moisture_content' => 'nullable|numeric|min:5|max:20',
            'water_activity' => 'nullable|numeric|min:0|max:1',
            'crop_year' => 'nullable|string|max:20',
            'altitude_min' => 'nullable|integer|min:500|max:3000',
            'altitude_max' => 'nullable|integer|min:500|max:3000',
            'total_quantity_bags' => 'required|integer|min:1',
            'available_quantity_bags' => 'nullable|integer|min:0',
            'minimum_order_bags' => 'nullable|integer|min:1',
            'bag_type' => 'nullable|string|max:50',
            'bag_weight_kg' => 'nullable|numeric|min:10',
            'indicative_price_usd_per_lb' => 'required|numeric|min:0.5',
            'incoterm' => 'nullable|string|max:50',
            'port_of_loading' => 'nullable|string|max:50',
            'sample_available' => 'nullable|boolean',
            'sample_cost_usd' => 'nullable|numeric|min:0',
            'tasting_notes' => 'nullable|array',
            'sensory_attributes' => 'nullable|array',
            'description' => 'nullable|string|max:5000',
            'primary_image' => 'nullable|image|max:10240',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'image|max:10240',
            'evidence_document' => 'nullable|file|max:10240',
            'cupping_document' => 'nullable|file|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Evidence files are checked by content and kept private. Uploading one never
        // raises the lot's evidence level; only an admin review does.
        $documents = app(PrivateDocumentStore::class);
        $evidenceField = $request->hasFile('evidence_document')
            ? 'evidence_document'
            : ($request->hasFile('cupping_document') ? 'cupping_document' : null);

        if ($evidenceField) {
            $reason = $documents->rejectionReason($request->file($evidenceField));
            if ($reason) {
                return response()->json($documents->errorResponse($evidenceField, $reason), 422);
            }
        }

        $pricePerLb = (float) $request->input('indicative_price_usd_per_lb');
        $pricePerMt = round($pricePerLb * 2204.62262, 2);
        $currentOrg = $user->currentOrganization() ?: $user->primaryOrganization();

        $evidenceDocPath = $evidenceField
            ? $documents->store($request->file($evidenceField), 'marketplace/listings/evidence')
            : null;

        $listing = CoffeeListing::create([
            'seller_id' => $user->id,
            'organization_id' => $currentOrg?->id,
            'title' => $request->input('title'),
            'origin_region' => $request->input('origin_region'),
            'sub_region' => $request->input('sub_region'),
            'washing_station' => $request->input('washing_station'),
            'coffee_type' => $request->input('coffee_type', 'arabica'),
            'variety' => $request->input('variety'),
            'processing_method' => $request->input('processing_method'),
            'grade' => $request->input('grade'),
            'screen_size' => $request->input('screen_size'),
            'cupping_score' => $request->input('cupping_score'),
            'evidence_document_path' => $evidenceDocPath,
            'moisture_content' => $request->input('moisture_content'),
            'water_activity' => $request->input('water_activity'),
            'crop_year' => $request->input('crop_year', '2025/2026'),
            'altitude_min' => $request->input('altitude_min'),
            'altitude_max' => $request->input('altitude_max'),
            'total_quantity_bags' => (int) $request->input('total_quantity_bags'),
            'available_quantity_bags' => (int) $request->input('available_quantity_bags', $request->input('total_quantity_bags')),
            'minimum_order_bags' => (int) $request->input('minimum_order_bags', 10),
            'bag_type' => $request->input('bag_type', 'jute_with_grainpro'),
            'packaging_type' => $request->input('packaging_type', 'GrainPro + Jute'),
            'bag_weight_kg' => (float) $request->input('bag_weight_kg', 60.00),
            'indicative_price_usd_per_lb' => $pricePerLb,
            'indicative_price_usd_per_mt' => $pricePerMt,
            'incoterm' => $request->input('incoterm', 'FOB Djibouti'),
            'port_of_loading' => $request->input('port_of_loading', 'Djibouti'),
            'sample_available' => $request->boolean('sample_available', true),
            'sample_cost_usd' => (float) $request->input('sample_cost_usd', 0.00),
            'tasting_notes' => $request->input('tasting_notes', []),
            'sensory_attributes' => $request->input('sensory_attributes'),
            'description' => $request->input('description'),
            'status' => 'active',
            'expires_at' => now()->addDays(90),
        ]);

        // Upload primary image
        if ($request->hasFile('primary_image')) {
            $file = $request->file('primary_image');
            $path = $file->store('marketplace/listings/images', 'public');
            CoffeeListingMedia::create([
                'listing_id' => $listing->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'media_type' => 'image',
                'is_primary' => true,
                'sort_order' => 1,
            ]);
        }

        // Upload additional gallery images
        if ($request->hasFile('gallery_images')) {
            $order = 2;
            foreach ($request->file('gallery_images') as $galleryFile) {
                $path = $galleryFile->store('marketplace/listings/images', 'public');
                CoffeeListingMedia::create([
                    'listing_id' => $listing->id,
                    'file_path' => $path,
                    'file_name' => $galleryFile->getClientOriginalName(),
                    'file_size' => $galleryFile->getSize(),
                    'mime_type' => $galleryFile->getMimeType(),
                    'media_type' => 'image',
                    'is_primary' => false,
                    'sort_order' => $order++,
                ]);
            }
        }

        // Upload cupping lab certificate
        if ($request->hasFile('cupping_document')) {
            $doc = $request->file('cupping_document');
            $path = $doc->store('marketplace/listings/documents', 'public');
            CoffeeListingMedia::create([
                'listing_id' => $listing->id,
                'file_path' => $path,
                'file_name' => $doc->getClientOriginalName(),
                'file_size' => $doc->getSize(),
                'mime_type' => $doc->getMimeType(),
                'media_type' => 'cupping_report',
                'is_primary' => false,
                'sort_order' => 10,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Coffee lot published to marketplace successfully.',
            'data' => $listing->load('media'),
        ], 201);
    }

    /**
     * GET /api/marketplace/plans
     * Public list of available subscription tiers.
     */
    /**
     * GET /api/marketplace/plans
     * Public list of available subscription tiers (dynamic SaaS plans with legacy fallback).
     */
    public function plans(): JsonResponse
    {
        $dynamicPlans = \App\Models\Plan::where('is_public', true)
            ->where('is_active', true)
            ->with(['prices', 'entitlements.feature'])
            ->orderBy('sort_order')
            ->get();

        if ($dynamicPlans->isNotEmpty()) {
            $formattedPlans = $dynamicPlans->map(function ($plan) {
                $monthlyEtb = $plan->prices->first(fn($p) => $p->currency === 'ETB' && $p->billing_period === 'monthly');
                $monthlyUsd = $plan->prices->first(fn($p) => $p->currency === 'USD' && $p->billing_period === 'monthly');
                $annualEtb = $plan->prices->first(fn($p) => $p->currency === 'ETB' && $p->billing_period === 'annual');
                $annualUsd = $plan->prices->first(fn($p) => $p->currency === 'USD' && $p->billing_period === 'annual');

                $entitlementsMap = [];
                $featureStrings = [];
                foreach ($plan->entitlements as $ent) {
                    $entitlementsMap[$ent->feature_key] = $ent->value;
                    $lbl = $ent->feature ? $ent->feature->label : str_replace('_', ' ', $ent->feature_key);
                    if ($ent->value === -1 || $ent->value === '-1') {
                        $featureStrings[] = "Unlimited {$lbl}";
                    } elseif (is_numeric($ent->value) && (int) $ent->value > 0) {
                        $unit = $ent->feature?->unit ? " {$ent->feature->unit}" : '';
                        $featureStrings[] = "Up to {$ent->value}{$unit} {$lbl}";
                    } elseif ($ent->value === true || $ent->value === '1' || $ent->value === 'true') {
                        $featureStrings[] = $lbl;
                    } elseif ($ent->value !== false && $ent->value !== '0' && $ent->value !== 'false') {
                        $featureStrings[] = "{$lbl}: {$ent->value}";
                    }
                }

                $activeLotsLimit = (int) ($entitlementsMap['active_lots'] ?? 3);
                $badge = match ($plan->key) {
                    'growth' => 'Most Popular',
                    'business' => 'Enterprise Grade',
                    default => null,
                };

                return [
                    'id' => $plan->id,
                    'key' => $plan->key,
                    'slug' => $plan->key,
                    'name' => $plan->name,
                    'description' => $plan->description,
                    'trial_days' => $plan->trial_days,
                    'badge' => $badge,
                    'is_popular' => $plan->key === 'growth',
                    'price_etb' => (float) ($monthlyEtb ? $monthlyEtb->amount : 0.00),
                    'price_usd' => (float) ($monthlyUsd ? $monthlyUsd->amount : 0.00),
                    'annual_price_etb' => (float) ($annualEtb ? $annualEtb->amount : 0.00),
                    'annual_price_usd' => (float) ($annualUsd ? $annualUsd->amount : 0.00),
                    'listing_limit' => $activeLotsLimit === -1 ? 999999 : $activeLotsLimit,
                    'rfq_limit' => (int) ($entitlementsMap['rfq_responses_per_month'] ?? 5),
                    'deals_limit' => (int) ($entitlementsMap['active_deal_rooms'] ?? 2),
                    'storage_gb' => (int) ($entitlementsMap['storage_gb'] ?? 1),
                    'features' => $featureStrings,
                    'entitlements' => $entitlementsMap,
                    'prices' => $plan->prices->map(fn($p) => [
                        'currency' => $p->currency,
                        'billing_period' => $p->billing_period,
                        'amount' => (float) $p->amount,
                    ]),
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedPlans,
            ]);
        }

        $plans = MarketplaceSubscriptionPlan::active()->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    /**
     * POST /api/marketplace/subscribe
     * Initialize subscription checkout (Chapa card/telebirr or offline bank slip).
     */
    public function subscribe(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Check if plan is a Dynamic SaaS Plan (by key or by ID)
        $planParam = $request->input('plan_id') ?? $request->input('plan_key');
        $saasPlan = null;
        if (is_numeric($planParam)) {
            $saasPlan = \App\Models\Plan::find((int) $planParam);
        }
        if (!$saasPlan && is_string($planParam)) {
            $saasPlan = \App\Models\Plan::where('key', strtolower(trim($planParam)))->first();
        }

        if ($saasPlan) {
            $validator = Validator::make($request->all(), [
                'gateway' => 'required|in:chapa,offline',
                'period' => 'nullable|in:monthly,annual',
                'currency' => 'nullable|in:ETB,USD',
                'receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
                'transaction_id' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // No automatic business creation: the user sets one up first (Business setup).
            $org = $user->currentOrganization() ?? $user->primaryOrganization();
            if (!$org) {
                return response()->json([
                    'success' => false,
                    'code' => 'ORGANIZATION_REQUIRED',
                    'message' => 'Create a business workspace before choosing a plan.',
                ], 422);
            }

            // Enforce RBAC: Only organization owner can manage or purchase billing
            if (!$org->canManageBilling($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permission denied. Only organization owners can manage or purchase subscription plans.',
                ], 403);
            }

            $period = $request->input('period', 'monthly');
            $currency = $request->input('currency', 'ETB');
            $gateway = $request->input('gateway', 'chapa');

            $billingService = app(\App\Services\Billing\BillingService::class);
            $checkoutResult = $billingService->subscribe($org, $saasPlan, $period, $currency, $gateway, $user);

            return response()->json($checkoutResult);
        }

        // 2. Fallback to Legacy MarketplaceSubscriptionPlan
        $validator = Validator::make($request->all(), [
            'plan_id' => 'required|exists:marketplace_subscription_plans,id',
            'gateway' => 'required|in:chapa,offline',
            'receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'transaction_id' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plan = MarketplaceSubscriptionPlan::findOrFail($request->input('plan_id'));
        $gateway = $request->input('gateway');

        $data = [
            'transaction_id' => $request->input('transaction_id'),
            'notes' => $request->input('notes'),
        ];

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file'] = $request->file('receipt_file')->store('receipts/marketplace', 'public');
        }

        try {
            $result = $this->subscriptionService->initializeSubscription($user, $plan, $gateway, $data);
            return response()->json($result);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * POST /api/marketplace/subscribe/chapa/verify
     * Verification endpoint for Chapa subscription payments.
     */
    public function chapaVerify(Request $request): JsonResponse
    {
        $txRef = $request->input('tx_ref');
        if (!$txRef) {
            return response()->json(['success' => false, 'message' => 'tx_ref is required'], 400);
        }

        $payment = Payment::where('chapa_tx_ref', $txRef)->first();
        if (!$payment) {
            return response()->json(['success' => false, 'message' => 'Payment reference not found'], 404);
        }

        if ($payment->status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'Payment already verified and completed.',
                'data' => $payment->load('marketplaceSubscription.plan'),
            ]);
        }

        // Verify with Chapa API
        $verifyResult = $this->paymentGatewayService->verifyChapa($txRef);
        if (!$verifyResult['success']) {
            return response()->json([
                'success' => false,
                'message' => $verifyResult['message'] ?? 'Chapa verification failed.',
            ], 400);
        }

        $payment->update([
            'status' => 'completed',
            'chapa_reference' => $verifyResult['data']['reference'] ?? null,
        ]);

        if ($payment->marketplaceSubscription) {
            $this->subscriptionService->activateSubscription($payment->marketplaceSubscription);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subscription payment verified successfully! Your account features are now unlocked.',
            'data' => $payment->load('marketplaceSubscription.plan'),
        ]);
    }
}

