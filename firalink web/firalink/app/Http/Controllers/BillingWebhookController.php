<?php

namespace App\Http\Controllers;

use App\Services\Billing\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingWebhookController extends Controller
{
    protected BillingService $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * POST /billing/webhook/{provider}
     * Process incoming provider webhooks (Chapa, Telebirr, CBE Birr).
     */
    public function handle(Request $request, string $provider): JsonResponse
    {
        $result = $this->billingService->handleWebhook($provider, $request);

        $status = $result['http_status'] ?? ($result['success'] ? 200 : 400);
        unset($result['http_status']);

        return response()->json($result, $status);
    }
}
