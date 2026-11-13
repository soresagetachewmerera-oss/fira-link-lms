<?php

namespace App\Http\Controllers\Api\Instructor;

use App\Http\Controllers\Controller;
use App\Models\Bundle;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public function index(Request $request)
    {
        $bundles = $request->user()->id; // Actually, instructor bundles have vendor_id = user_id
        
        $bundles = Bundle::where('vendor_id', $request->user()->id)
            ->withCount('courses')
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $bundles
        ]);
    }

    public function show(Request $request, $id)
    {
        $bundle = Bundle::where('vendor_id', $request->user()->id)
            ->with(['courses:id,title,featured_image,regular_price,sale_price'])
            ->withCount('courses')
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bundle
        ]);
    }
}
