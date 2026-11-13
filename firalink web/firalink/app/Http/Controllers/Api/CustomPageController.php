<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use Illuminate\Http\Request;

class CustomPageController extends Controller
{
    /**
     * Get list of all custom pages.
     */
    public function index()
    {
        $pages = CustomPage::select('id', 'title', 'slug')->get();
        return response()->json($pages);
    }

    /**
     * Get details of a specific custom page.
     */
    public function show($slug)
    {
        $page = CustomPage::where('slug', $slug)->firstOrFail();

        return response()->json([
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'description' => $page->description,
        ]);
    }
}
