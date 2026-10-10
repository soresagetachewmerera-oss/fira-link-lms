<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use App\Models\Course;
use App\Models\User;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index()
    {
        $query = Bundle::with(['vendor', 'courses', 'bundleEnrollments']);
        
        if (request()->has('search') && request()->search) {
            $search = request()->search;
            $query->where('title', 'like', "%{$search}%");
        }

        if (request()->has('status') && request()->status) {
            $query->where('status', request()->status);
        }

        $bundles = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $pendingCount = Bundle::where('approval_status', 'pending')->count();

        return view('admin.bundles.index', compact('bundles', 'pendingCount'));
    }

    public function create()
    {
        $courses = Course::where('status', 'published')->get();
        return view('admin.bundles.create', compact('courses'));
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
            'approval_status' => 'approved', // Admin created ones are auto-approved
            'featured_image' => $imagePath,
        ]);

        $bundle->courses()->attach($request->courses);

        ToastMagic::success('Bundle created successfully.');
        return redirect()->route('admin.bundles.index');
    }

    public function edit(Bundle $bundle)
    {
        $courses = Course::where('status', 'published')->get();
        return view('admin.bundles.edit', compact('bundle', 'courses'));
    }

    public function update(Request $request, Bundle $bundle)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'courses' => 'required|array|min:1',
            'courses.*' => 'exists:courses,id',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

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
        return redirect()->route('admin.bundles.index');
    }

    public function approve(Bundle $bundle)
    {
        $bundle->update(['approval_status' => 'approved']);
        ToastMagic::success("Bundle '{$bundle->title}' approved successfully.");
        return back();
    }

    public function reject(Bundle $bundle)
    {
        $bundle->update(['approval_status' => 'rejected']);
        ToastMagic::info("Bundle '{$bundle->title}' rejected.");
        return back();
    }

    public function destroy(Bundle $bundle)
    {
        $bundle->delete();
        ToastMagic::success('Bundle deleted successfully.');
        return redirect()->route('admin.bundles.index');
    }
}
