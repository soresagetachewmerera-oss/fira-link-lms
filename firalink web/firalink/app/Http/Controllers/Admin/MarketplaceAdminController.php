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
     * Approve and verify exporter profile.
     */
    public function verifySeller(Request $request, int $id)
    {
        $seller = MarketplaceSellerProfile::findOrFail($id);
        $before = $seller->toArray();
        $seller->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_notes' => $request->input('notes', 'Verified by Admin.'),
        ]);

        if ($seller->organization) {
            $seller->organization->syncVerifiedRoles();
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'organization_id' => $seller->organization_id,
            'action' => 'seller_profile_verified',
            'auditable_type' => MarketplaceSellerProfile::class,
            'auditable_id' => $seller->id,
            'before_state' => $before,
            'after_state' => $seller->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Exporter '{$seller->company_name}' verified successfully.");
    }

    /**
     * Reject exporter application.
     */
    public function rejectSeller(Request $request, int $id)
    {
        $seller = MarketplaceSellerProfile::findOrFail($id);
        $before = $seller->toArray();
        $seller->update([
            'verification_status' => 'rejected',
            'verification_notes' => $request->input('notes', 'Application rejected. Please submit valid ECTA credentials.'),
        ]);

        if ($seller->organization) {
            $seller->organization->syncVerifiedRoles();
        }

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'organization_id' => $seller->organization_id,
            'action' => 'seller_profile_rejected',
            'auditable_type' => MarketplaceSellerProfile::class,
            'auditable_id' => $seller->id,
            'before_state' => $before,
            'after_state' => $seller->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Exporter application rejected.");
    }

    /**
     * Approve Organization Role Verification Dossier.
     */
    public function verifyOrganizationRole(Request $request, int $id)
    {
        $verification = OrganizationVerification::with('organization')->findOrFail($id);
        $before = $verification->toArray();

        $verification->update([
            'status' => OrganizationVerification::STATUS_APPROVED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'admin_notes' => $request->input('notes', 'Dossier audited and approved by Compliance Team.'),
            'rejection_reason' => null,
            'license_expires_at' => $request->input('license_expires_at', $verification->license_expires_at ?? now()->addYear()),
        ]);

        $verification->organization->syncVerifiedRoles();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'organization_id' => $verification->organization_id,
            'action' => 'organization_role_verified',
            'auditable_type' => OrganizationVerification::class,
            'auditable_id' => $verification->id,
            'before_state' => $before,
            'after_state' => $verification->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Role '{$verification->target_role}' approved for {$verification->organization->name}.");
    }

    /**
     * Reject Organization Role Verification Dossier.
     */
    public function rejectOrganizationRole(Request $request, int $id)
    {
        $request->validate(['rejection_reason' => 'required|string|min:5|max:1000']);
        $verification = OrganizationVerification::with('organization')->findOrFail($id);
        $before = $verification->toArray();

        $verification->update([
            'status' => OrganizationVerification::STATUS_REJECTED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        $verification->organization->syncVerifiedRoles();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'organization_id' => $verification->organization_id,
            'action' => 'organization_role_rejected',
            'auditable_type' => OrganizationVerification::class,
            'auditable_id' => $verification->id,
            'before_state' => $before,
            'after_state' => $verification->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Role '{$verification->target_role}' rejected with documented rationale.");
    }

    /**
     * Suspend Organization Role Trading Privileges.
     */
    public function suspendOrganizationRole(Request $request, int $id)
    {
        $request->validate(['reason' => 'required|string|min:5|max:1000']);
        $verification = OrganizationVerification::with('organization')->findOrFail($id);
        $before = $verification->toArray();

        $verification->update([
            'status' => OrganizationVerification::STATUS_SUSPENDED,
            'reviewed_by' => $request->user()?->id,
            'reviewed_at' => now(),
            'admin_notes' => 'Suspended: ' . $request->input('reason'),
        ]);

        $verification->organization->syncVerifiedRoles();

        AuditLog::create([
            'user_id' => $request->user()?->id,
            'organization_id' => $verification->organization_id,
            'action' => 'organization_role_suspended',
            'auditable_type' => OrganizationVerification::class,
            'auditable_id' => $verification->id,
            'before_state' => $before,
            'after_state' => $verification->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()->with('success', "Trading privileges for role '{$verification->target_role}' suspended.");
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

