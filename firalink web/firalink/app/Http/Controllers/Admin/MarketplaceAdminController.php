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
        $listing = CoffeeListing::with('organization')->findOrFail($id);
        $oldStatus = $listing->status;
        $newStatus = $oldStatus === 'active' ? 'archived' : 'active';

        // Making a lot active follows the same rules as the seller's Publish: a verified seller business
        // and a complete lot. Admins remove lots; they do not publish them past those rules.
        if ($newStatus === 'active') {
            $required = ['title', 'origin_region', 'crop_year', 'processing_method', 'grade', 'total_quantity_bags', 'minimum_order_bags', 'indicative_price_usd_per_lb'];
            $complete = collect($required)->every(fn ($f) => filled($listing->{$f}));
            if (!$complete || !VerificationReviewService::isApprovedSeller($listing->organization)) {
                return redirect()->back()->with('error', __('lots.errors.admin_activate_blocked'));
            }
        }

        $listing->forceFill(['status' => $newStatus, 'published_at' => $newStatus === 'active' ? ($listing->published_at ?? now()) : $listing->published_at])->save();
        AuditLog::create([
            'user_id' => $request->user()->id,
            'organization_id' => $listing->organization_id,
            'action' => $newStatus === 'active' ? 'lot_reactivated_by_admin' : 'lot_archived_by_admin',
            'auditable_type' => CoffeeListing::class,
            'auditable_id' => $listing->id,
            'before_state' => ['status' => $oldStatus],
            'after_state' => ['status' => $newStatus],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Listing status updated to {$newStatus}.");
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

