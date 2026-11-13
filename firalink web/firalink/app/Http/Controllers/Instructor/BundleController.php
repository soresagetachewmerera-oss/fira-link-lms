<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Course;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index()
    {
        $query = Bundle::where('vendor_id', auth()->id())
                    ->with(['courses', 'bundleEnrollments']);
        
        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where('title', 'like', "%{$search}%");
        }

        if (request()->has('status') && request()->status) {
            $query->where('status', request()->status);
        }

        $bundles = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        return view('instructor.bundles.index', compact('bundles'));
    }

    public function create()
    {
        $courses = Course::where('instructor_id', auth()->id())->where('status', 'published')->get();
        return view('instructor.bundles.create', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'courses' => 'required|array|min:2',
            'courses.*' => 'exists:courses,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        // Ensure instructor owns these courses
        $ownedCourses = Course::whereIn('id', $request->courses)->where('instructor_id', auth()->id())->count();
        if ($ownedCourses !== count($request->courses)) {
            ToastMagic::error('You can only bundle your own courses.');
            return back();
        }

        $imagePath = null;
        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('bundles', 'public');
        }

        $bundle = Bundle::create([
            'vendor_id' => auth()->id(),
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'status' => $request->status,
            'approval_status' => 'pending', // Requires admin approval
            'featured_image' => $imagePath,
        ]);

        $bundle->courses()->attach($request->courses);

        ToastMagic::success('Bundle created successfully. Waiting for admin approval.');
        return redirect()->route('instructor.bundles.index');
    }

    public function edit(Bundle $bundle)
    {
        if ($bundle->vendor_id !== auth()->id()) {
            abort(403);
        }

        $courses = Course::where('instructor_id', auth()->id())->where('status', 'published')->get();
        return view('instructor.bundles.edit', compact('bundle', 'courses'));
    }

    public function update(Request $request, Bundle $bundle)
    {
        if ($bundle->vendor_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'courses' => 'required|array|min:2',
            'courses.*' => 'exists:courses,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $ownedCourses = Course::whereIn('id', $request->courses)->where('instructor_id', auth()->id())->count();
        if ($ownedCourses !== count($request->courses)) {
            ToastMagic::error('You can only bundle your own courses.');
            return back();
        }

        if ($request->hasFile('featured_image')) {
            $imagePath = $request->file('featured_image')->store('bundles', 'public');
            $bundle->featured_image = $imagePath;
        }

        $bundle->update([
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'status' => $request->status,
        ]);

        $bundle->courses()->sync($request->courses);

        ToastMagic::success('Bundle updated successfully.');
        return redirect()->route('instructor.bundles.index');
    }

    public function destroy(Bundle $bundle)
    {
        if ($bundle->vendor_id !== auth()->id()) {
            abort(403);
        }

        $bundle->delete();
        ToastMagic::success('Bundle deleted successfully.');
        return redirect()->route('instructor.bundles.index');
    }
}
