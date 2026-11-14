<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CoffeeListing;
use App\Models\MarketplaceRfq;
use App\Models\MarketplaceSellerProfile;
use App\Models\Organization;
use App\Models\OrganizationVerification;
use App\Models\UserMarketplaceSubscription;
use App\Services\MarketplaceSubscriptionService;
use App\Services\Verification\VerificationReviewService;
use Illuminate\Http\Request;

class MarketplaceAdminController extends Controller
{
    protected MarketplaceSubscriptionService $subscriptionService;

    public function __construct(MarketplaceSubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * List all seller business profiles and their verification status.
     */
    public function sellers(Request $request)
    {
        $query = MarketplaceSellerProfile::with(['user', 'listings']);

        if ($status = $request->input('status')) {
            $query->where('verification_status', $status);
        }

        $sellers = $query->latest('created_at')->paginate(15);

        return view('admin.marketplace.sellers', compact('sellers'));
    }

    /**
     * Show seller profile details for audit.
     */
    public function showSeller(int $id)
    {
        $seller = MarketplaceSellerProfile::with([
            'user',
            'listings.primaryImage',
            'organization.verifications.documents',
        ])->findOrFail($id);
        return view('admin.marketplace.seller-detail', compact('seller'));
    }

    /**
     * List and moderate coffee listings.
     */
    public function listings(Request $request)
    {
        $query = CoffeeListing::with(['primaryImage', 'seller.sellerProfile']);

        if ($origin = $request->input('origin')) {
            $query->where('origin_region', 'LIKE', "%{$origin}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $listings = $query->latest('created_at')->paginate(15);

        return view('admin.marketplace.listings', compact('listings'));
    }

    /**
     * Toggle listing active / suspended status.
     */
    public function toggleListingStatus(Request $request, int $id)
    {
        $listing = CoffeeListing::findOrFail($id);
        $newStatus = $listing->status === 'active' ? 'archived' : 'active';
        $listing->update(['status' => $newStatus]);

        return redirect()->back()->with('success', "Listing status updated to {$newStatus}.");
    }

    /**
     * Admin review: set a lot's evidence level after checking its evidence.
     * This is the only way evidence_level changes.
     */
    public function reviewEvidenceLevel(Request $request, int $id)
    {
        $validated = $request->validate([
            'evidence_level' => 'required|in:' . implode(',', CoffeeListing::EVIDENCE_LEVELS),
            'notes' => 'required|string|min:5|max:1000',
        ]);

        $listing = CoffeeListing::findOrFail($id);
        $before = $listing->evidence_level;

        $listing->forceFill(['evidence_level' => $validated['evidence_level']])->save();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'organization_id' => $listing->organization_id,
            'action' => 'listing_evidence_level_reviewed',
            'auditable_type' => CoffeeListing::class,
            'auditable_id' => $listing->id,
            'before_state' => ['evidence_level' => $before],
            'after_state' => ['evidence_level' => $listing->evidence_level, 'notes' => $validated['notes']],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Evidence level for '{$listing->title}' set to {$listing->evidence_level}.");
    }

    /**
     * Toggle featured status.
     */
    public function toggleListingFeatured(Request $request, int $id)
    {
        $listing = CoffeeListing::findOrFail($id);
        $listing->update([
            'is_featured' => !$listing->is_featured,
            'featured_until' => !$listing->is_featured ? now()->addMonths(1) : null,
        ]);

        return redirect()->back()->with('success', "Featured status toggled.");
    }

    /**
     * Overview of international buyer tenders (RFQs).
     */
    public function rfqs(Request $request)
    {
        $rfqs = MarketplaceRfq::with(['buyer', 'quotations.seller.sellerProfile'])
            ->latest('created_at')
            ->paginate(15);

        return view('admin.marketplace.rfqs', compact('rfqs'));
    }

    /**
     * Manage seller subscriptions and audit offline bank wire payments.
     */
    public function subscriptions(Request $request)
    {
        $query = UserMarketplaceSubscription::with(['user.sellerProfile', 'plan', 'payment']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $subscriptions = $query->latest('created_at')->paginate(15);

        return view('admin.marketplace.subscriptions', compact('subscriptions'));
    }

    /**
     * Approve offline bank wire subscription payment.
     */
    public function approveOfflineSubscription(int $id)
    {
        $sub = UserMarketplaceSubscription::with(['payment', 'user', 'plan'])->findOrFail($id);

        if ($sub->payment) {
            $sub->payment->update(['status' => 'completed']);
        }

        $this->subscriptionService->activateSubscription($sub);

        return redirect()->back()->with('success', "Subscription #{$sub->id} activated successfully.");
    }
}

