<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use Illuminate\Contracts\View\View;

/**
 * Page Controller
 *
 * This controller handles the page functionality.
 *
 * @package App\Http\Controllers\Front
 */
class PageController extends Controller
{
    public function __invoke(CustomPage $customPage): View
    {
        $seo = frontend_meta($customPage->slug) ?? [];

        return view('pages.custom', [
            'page' => $customPage,
            'pageTitle' => $customPage->title,
            'metaDescription' => $customPage->description ?: ($seo['description'] ?? null),
            'seoMeta' => $seo,
        ]);
    }
}
