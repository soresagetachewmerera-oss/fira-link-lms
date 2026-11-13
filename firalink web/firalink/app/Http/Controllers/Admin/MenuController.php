<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Menu Controller
 *
 * This controller handles the management of menus.
 *
 * @package App\Http\Controllers\Admin
 */
class MenuController extends Controller
{
    /**
     * Display all menus
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index(): View
    {
        $menus = Menu::query()
            ->withCount('allItems')
            ->orderBy('name')
            ->paginate(12);

        return view('admin.frontend.menus.index', [
            'menus' => $menus,
        ]);
    }

    /**
     * Display the create menu form
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function create(): View
    {
        return view('admin.frontend.menus.create', [
            'menu' => new Menu(['is_active' => true]),
            'locationSuggestions' => $this->locationSuggestions(),
        ]);
    }

    /**
     * Store a new menu
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedData($request);

        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['name']);
        }

        $menu = Menu::create($data);
        Cache::forget('frontend.menus.locations');

        ToastMagic::success('Menu created successfully. You can now add menu items.');

        return redirect()
            ->route('admin.frontend.menus.edit', $menu);
    }

    /**
     * Display a menu details
     *
     * @param \App\Models\Menu $menu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function show(Menu $menu): RedirectResponse
    {
        return redirect()->route('admin.frontend.menus.edit', $menu);
    }

    /**
     * Display the edit menu form
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Menu $menu
     * @return \Illuminate\Contracts\View\View
     */
    public function edit(Request $request, Menu $menu): View
    {
        $menu->load(['allItems' => function ($query) {
            $query->orderBy('order');
        }, 'allItems.children']);

        $menuItems = $menu->allItems
            ->whereNull('parent_id')
            ->map(function (MenuItem $item) {
                return $this->formatMenuItem($item);
            })
            ->values();

        $currentItem = null;
        if ($request->filled('item')) {
            $currentItem = $menu->allItems->firstWhere('id', (int) $request->input('item'));
        }

        return view('admin.frontend.menus.edit', [
            'menu' => $menu,
            'menuItems' => $menuItems,
            'flatItems' => $menu->allItems,
            'locationSuggestions' => $this->locationSuggestions(),
            'currentItem' => $currentItem,
        ]);
    }

    /**
     * Update a menu
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Menu $menu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validatedData($request, $menu->id);

        if (empty($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($data['name'], $menu->id);
        }

        $menu->update($data);
        Cache::forget('frontend.menus.locations');

        ToastMagic::success('Menu updated successfully.');

        return redirect()
            ->route('admin.frontend.menus.edit', $menu);
    }

    /**
     * Delete a menu
     *
     * @param \App\Models\Menu $menu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();
        Cache::forget('frontend.menus.locations');

        ToastMagic::success('Menu deleted successfully.');

        return redirect()
            ->route('admin.frontend.menus.index');
    }

    /**
     * Validate the request data
     *
     * @param \Illuminate\Http\Request $request
     * @param int|null $menuId
     * @return array
     */
    private function validatedData(Request $request, ?int $menuId = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('menus', 'slug')->ignore($menuId)],
            'location' => ['required', 'string', 'max:120', Rule::unique('menus', 'location')->ignore($menuId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        return $validated;
    }

    /**
     * Generate a unique slug
     *
     * @param string $name
     * @param int|null $ignoreId
     * @return string
     */
    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $counter = 1;

        while (Menu::where('slug', $slug)
            ->when($ignoreId, fn($query) => $query->where('id', '!=', $ignoreId))
            ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function formatMenuItem(MenuItem $menuItem): array
    {
        return [
            'id' => $menuItem->id,
            'title' => $menuItem->title,
            'url' => $menuItem->resolved_url,
            'route_name' => $menuItem->route_name,
            'route_parameters' => $menuItem->route_parameters,
            'target' => $menuItem->target,
            'is_active' => $menuItem->is_active,
            'order' => $menuItem->order,
            'children' => $menuItem->children->map(function (MenuItem $child) {
                return $this->formatMenuItem($child);
            })->values(),
        ];
    }

    private function locationSuggestions(): array
    {
        return [
            'main-navigation',
            'mobile-navigation',
            'footer-links',
            'quick-links',
        ];
    }
}
