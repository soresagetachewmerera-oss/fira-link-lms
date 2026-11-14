<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Verification\PublicSellerProfile;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/sellers/{firaId} — the public seller check, by Fira ID only (rate-limited per IP).
 * There is no list or search endpoint. Unknown, malformed and never-verified IDs all get the
 * same "not found".
 */
class PublicSellerController extends Controller
{
    public function show(string $firaId, PublicSellerProfile $profiles): JsonResponse
    {
        $org = $profiles->find($firaId);
        if (!$org) {
            return response()->json(['success' => false, 'code' => 'NOT_FOUND', 'message' => __('verification.public.not_found')], 404);
        }

        return response()->json(['success' => true, 'data' => $profiles->build($org)])
            ->header('Cache-Control', 'no-store')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
