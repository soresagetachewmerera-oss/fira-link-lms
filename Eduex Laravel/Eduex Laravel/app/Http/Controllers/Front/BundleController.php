<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BundleController extends Controller
{
    /**
     * Display all active, approved bundles.
     */
    public function index(Request $request)
    {
        $query = Bundle::where('status', 'active')
            ->where('approval_status', 'approved')
            ->with(['vendor:id,name,profile_photo', 'courses.instructor']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $bundles = $query->latest()->paginate(12);

        return view('bundles.index', [
            'pageTitle' => 'Course Bundles',
            'metaDescription' => 'Browse our exclusive course bundles and unlock complete learning paths at a discounted price.',
            'bundles' => $bundles,
        ]);
    }

    /**
     * Display a single bundle detail page.
     */
    public function show($id)
    {
        $bundle = Bundle::where('status', 'active')
            ->where('approval_status', 'approved')
            ->with(['vendor:id,name,profile_photo', 'courses.instructor', 'courses.reviews'])
            ->findOrFail($id);

        // Check if the logged-in user already owns all courses in this bundle
        $alreadyOwnsAll = false;
        $ownedCourseIds = collect();

        if (Auth::check()) {
            $ownedCourseIds = Enrollment::where('user_id', Auth::id())
                ->whereIn('course_id', $bundle->courses->pluck('id'))
                ->whereIn('status', ['approved', 'completed'])
                ->pluck('course_id');

            $alreadyOwnsAll = $ownedCourseIds->count() === $bundle->courses->count()
                && $bundle->courses->count() > 0;
        }

        // Compute aggregate stats for the bundle
        $totalLessons = $bundle->courses->sum(function ($course) {
            return $course->topics_count ?? 0;
        });

        $totalStudents = $bundle->courses->sum(function ($course) {
            return $course->students_count ?? 0;
        });

        return view('bundles.show', [
            'pageTitle' => $bundle->title . ' — Bundle',
            'metaDescription' => \Illuminate\Support\Str::limit(strip_tags($bundle->description ?? $bundle->title), 160),
            'bundle' => $bundle,
            'alreadyOwnsAll' => $alreadyOwnsAll,
            'ownedCourseIds' => $ownedCourseIds,
            'totalStudents' => $totalStudents,
        ]);
    }
}
