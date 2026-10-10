<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Devrabiul\ToastMagic\Facades\ToastMagic;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $banners = Banner::ordered()->get();
        return view('admin.banners.index', compact('banners'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.banners.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'link' => 'nullable|url',
            'order' => 'integer',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('banners', 'public');
            $data['image_path'] = $path;
        }

        // Fix: Explicitly handle checkbox 'on' value
        $data['is_active'] = $request->has('is_active');

        Banner::create($data);

        ToastMagic::success('Banner created successfully.');
        return redirect()->route('admin.banners.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function edit(Banner $banner)
    {
        return view('admin.banners.edit', compact('banner'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Banner $banner)
    {
        $request->validate([
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'link' => 'nullable|url',
            'order' => 'integer',
        ]);

        $data = $request->except('image');

        if ($request->hasFile('image')) {
            // Delete old image
            if ($banner->image_path) {
                Storage::disk('public')->delete($banner->image_path);
            }

            $path = $request->file('image')->store('banners', 'public');
            $data['image_path'] = $path;
        }

        $data['is_active'] = $request->has('is_active');

        $banner->update($data);

        ToastMagic::success('Banner updated successfully.');
        return redirect()->route('admin.banners.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Banner  $banner
     * @return \Illuminate\Http\Response
     */
    public function destroy(Banner $banner)
    {
        if ($banner->image_path) {
            Storage::disk('public')->delete($banner->image_path);
        }

        $banner->delete();

        ToastMagic::success('Banner deleted successfully.');
        return back();
    }
}
