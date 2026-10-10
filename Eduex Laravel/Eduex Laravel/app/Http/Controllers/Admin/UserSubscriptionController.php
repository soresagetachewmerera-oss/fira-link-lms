<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\SubscriptionService;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * User Subscription Controller (Admin)
 *
 * Handles viewing student subscriptions, manual assignment, and offline subscription approvals.
 *
 * @package App\Http\Controllers\Admin
 */
class UserSubscriptionController extends Controller
{
    public function __construct(private SubscriptionService $subscriptionService)
    {
    }

    /**
     * Display a listing of user subscriptions.
     */
    public function index(Request $request): View
    {
        $query = UserSubscription::with(['user', 'plan', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $subscriptions = $query->latest('id')->paginate(15)->withQueryString();
        $plans = SubscriptionPlan::active()->orderBy('name')->get();
        $students = User::where('role', 'student')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.subscriptions.index', compact('subscriptions', 'plans', 'students'));
    }

    /**
     * Manually assign subscription plan to a student.
     */
    public function assign(Request $request): RedirectResponse
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'subscription_plan_id' => 'required|exists:subscription_plans,id',
            'duration_days' => 'nullable|integer|min:1',
        ]);

        $user = User::findOrFail($request->user_id);
        $plan = SubscriptionPlan::findOrFail($request->subscription_plan_id);

        $this->subscriptionService->manualAssign($user, $plan, $request->duration_days);

        ToastMagic::success('Subscription assigned to student successfully.');

        return redirect()->back();
    }

    /**
     * Approve offline subscription payment.
     */
    public function approveOffline(int $paymentId): RedirectResponse
    {
        $payment = Payment::with(['userSubscription', 'subscriptionPlan'])->findOrFail($paymentId);

        $this->subscriptionService->approveOfflineSubscription($payment);

        ToastMagic::success('Offline subscription payment approved successfully.');

        return redirect()->back();
    }
}
