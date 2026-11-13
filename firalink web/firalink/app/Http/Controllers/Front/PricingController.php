<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;

class PricingController extends Controller
{
    /**
     * Display the transparent tuition, pricing and enrollment options page.
     */
    public function index(): View
    {
        $courses = Course::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->get();

        $pageTitle = 'Pricing';
        $metaDescription = 'Transparent course tuition, professional trade certification fees, and free business tools pricing at Fira Link Academy in Ethiopia.';

        $breadcrumbs = [
            'Pricing' => route('pricing'),
        ];

        return view('pricing.index', compact('courses', 'pageTitle', 'metaDescription', 'breadcrumbs'));
    }
}
