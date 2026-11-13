<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CustomPage;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Custom Page Controller
 *
 * This controller handles the management of custom pages.
 *
 * @package App\Http\Controllers\Admin
 */
class CustomPageController extends Controller
{
    /**
     * Display all custom pages
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        $pages = CustomPage::orderBy('title')->get();

        return view('admin.custom-pages.index', compact('pages'));
    }

    /**
     * Display the edit custom page form
     *
     * @param \App\Models\CustomPage $customPage
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(CustomPage $customPage): View
    {
        return view('admin.custom-pages.edit', [
            'page' => $customPage,
        ]);
    }

    /**
     * Update a custom page
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\CustomPage $customPage
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, CustomPage $customPage): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
        ]);

        $customPage->update($validated);

        ToastMagic::success('Page updated successfully.');

        return redirect()->route('admin.custom-pages.index');
    }
}

