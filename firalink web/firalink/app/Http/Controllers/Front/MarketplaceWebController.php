<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CoffeeListing;
use App\Models\CoffeeListingMedia;
use App\Models\CoffeeMinimumPriceRecord;
use App\Models\MarketplaceInquiry;
use App\Models\MarketplaceQuotation;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSellerProfile;
use App\Models\MarketplaceSubscriptionPlan;
use App\Models\UserMarketplaceSubscription;
use App\Models\Feature;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlanEntitlement;
use App\Services\Billing\BillingService;
use App\Services\EntitlementService;
use App\Services\MarketplaceService;
use App\Services\MarketplaceSubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class MarketplaceWebController extends Controller
{
    protected MarketplaceService $marketplaceService;
    protected MarketplaceSubscriptionService $subscriptionService;
    protected BillingService $billingService;
    protected EntitlementService $entitlementService;

    public function __construct(
        MarketplaceService $marketplaceService,
        MarketplaceSubscriptionService $subscriptionService,
        BillingService $billingService,
        EntitlementService $entitlementService
    ) {
        $this->marketplaceService = $marketplaceService;
        $this->subscriptionService = $subscriptionService;
        $this->billingService = $billingService;
        $this->entitlementService = $entitlementService;
    }

    /**
     * Public Marketplace Catalog
     */
    public function index(Request $request)
    {
        $listings = $this->marketplaceService->getListings($request, 12);
        $taxonomies = $this->marketplaceService->getFilterTaxonomies();

        // Get recent benchmark price highlights
        $recentBenchmarks = CoffeeMinimumPriceRecord::query()
            ->latest('effective_date')
            ->limit(6)
            ->get();

        return view('marketplace.index', compact('listings', 'taxonomies', 'recentBenchmarks'));
    }

    /**
     * Single Lot Detail View
     */
    public function show(Request $request, string $slug)
    {
        $user = Auth::user();
        $detail = $this->marketplaceService->getListingDetail($slug, $user);

        return view('marketplace.show', [
            'listing' => $detail['listing'],
            'isSaved' => $detail['is_saved'],
            'benchmark' => $detail['official_benchmark'],
            'relatedLots' => $detail['related_lots'],
        ]);
    }

    /**
     * Verified Coffee Exporters & Suppliers Directory
     */
    public function suppliers(Request $request)
    {
        $query = MarketplaceSellerProfile::with(['user'])
            ->withCount(['listings' => fn($q) => $q->active()]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'LIKE', "%{$search}%")
                  ->orWhere('city', 'LIKE', "%{$search}%")
                  ->orWhere('company_bio', 'LIKE', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('business_type', $type);
        }

        if ($origin = $request->input('origin')) {
            $query->whereJsonContains('origin_regions', $origin);
        }

        $suppliers = $query->latest('created_at')->paginate(9);
        $taxonomies = $this->marketplaceService->getFilterTaxonomies();

        return view('marketplace.suppliers', compact('suppliers', 'taxonomies'));
    }

    /**
     * Single Exporter Profile & Published Lots
     */
    public function showSupplier(string $slug)
    {
        $seller = MarketplaceSellerProfile::where('company_slug', $slug)
            ->with(['user', 'listings' => fn($q) => $q->active()->with('primaryImage')])
            ->firstOrFail();

        return view('marketplace.supplier-show', compact('seller'));
    }

    /**
     * Public RFQ Procurement Tenders Board
     */
    public function rfqs(Request $request)
    {
        $query = MarketplaceRfq::query()->with('buyer')->open();

        if ($origin = $request->input('origin')) {
            $query->where('target_origin_region', 'LIKE', "%{$origin}%");
        }

        if ($country = $request->input('destination_country')) {
            $query->where('destination_country', 'LIKE', "%{$country}%");
        }

        $rfqs = $query->latest('created_at')->paginate(10);
        $taxonomies = $this->marketplaceService->getFilterTaxonomies();

        return view('marketplace.rfqs', compact('rfqs', 'taxonomies'));
    }

    /**
     * Web Form to Post an RFQ
     */
    public function createRfq()
    {
        $taxonomies = $this->marketplaceService->getFilterTaxonomies();
        return view('marketplace.create-rfq', compact('taxonomies'));
    }

    /**
     * Store Buyer RFQ
     */
    public function storeRfq(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Please sign in or register to publish a procurement RFQ.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'target_origin_region' => 'nullable|string|max:100',
            'target_grade' => 'nullable|string|max:50',
            'target_processing_method' => 'nullable|string|max:50',
            'target_cupping_score_min' => 'nullable|numeric|between:70,100',
            'volume_bags' => 'required|integer|min:1',
            'bag_type_preference' => 'nullable|string|max:50',
            'target_price_usd_per_lb' => 'nullable|numeric|min:0.5',
            'destination_country' => 'required|string|max:100',
            'destination_port' => 'required|string|max:100',
            'preferred_incoterm' => 'required|string|max:20',
            'preferred_payment_terms' => 'nullable|string|max:100',
            'target_delivery_date' => 'nullable|date',
            'closing_date' => 'nullable|date|after_or_equal:today',
            'specifications' => 'nullable|string|max:2000',
        ]);

        $validated['buyer_id'] = Auth::id();
        $validated['status'] = 'open';

        $rfq = MarketplaceRfq::create($validated);

        return redirect()->route('marketplace.rfqs')->with('success', "Procurement tender '{$rfq->title}' has been published successfully (Reference: {$rfq->rfq_number}).");
    }

    /**
     * Web Form to Publish Coffee Lot
     */
    public function createListing()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Please sign in as an exporter to publish coffee lots.');
        }

        $user = Auth::user();
        if (!$user->isVerifiedExporter()) {
            return redirect()->route('marketplace.verification.center')
                ->with('warning', 'Trust is Never For Sale: Only verified Ethiopian exporters or producers can publish live coffee export lots. Please complete your compliance verification.');
        }

        $quota = $this->subscriptionService->canPostListing($user);
        if (!$quota['allowed']) {
            return redirect()->route('marketplace.pricing')
                ->with('warning', $quota['message'])
                ->with('error', $quota['message']);
        }

        $taxonomies = $this->marketplaceService->getFilterTaxonomies();
        return view('marketplace.create-listing', compact('taxonomies', 'quota'));
    }

    /**
     * Store Coffee Lot from Web Form
     */
    public function storeListing(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Please sign in to publish coffee lots.');
        }

        $user = Auth::user();
        if (!$user->isVerifiedExporter()) {
            return redirect()->route('marketplace.verification.center')
                ->with('warning', 'Trust is Never For Sale: Only verified Ethiopian exporters or producers can publish live coffee export lots. Please complete your compliance verification.');
        }

        $quota = $this->subscriptionService->canPostListing($user);
        if (!$quota['allowed']) {
            return redirect()->route('marketplace.pricing')
                ->with('warning', $quota['message'])
                ->with('error', $quota['message']);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'origin_region' => 'required|string|max:100',
            'sub_region' => 'nullable|string|max:100',
            'washing_station' => 'nullable|string|max:150',
            'coffee_type' => 'required|string|max:50',
            'variety' => 'nullable|string|max:150',
            'processing_method' => 'required|string|max:50',
            'grade' => 'required|string|max:50',
            'screen_size' => 'nullable|string|max:50',
            'cupping_score' => 'nullable|numeric|between:70,100',
            'moisture_content' => 'nullable|numeric|between:8,15',
            'crop_year' => 'required|string|max:20',
            'total_quantity_bags' => 'required|integer|min:1',
            'available_quantity_bags' => 'nullable|integer|min:0',
            'minimum_order_bags' => 'nullable|integer|min:1',
            'indicative_price_usd_per_lb' => 'required|numeric|min:0.5',
            'incoterm' => 'required|string|max:50',
            'sample_available' => 'boolean',
            'tasting_notes' => 'nullable|string',
            'description' => 'nullable|string|max:3000',
            'image_url' => 'nullable|url',
        ]);

        $validated['seller_id'] = Auth::id();
        $validated['available_quantity_bags'] = $validated['available_quantity_bags'] ?? $validated['total_quantity_bags'];
        $validated['indicative_price_usd_per_mt'] = $validated['indicative_price_usd_per_lb'] * 2204.62;
        $validated['status'] = 'active';

        if (!empty($validated['tasting_notes'])) {
            $validated['tasting_notes'] = array_map('trim', explode(',', $validated['tasting_notes']));
        }

        $imageUrl = $validated['image_url'] ?? null;
        unset($validated['image_url']);

        $listing = CoffeeListing::create($validated);

        if ($imageUrl) {
            CoffeeListingMedia::create([
                'listing_id' => $listing->id,
                'file_path' => $imageUrl,
                'file_name' => basename($imageUrl),
                'media_type' => 'image',
                'is_primary' => true,
                'sort_order' => 1,
            ]);
        }

        return redirect()->route('marketplace.show', $listing->slug)->with('success', "Specialty lot '{$listing->title}' published successfully!");
    }

    /**
     * Submit an Inquiry or Sample Request
     */
    public function inquire(Request $request, int $id)
    {
        $listing = CoffeeListing::findOrFail($id);

        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Please sign in to inquire.'], 401);
            }
            return redirect()->route('login')->with('warning', 'Please sign in or register to submit an inquiry to this exporter.');
        }

        $validated = $request->validate([
            'inquiry_type' => 'required|string|in:general,sample_request,price_quote,visit_request',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:3000',
            'quantity_interested_bags' => 'nullable|integer|min:1',
            'shipping_address' => 'nullable|string|max:500',
            'courier_account' => 'nullable|string|max:100',
        ]);

        $inquiry = MarketplaceInquiry::create([
            'listing_id' => $listing->id,
            'seller_id' => $listing->seller_id,
            'buyer_id' => Auth::id(),
            'inquiry_type' => $validated['inquiry_type'],
            'subject' => $validated['subject'],
            'message' => $validated['message'],
            'quantity_interested_bags' => $validated['quantity_interested_bags'] ?? null,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'courier_account' => $validated['courier_account'] ?? null,
            'sample_status' => $validated['inquiry_type'] === 'sample_request' ? 'requested' : 'none',
            'status' => 'pending',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Inquiry delivered to exporter successfully.',
                'data' => $inquiry,
            ]);
        }

        $msg = $validated['inquiry_type'] === 'sample_request'
            ? 'Your green coffee sample request has been submitted to the exporter!'
            : 'Your inquiry has been delivered directly to the exporter!';

        return redirect()->back()->with('success', $msg);
    }

    /**
     * Toggle Bookmark / Saved Listing
     */
    public function toggleSave(Request $request, int $id)
    {
        if (!Auth::check()) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
            }
            return redirect()->route('login')->with('warning', 'Please sign in to bookmark coffee lots.');
        }

        $res = $this->marketplaceService->toggleSaveListing(Auth::user(), $id);

        if ($request->wantsJson()) {
            return response()->json($res);
        }

        return redirect()->back()->with('success', $res['is_saved'] ? 'Saved to your favorites.' : 'Removed from favorites.');
    }

    /**
     * My Marketplace Dashboard (Unified Buyer & Seller Hub)
     */
    public function dashboard()
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Please sign in to view your marketplace dashboard.');
        }

        $user = Auth::user();

        // Seller profile & metrics
        $sellerProfile = MarketplaceSellerProfile::where('user_id', $user->id)->first();
        $myListings = CoffeeListing::where('seller_id', $user->id)->with('primaryImage')->latest()->get();
        $receivedInquiries = MarketplaceInquiry::where('seller_id', $user->id)->with(['listing', 'buyer'])->latest()->get();
        $submittedQuotes = MarketplaceQuotation::where('seller_id', $user->id)->with(['rfq', 'listing'])->latest()->get();

        // Buyer metrics
        $sentInquiries = MarketplaceInquiry::where('buyer_id', $user->id)->with(['listing', 'seller'])->latest()->get();
        $myRfqs = MarketplaceRfq::where('buyer_id', $user->id)->withCount('quotations')->latest()->get();
        $receivedQuotes = MarketplaceQuotation::whereHas('rfq', fn($q) => $q->where('buyer_id', $user->id))
            ->with(['rfq', 'seller', 'listing'])
            ->latest()
            ->get();
        $savedListings = $user->savedCoffeeListings()->with('primaryImage')->get();

        // Organization & SaaS Entitlement Snapshot
        $org = $user->primaryOrganization();
        $snapshot = $org ? $this->entitlementService->snapshot($org) : null;
        $isOwner = $org ? $org->canManageBilling($user) : true;
        $invoices = $org ? $org->invoices()->latest()->take(10)->get() : collect();

        // Legacy Subscription & Quotas compatibility
        $activeSubscription = $org?->activeSubscription?->load('plan') ?? UserMarketplaceSubscription::where('user_id', $user->id)
            ->where('status', 'active')
            ->with('plan')
            ->latest('starts_at')
            ->first();

        $quota = $this->subscriptionService->canPostListing($user);

        return view('marketplace.dashboard', compact(
            'sellerProfile',
            'myListings',
            'receivedInquiries',
            'submittedQuotes',
            'sentInquiries',
            'myRfqs',
            'receivedQuotes',
            'savedListings',
            'activeSubscription',
            'quota',
            'org',
            'snapshot',
            'isOwner',
            'invoices'
        ));
    }

    /**
     * Subscription Plans & Pricing Page (Dynamic Database-Driven)
     */
    public function pricing()
    {
        $plans = Plan::with(['activePrices', 'entitlements.feature'])
            ->where('is_active', true)
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->get();

        $features = Feature::orderBy('id')->get();

        // Legacy plan fallback compatibility
        $sellerPlans = MarketplaceSubscriptionPlan::active()->forSellers()->get();
        $buyerPlans = MarketplaceSubscriptionPlan::active()->forBuyers()->get();

        return view('marketplace.pricing', compact('plans', 'features', 'sellerPlans', 'buyerPlans'));
    }

    /**
     * Subscribe or Change Plan via Web
     */
    public function subscribe(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('warning', 'Please sign in to select a subscription plan.');
        }

        $user = Auth::user();
        $org = $user->primaryOrganization();

        if (!$org && $user->sellerProfile) {
            $org = Organization::firstOrCreate(
                ['slug' => $user->sellerProfile->company_slug ?? 'org-' . $user->id],
                [
                    'name' => $user->sellerProfile->company_name ?? 'Exporter ' . $user->name,
                    'business_type' => $user->sellerProfile->business_type ?? 'exporter',
                    'status' => 'active',
                    'billing_email' => $user->email,
                ]
            );
            $user->sellerProfile->update(['organization_id' => $org->id]);
            $user->organizations()->syncWithoutDetaching([$org->id => ['role' => 'owner', 'is_active' => true]]);
        }

        if (!$org) {
            return redirect()->route('marketplace.dashboard')
                ->with('error', 'Please create an organization or exporter profile before selecting a subscription.');
        }

        // HARD CONSTRAINT: Only the org Owner role may change plan or billing
        if (!$org->canManageBilling($user)) {
            return redirect()->route('marketplace.dashboard')
                ->with('error', 'Permission denied: Only the organization Owner role may change plans or billing settings.');
        }

        $validated = $request->validate([
            'plan_id' => 'required',
            'period' => 'nullable|string|in:monthly,annual',
            'currency' => 'nullable|string|in:ETB,USD',
            'gateway' => 'nullable|string|in:chapa,offline',
        ]);

        $period = $validated['period'] ?? 'monthly';
        $currency = $validated['currency'] ?? 'ETB';
        $gateway = $validated['gateway'] ?? 'chapa';

        // Check if plan_id matches a dynamic SaaS Plan
        $plan = is_numeric($validated['plan_id'])
            ? (Plan::find($validated['plan_id']) ?? MarketplaceSubscriptionPlan::find($validated['plan_id']))
            : Plan::where('key', $validated['plan_id'])->first();

        if ($plan instanceof Plan) {
            $res = $this->billingService->subscribe($org, $plan, $period, $currency, $gateway, $user);

            if (!empty($res['checkout_url'])) {
                return redirect()->away($res['checkout_url']);
            }

            return redirect()->route('marketplace.dashboard')
                ->with('success', $res['message'] ?? "Subscribed to {$plan->name} successfully!");
        }

        // Legacy fallback
        if ($plan instanceof MarketplaceSubscriptionPlan) {
            $res = $this->subscriptionService->initializeSubscription($user, $plan, $gateway);
            if (!empty($res['payment_url'])) {
                return redirect()->away($res['payment_url']);
            }
            return redirect()->route('marketplace.dashboard')
                ->with('success', "Subscription for plan '{$plan->name}' has been activated!");
        }

        return redirect()->back()->with('error', 'Invalid subscription plan selected.');
    }

    /**
     * Cancel or set subscription to cancel at period end (Owner only)
     */
    public function cancelSubscription(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();
        $org = $user->primaryOrganization();

        if (!$org || !$org->canManageBilling($user)) {
            return redirect()->route('marketplace.dashboard')
                ->with('error', 'Permission denied: Only the organization Owner role may manage subscription cancellation.');
        }

        $sub = $org->activeSubscription;
        if (!$sub) {
            return redirect()->route('marketplace.dashboard')->with('error', 'No active subscription found to cancel.');
        }

        $starterPlan = Plan::where('key', 'starter')->first();
        $immediate = $request->boolean('immediate', false);

        if ($immediate && $starterPlan) {
            $sub->update([
                'plan_id' => $starterPlan->id,
                'status' => 'active',
                'cancel_at_period_end' => false,
            ]);
            $this->entitlementService->reconcileDowngrade($org, $starterPlan);
            $msg = 'Your subscription has been switched to Starter. Lots exceeding your quota have been set to inactive (never deleted). Live deals remain active.';
        } else {
            $sub->update(['cancel_at_period_end' => true]);
            $msg = 'Your subscription will not renew at the end of the billing period. All open deals, trade records, and documents remain intact.';
        }

        \App\Models\SubscriptionEvent::create([
            'subscription_id' => $sub->id,
            'event_type' => 'canceled',
            'actor_type' => 'user',
            'actor_id' => $user->id,
            'metadata' => ['cancel_at_period_end' => $sub->cancel_at_period_end, 'immediate' => $immediate],
        ]);

        \App\Models\AuditLog::log('subscription.canceled', $sub, [], [
            'organization_id' => $org->id,
            'user_id' => $user->id,
            'immediate' => $immediate,
        ], $org->id, $user->id);

        return redirect()->route('marketplace.dashboard')->with('success', $msg);
    }
}
