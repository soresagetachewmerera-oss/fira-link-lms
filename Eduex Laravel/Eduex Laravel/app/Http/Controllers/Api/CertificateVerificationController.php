<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CertificateVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CertificateVerificationController extends Controller
{
    public function __construct(
        protected CertificateVerificationService $verificationService
    ) {}

    /**
     * Verify certificate via POST request.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function verify(Request $request): JsonResponse
    {
        $code = $request->input('certificate_number') ?? $request->input('code');

        if (empty($code)) {
            return response()->json([
                'success' => false,
                'message' => __('frontend.certificate_code_required') ?? 'Certificate code is required.',
            ], 422);
        }

        $result = $this->verificationService->verify($code);

        if (! $result['is_valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Invalid or unverified certificate code.',
                'data' => $result,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }

    /**
     * Verify certificate via GET route parameter.
     *
     * @param Request $request
     * @param string $code
     * @return JsonResponse
     */
    public function show(Request $request, string $code): JsonResponse
    {
        $result = $this->verificationService->verify($code);

        if (! $result['is_valid']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Invalid or unverified certificate code.',
                'data' => $result,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $result,
        ]);
    }
}
