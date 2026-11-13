<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\CertificateVerificationService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateVerificationController extends Controller
{
    public function __construct(
        protected CertificateVerificationService $verificationService
    ) {}

    /**
     * Display the certificate verification page or perform verification.
     *
     * @param Request $request
     * @return View
     */
    public function verify(Request $request): View
    {
        $code = $request->input('certificate_number') ?? $request->input('code');
        $result = null;

        if (! empty($code)) {
            $result = $this->verificationService->verify($code);
        }

        return view('pages.verify-certificate', [
            'code' => $code,
            'result' => $result,
        ]);
    }
}
