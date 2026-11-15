<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessBlock;
use App\Policies\MarketplaceRfqPolicy;
use App\Services\Trade\TrustReportService;
use App\Services\Verification\VerificationException;
use App\Support\BusinessSummary;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Blocks between businesses (slice 7): owner or manager of the active business. */
class BusinessBlockController extends Controller
{
    public function __construct(private TrustReportService $reports)
    {
    }

    private function org(Request $request)
    {
        $org = $request->user()->currentOrganization();

        return $org && MarketplaceRfqPolicy::manages($request->user(), $org->id) ? $org : null;
    }

    private function denied(): JsonResponse
    {
        return response()->json(['success' => false, 'code' => 'NOT_ALLOWED', 'message' => __('trade.errors.not_allowed')], 403);
    }

    public function index(Request $request): JsonResponse
    {
        if (!$org = $this->org($request)) {
            return $this->denied();
        }
        $blocks = BusinessBlock::with('blockedOrganization')->where('organization_id', $org->id)->latest()->get();

        return response()->json(['success' => true, 'data' => $blocks->map(fn ($b) => [
            'business' => BusinessSummary::of($b->blockedOrganization),
            'blocked_at' => $b->created_at?->toIso8601String(),
        ])->values()]);
    }

    public function store(Request $request): JsonResponse
    {
        if (!$org = $this->org($request)) {
            return $this->denied();
        }
        $request->validate(['organization_id' => 'required|integer']);
        try {
            $this->reports->block($org, $request->integer('organization_id'), $request->user());
        } catch (VerificationException $e) {
            return $e->toResponse();
        }

        return response()->json(['success' => true], 201);
    }

    public function destroy(Request $request, int $organizationId): JsonResponse
    {
        if (!$org = $this->org($request)) {
            return $this->denied();
        }
        $this->reports->unblock($org, $organizationId, $request->user());

        return response()->json(['success' => true]);
    }
}
