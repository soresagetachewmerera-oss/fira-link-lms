<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Contracts\View\View;

class ProgramController extends Controller
{
    /**
     * Display the Fira Link Platform Flagship Programs page.
     */
    public function index(): View
    {
        $courses = Course::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->latest()
            ->take(6)
            ->get();

        $pageTitle = 'Trade Programs & Specialized Training Tracks';
        $metaDescription = 'Explore professional import-export programs at Fira Link Platform in Ethiopia: Coffee Sourcing & Export, Customs Clearance, Freight Corridors, and Market Acquisition.';

        $breadcrumbs = [
            'Programs' => route('programs'),
        ];

        return view('programs.index', compact('courses', 'pageTitle', 'metaDescription', 'breadcrumbs'));
    }
}
