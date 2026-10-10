<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;
use Devrabiul\ToastMagic\Facades\ToastMagic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

/**
 * Menu Item Controller
 *
 * This controller handles the management of menu items.
 *
 * @package App\Http\Controllers\Admin
 */
class MenuItemController extends Controller
{
    /**
     * Store a new menu item
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Menu $menu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Menu $menu): RedirectResponse
    {
        $data = $this->validatedData($request, $menu);

        $maxOrder = $menu->allItems()
            ->where('parent_id', $data['parent_id'] ?? null)
            ->max('order') ?? -1;
        $data['order'] = $maxOrder + 1;

        $menuItem = $menu->allItems()->create($data);
        Cache::forget('frontend.menus.locations');

        ToastMagic::success(sprintf('Menu item "%s" added.', $menuItem->title));

        return redirect()
            ->route('admin.frontend.menus.edit', $menu);
    }

    /**
     * Update a menu item
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Menu $menu
     * @param \App\Models\MenuItem $menuItem
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Menu $menu, MenuItem $menuItem): RedirectResponse
    {
        abort_unless($menuItem->menu_id === $menu->id, 404);

        $data = $this->validatedData($request, $menu, $menuItem->id);

        $menuItem->update($data);
        Cache::forget('frontend.menus.locations');

        ToastMagic::success('Menu item updated.');

        return redirect()
            ->route('admin.frontend.menus.edit', $menu);
    }

    /**
     * Delete a menu item
     *
     * @param \App\Models\Menu $menu
     * @param \App\Models\MenuItem $menuItem
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Menu $menu, MenuItem $menuItem): RedirectResponse
    {
        abort_unless($menuItem->menu_id === $menu->id, 404);

        $menuItem->delete();
        Cache::forget('frontend.menus.locations');

        ToastMagic::success('Menu item deleted.');

        return redirect()
            ->route('admin.frontend.menus.edit', $menu);
    }

    /**
     * Reorder menu items
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Menu $menu
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reorder(Request $request, Menu $menu): RedirectResponse
    {
        $orders = $request->input('orders', []);

        foreach ($orders as $id => $order) {
            $menu->allItems()
                ->where('id', $id)
                ->update(['order' => (int) $order]);
        }

        Cache::forget('frontend.menus.locations');
        ToastMagic::success('Menu order updated.');

        return redirect()
            ->route('admin.frontend.menus.edit', $menu);
    }

    /**
     * Validate the request data
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Menu $menu
     * @param int|null $menuItemId
     * @return array
     */
    private function validatedData(Request $request, Menu $menu, ?int $menuItemId = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'url' => ['nullable', 'string', 'max:255'],
            'route_name' => ['nullable', 'string', 'max:190'],
            'route_parameters_json' => ['nullable', 'string'],
            'target' => ['nullable', 'in:_self,_blank,_parent,_top'],
            'parent_id' => [
                'nullable',
                Rule::exists('menu_items', 'id')->where(fn($query) => $query->where('menu_id', $menu->id)->when($menuItemId, fn($q) => $q->where('id', '!=', $menuItemId))),
            ],
            'is_active' => ['nullable', 'boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        if (empty($data['url']) && empty($data['route_name'])) {
            $request->validate([
                'url' => ['required_without:route_name'],
                'route_name' => ['required_without:url'],
            ]);
        }

        $data['is_active'] = $request->boolean('is_active', true);

        if (array_key_exists('route_parameters_json', $data)) {
            if (filled($data['route_parameters_json'])) {
                $decoded = json_decode($data['route_parameters_json'], true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                    $request->validate([
                        'route_parameters_json' => ['nullable', 'json'],
                    ]);
                }

                $data['route_parameters'] = $decoded ?? null;
            } else {
                $data['route_parameters'] = null;
            }
        }

        unset($data['route_parameters_json']);

        if (isset($data['order'])) {
            $data['order'] = (int) $data['order'];
        }

        return $data;
    }
}
