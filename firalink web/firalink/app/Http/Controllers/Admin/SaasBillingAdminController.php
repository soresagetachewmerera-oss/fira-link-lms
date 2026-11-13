<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Feature;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\OrganizationEntitlementOverride;
use App\Models\Plan;
use App\Models\PlanEntitlement;
use App\Models\PlanPrice;
use App\Models\Subscription;
use App\Models\SubscriptionEvent;
use App\Services\EntitlementService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaasBillingAdminController extends Controller
{
    protected EntitlementService $entitlementService;

    public function __construct(EntitlementService $entitlementService)
    {
        $this->entitlementService = $entitlementService;
    }

    /**
     * Plans and Entitlement Matrix Editor
     */
    public function plans()
    {
        $plans = Plan::with(['prices', 'entitlements'])->orderBy('sort_order')->get();
        $features = Feature::orderBy('id')->get();

        return view('admin.saas.plans', compact('plans', 'features'));
    }

    /**
     * Update Plan metadata
     */
    public function updatePlan(Request $request, int $id)
    {
        $plan = Plan::findOrFail($id);
        $before = $plan->toArray();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'is_public' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'trial_days' => 'required|integer|min:0',
        ]);

        $validated['is_public'] = $request->boolean('is_public');
        $validated['is_active'] = $request->boolean('is_active');

        $plan->update($validated);

        AuditLog::log('plan_metadata_updated', $plan, $before, $plan->fresh()->toArray());

        ToastMagic::success("Plan '{$plan->name}' updated successfully.");
        return redirect()->back();
    }

    /**
     * Update Plan Price
     */
    public function updatePrice(Request $request, int $id)
    {
        $price = PlanPrice::findOrFail($id);
        $before = $price->toArray();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $price->update([
            'amount' => $validated['amount'],
            'is_active' => $request->boolean('is_active', true),
            'effective_from' => now(),
        ]);

        AuditLog::log('plan_price_updated', $price->plan, $before, $price->fresh()->toArray());

        ToastMagic::success("Price for {$price->plan->name} ({$price->currency} {$price->billing_period}) updated.");
        return redirect()->back();
    }

    /**
     * Update Entitlement Matrix for a Plan
     */
    public function updateEntitlements(Request $request, int $id)
    {
        $plan = Plan::findOrFail($id);
        $matrix = $request->input('entitlements', []);

        // HARD CONSTRAINT #4: TRUST IS NEVER FOR SALE.
        // Guard check: Reject any attempt to set verification or ranking keys as plan entitlements
        $forbiddenKeys = ['verification_tier', 'verification_status', 'search_ranking_boost', 'remove_reports', 'evidence_level'];
        foreach (array_keys($matrix) as $k) {
            if (in_array(strtolower($k), $forbiddenKeys)) {
                AuditLog::log('security_alert_trust_for_sale_attempt', $plan, [], ['attempted_key' => $k]);
                ToastMagic::error("Security policy violation: '{$k}' cannot be tied to a plan. Trust is never for sale.");
                return redirect()->back();
            }
        }

        DB::transaction(function () use ($plan, $matrix) {
            foreach ($matrix as $featureKey => $val) {
                if (trim($val) === '') {
                    continue;
                }

                PlanEntitlement::updateOrCreate(
                    ['plan_id' => $plan->id, 'feature_key' => $featureKey],
                    ['value' => trim((string) $val)]
                );
            }
        });

        // Invalidate cached entitlements across organizations
        $orgs = Organization::whereHas('subscriptions', fn($q) => $q->where('plan_id', $plan->id))->get();
        foreach ($orgs as $org) {
            $this->entitlementService->invalidate($org);
        }

        AuditLog::log('entitlement_matrix_updated', $plan, [], $matrix);

        ToastMagic::success("Entitlement matrix for '{$plan->name}' saved and live across all organizations.");
        return redirect()->back();
    }

    /**
     * Feature Registry Screen
     */
    public function features()
    {
        $features = Feature::withCount('planEntitlements')->orderBy('id')->get();
        return view('admin.saas.features', compact('features'));
    }

    /**
     * Store new feature in registry
     */
    public function storeFeature(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string|max:50|unique:features,key|regex:/^[a-z0-9_]+$/',
            'label' => 'required|string|max:100',
            'type' => 'required|in:limit,boolean,tier',
            'unit' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:500',
            'is_available' => 'nullable|boolean',
        ]);

        // HARD CONSTRAINT #4: TRUST IS NEVER FOR SALE
        $forbiddenKeys = ['verification_tier', 'verification_status', 'search_ranking_boost', 'remove_reports', 'evidence_level'];
        if (in_array(strtolower($validated['key']), $forbiddenKeys)) {
            ToastMagic::error("Prohibited feature key: Trust metrics cannot be configured as gateable entitlements.");
            return redirect()->back();
        }

        $validated['is_available'] = $request->boolean('is_available', true);

        $feature = Feature::create($validated);

        AuditLog::log('feature_registered', null, [], $feature->toArray());

        ToastMagic::success("Feature '{$feature->label}' registered in capability catalog.");
        return redirect()->back();
    }

    /**
     * Organization Entitlement Overrides Screen
     */
    public function overrides()
    {
        $overrides = OrganizationEntitlementOverride::with(['organization', 'creator'])->latest('id')->paginate(20);
        $organizations = Organization::orderBy('name')->get();
        $features = Feature::orderBy('label')->get();

        return view('admin.saas.overrides', compact('overrides', 'organizations', 'features'));
    }

    /**
     * Store a per-organization entitlement override
     */
    public function storeOverride(Request $request)
    {
        $validated = $request->validate([
            'organization_id' => 'required|exists:organizations,id',
            'feature_key' => 'required|exists:features,key',
            'value' => 'required|string|max:100',
            'reason' => 'required|string|min:5|max:255',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $override = OrganizationEntitlementOverride::create([
            'organization_id' => $validated['organization_id'],
            'feature_key' => $validated['feature_key'],
            'value' => $validated['value'],
            'reason' => $validated['reason'],
            'expires_at' => $validated['expires_at'],
            'created_by' => auth()->id(),
        ]);

        $org = Organization::find($validated['organization_id']);
        if ($org) {
            $this->entitlementService->invalidate($org);
            AuditLog::log('override_granted', $org, [], $override->toArray(), $org->id);
        }

        ToastMagic::success("Entitlement override granted for '{$org?->name}'.");
        return redirect()->back();
    }

    /**
     * Delete an entitlement override
     */
    public function destroyOverride(int $id)
    {
        $override = OrganizationEntitlementOverride::findOrFail($id);
        $org = $override->organization;

        AuditLog::log('override_revoked', $org, $override->toArray(), [], $org->id);

        $override->delete();

        if ($org) {
            $this->entitlementService->invalidate($org);
        }

        ToastMagic::success("Override revoked.");
        return redirect()->back();
    }

    /**
     * Subscriptions Management
     */
    public function subscriptions(Request $request)
    {
        $query = Subscription::with(['organization.owner', 'plan']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($planId = $request->input('plan_id')) {
            $query->where('plan_id', $planId);
        }

        $subscriptions = $query->latest('updated_at')->paginate(20);
        $plans = Plan::all();

        return view('admin.saas.subscriptions', compact('subscriptions', 'plans'));
    }

    /**
     * Manual Plan Change by Platform Admin
     */
    public function changeSubscriptionPlan(Request $request, int $id)
    {
        $sub = Subscription::with('organization')->findOrFail($id);
        $beforePlanId = $sub->plan_id;

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'status' => 'required|in:active,grace,past_due,canceled,expired',
            'reason' => 'required|string|max:255',
        ]);

        $newPlan = Plan::findOrFail($validated['plan_id']);

        $sub->update([
            'plan_id' => $newPlan->id,
            'status' => $validated['status'],
        ]);

        SubscriptionEvent::create([
            'subscription_id' => $sub->id,
            'organization_id' => $sub->organization_id,
            'event_type' => SubscriptionEvent::EVENT_ADMIN_CHANGED,
            'from_plan_id' => $beforePlanId,
            'to_plan_id' => $newPlan->id,
            'metadata' => ['reason' => $validated['reason'], 'admin_id' => auth()->id()],
            'created_by' => auth()->id(),
            'created_at' => now(),
        ]);

        AuditLog::log('admin_subscription_changed', $sub, ['plan_id' => $beforePlanId], ['plan_id' => $newPlan->id, 'reason' => $validated['reason']], $sub->organization_id);

        $this->entitlementService->invalidate($sub->organization);

        ToastMagic::success("Subscription for '{$sub->organization->name}' updated to {$newPlan->name}.");
        return redirect()->back();
    }

    /**
     * Invoices List
     */
    public function invoices(Request $request)
    {
        $query = Invoice::with(['organization', 'subscription.plan']);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->latest('issued_at')->paginate(25);

        return view('admin.saas.invoices', compact('invoices'));
    }

    /**
     * Audit Logs Explorer
     */
    public function auditLogs(Request $request)
    {
        $query = AuditLog::with(['user', 'organization']);

        if ($action = $request->input('action')) {
            $query->where('action', 'like', "%{$action}%");
        }

        $logs = $query->latest('created_at')->paginate(30);

        return view('admin.saas.audit-logs', compact('logs'));
    }
}
