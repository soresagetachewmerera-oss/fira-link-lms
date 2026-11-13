<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Contracts\View\View;

/**
 * Testimonial Controller
 *
 * This controller handles the testimonial functionality.
 *
 * @package App\Http\Controllers\Front
 */
class TestimonialController extends Controller
{
    public function index(): View
    {
        $baseQuery = Review::query()
            ->with(['user:id,name,profile_photo', 'course:id,title'])
            ->where('is_approved', true)
            ->latest();

        $averageRating = round((float) Review::where('is_approved', true)->avg('rating'), 1);

        $reviews = $baseQuery->paginate(12);

        if ($averageRating <= 0) {
            $averageRating = null;
        }

        $meta = frontend_meta('testimonials', null, []);

        return view('testimonial.index', [
            'pageTitle' => $meta['title'] ?? 'Student Testimonials',
            'metaDescription' => $meta['description'] ?? null,
            'reviews' => $reviews,
            'averageRating' => $averageRating,
        ]);
    }
}
