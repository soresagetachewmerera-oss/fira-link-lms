<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Policies\PayoutAccountPolicy;
use App\Services\Trade\PayoutAccountService;
use App\Services\Verification\VerificationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * The seller's payout bank account (slice 7). Owner and managers see it masked; only the owner
 * adds or changes it (fresh sign-in required). A reviewer confirms it before buyers are told it matches.
 */
class PayoutAccountController extends Controller
{
    public function __construct(private PayoutAccountService $payouts)
    {
    }

    /** GET /payout-account */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization();
        if (!PayoutAccountPolicy::canSee($user, $org)) {
            return response()->json(['success' => false, 'code' => 'NOT_ALLOWED', 'message' => __('trade.errors.not_allowed')], 403);
        }
        if (!in_array($org?->roleKey(), \App\Support\BusinessRole::SELLER_ROLES, true)) {
            return response()->json(['success' => false, 'code' => 'SELLERS_ONLY', 'message' => __('trade.errors.sellers_only')], 403);
        }

        return response()->json(['success' => true, 'data' => [
            'account' => PayoutAccountService::ownerJson(PayoutAccountService::current($org)),
            'can_change' => PayoutAccountPolicy::isOwner($user, $org),
            'pause_hours' => PayoutAccountService::PAUSE_HOURS,
        ]]);
    }

    /** POST /payout-account {bank_name, account_name, account_number} */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $org = $user->currentOrganization();
        if (!PayoutAccountPolicy::isOwner($user, $org)) {
            return response()->json(['success' => false, 'code' => 'OWNER_ONLY', 'message' => __('trade.errors.payout_owner_only')], 403);
        }
        if (!in_array($org->roleKey(), \App\Support\BusinessRole::SELLER_ROLES, true)) {
            return response()->json(['success' => false, 'code' => 'SELLERS_ONLY', 'message' => __('trade.errors.sellers_only')], 403);
        }
        $v = Validator::make($request->all(), [
            'bank_name' => 'required|string|max:120',
            'account_name' => 'required|string|max:200',
            'account_number' => 'required|string|max:40',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'code' => 'VALIDATION', 'message' => 'Validation error', 'errors' => $v->errors()], 422);
        }
        try {
            $a = $this->payouts->submit($org, $user, $v->validated());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true, 'data' => ['account' => PayoutAccountService::ownerJson($a)]], 201);
    }
}
