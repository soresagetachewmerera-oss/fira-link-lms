<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionPlanRequest;
use App\Http\Requests\Admin\UpdateSubscriptionPlanRequest;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionPlanService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Subscription Plan Controller (Admin)
 *
 * Handles creation, editing, status toggles, and deletion of subscription plans.
 *
 * @package App\Http\Controllers\Admin
 */
class SubscriptionPlanController extends Controller
{
    public function __construct(private SubscriptionPlanService $planService)
    {
    }

    /**
     * Display a listing of the subscription plans.
     */
    public function index(Request $request): View
    {
        $plans = $this->planService->getAllPlans($request->all());

        return view('admin.subscription-plans.index', compact('plans'));
    }

    /**
     * Show the form for creating a new subscription plan.
     */
    public function create(): View
    {
        $courses = Course::withCount('liveClasses')->orderBy('title')->get(['id', 'title']);
        $bundles = Bundle::orderBy('title')->get(['id', 'title']);

        return view('admin.subscription-plans.create', compact('courses', 'bundles'));
    }

    /**
     * Store a newly created subscription plan in storage.
     */
    public function store(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        $this->planService->createPlan($request->validated());

        ToastMagic::success('Subscription package plan created successfully.');

        return redirect()->route('admin.subscription-plans.index');
    }

    /**
     * Show the form for editing the specified subscription plan.
     */
    public function edit(int $id): View
    {
        $plan = $this->planService->findPlan($id);
        $courses = Course::withCount('liveClasses')->orderBy('title')->get(['id', 'title']);
        $bundles = Bundle::orderBy('title')->get(['id', 'title']);

        $selectedCourseIds = $plan->courses->pluck('id')->toArray();
        $selectedBundleIds = $plan->bundles->pluck('id')->toArray();

        return view('admin.subscription-plans.edit', compact('plan', 'courses', 'bundles', 'selectedCourseIds', 'selectedBundleIds'));
    }

    /**
     * Update the specified subscription plan in storage.
     */
    public function update(UpdateSubscriptionPlanRequest $request, int $id): RedirectResponse
    {
        $plan = $this->planService->findPlan($id);
        $this->planService->updatePlan($plan, $request->validated());

        ToastMagic::success('Subscription package plan updated successfully.');

        return redirect()->route('admin.subscription-plans.index');
    }

    /**
     * Toggle active/inactive status of a subscription plan.
     */
    public function toggleStatus(int $id): RedirectResponse
    {
        $plan = $this->planService->findPlan($id);
        $this->planService->toggleStatus($plan);

        ToastMagic::success('Subscription plan status updated successfully.');

        return redirect()->back();
    }

    /**
     * Remove the specified subscription plan from storage.
     */
    public function destroy(int $id): RedirectResponse
    {
        $plan = $this->planService->findPlan($id);
        $this->planService->deletePlan($plan);

        ToastMagic::success('Subscription plan deleted successfully.');

        return redirect()->route('admin.subscription-plans.index');
    }
}
