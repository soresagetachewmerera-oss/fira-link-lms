<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Verification\FiraId;
use App\Services\Verification\PublicSellerProfile;
use Illuminate\Http\Request;

/**
 * Public seller page for sharing (Telegram and other link previews): /verify/{firaId}.
 * Fira ID only, rate-limited, noindex. No list or search.
 */
class PublicSellerWebController extends Controller
{
    private function headers($response)
    {
        return $response->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'no-store');
    }

    public function index(Request $request)
    {
        if ($request->filled('id')) {
            $id = FiraId::normalize((string) $request->query('id'));

            return $id ? redirect()->route('verify.show', $id) : $this->headers(response()->view('verify.show', ['profile' => null, 'query' => $request->query('id')], 404));
        }

        return $this->headers(response()->view('verify.show', ['profile' => null, 'query' => null]));
    }

    public function show(string $firaId, PublicSellerProfile $profiles)
    {
        $org = $profiles->find($firaId);
        if (!$org) {
            return $this->headers(response()->view('verify.show', ['profile' => null, 'query' => $firaId], 404));
        }

        return $this->headers(response()->view('verify.show', ['profile' => $profiles->build($org), 'query' => null]));
    }

    public function qr(string $firaId, PublicSellerProfile $profiles)
    {
        $org = $profiles->find($firaId);
        abort_unless($org, 404);

        return $this->headers(response(PublicSellerProfile::qrSvg(route('verify.show', $org->fira_id)), 200, ['Content-Type' => 'image/svg+xml']));
    }
}
