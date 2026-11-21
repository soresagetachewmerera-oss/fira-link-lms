<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class LegalController extends Controller
{
    /**
     * Privacy Policy page
     */
    public function privacy(): View
    {
        $pageTitle = 'Privacy Policy';
        $metaDescription = 'Review the official privacy policy and data governance practices of Fira Link Platform and Fira Link Business Solution.';
        $breadcrumbs = [
            'Privacy Policy' => route('privacy'),
        ];

        return view('legal.privacy', compact('pageTitle', 'metaDescription', 'breadcrumbs'));
    }

    /**
     * Terms of Service page
     */
    public function terms(): View
    {
        $pageTitle = 'Terms of Service';
        $metaDescription = 'Terms of service, course enrollment rules, intellectual property, and refund policies for Fira Link Platform.';
        $breadcrumbs = [
            'Terms of Service' => route('terms'),
        ];

        return view('legal.terms', compact('pageTitle', 'metaDescription', 'breadcrumbs'));
    }
}
