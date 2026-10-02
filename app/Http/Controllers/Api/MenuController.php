<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppMenu;
use App\Models\AppMenuItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /** GET /api/menus — full menu tree filtered by user's role permissions */
    public function index(Request $request)
    {
        $user = $request->user();

        // Admins see everything
        if ($user && $user->hasRole('admin')) {
            return AppMenu::with('items.appTable')
                ->where('active', true)
                ->orderBy('order')
                ->get();
        }

        // Get user's roles
        $userRoles = $user ? $user->getRoleNames()->toArray() : [];

        // Get all table IDs the user has at least one permission on
        $allowedTableIds = \App\Models\TablePermission::whereIn('role', $userRoles)
            ->where(function ($q) {
                $q->where('can_read', true)
                  ->orWhere('can_create', true)
                  ->orWhere('can_update', true)
                  ->orWhere('can_delete', true);
            })
            ->pluck('app_table_id')
            ->unique()
            ->toArray();

        $menus = AppMenu::with(['items' => function ($q) use ($allowedTableIds) {
            $q->where('active', true)
              ->where(function ($q2) use ($allowedTableIds) {
                  // Show item if: no table linked (custom URL) OR table is in allowed list
                  $q2->whereNull('app_table_id')
                     ->orWhereIn('app_table_id', $allowedTableIds);
              })
              ->orderBy('order');
        }])
        ->where('active', true)
        ->orderBy('order')
        ->get();

        // Remove menu groups that have no visible items
        return $menus->filter(fn($m) => $m->items->count() > 0)->values();
    }

    /** GET /api/admin/menus — all menus for admin builder */
    public function adminIndex()
    {
        return AppMenu::with('items.appTable')->orderBy('order')->get();
    }

    /** POST /api/admin/menus */
    public function store(Request $request)
    {
        $data = $request->validate([
            'label'  => 'required|string|max:100',
            'icon'   => 'nullable|string|max:10',
            'order'  => 'integer',
            'active' => 'boolean',
        ]);
        return AppMenu::create($data);
    }

    /** PUT /api/admin/menus/{menu} */
    public function update(Request $request, AppMenu $menu)
    {
        $data = $request->validate([
            'label'  => 'sometimes|string|max:100',
            'icon'   => 'nullable|string|max:10',
            'order'  => 'integer',
            'active' => 'boolean',
        ]);
        $menu->update($data);
        return $menu->load('items.appTable');
    }

    /** DELETE /api/admin/menus/{menu} */
    public function destroy(AppMenu $menu)
    {
        $menu->delete();
        return response()->noContent();
    }

    /** POST /api/admin/menus/{menu}/items */
    public function storeItem(Request $request, AppMenu $menu)
    {
        $data = $request->validate([
            'label'        => 'required|string|max:100',
            'icon'         => 'nullable|string|max:10',
            'app_table_id' => 'nullable|exists:app_tables,id',
            'custom_url'   => 'nullable|string|max:255',
            'order'        => 'integer',
            'active'       => 'boolean',
        ]);
        $item = $menu->items()->create($data);
        return $item->load('appTable');
    }

    /** PUT /api/admin/menu-items/{item} */
    public function updateItem(Request $request, AppMenuItem $item)
    {
        $data = $request->validate([
            'label'        => 'sometimes|string|max:100',
            'icon'         => 'nullable|string|max:10',
            'app_table_id' => 'nullable|exists:app_tables,id',
            'custom_url'   => 'nullable|string|max:255',
            'order'        => 'integer',
            'active'       => 'boolean',
        ]);
        $item->update($data);
        return $item->load('appTable');
    }

    /** DELETE /api/admin/menu-items/{item} */
    public function destroyItem(AppMenuItem $item)
    {
        $item->delete();
        return response()->noContent();
    }

    /** POST /api/admin/menus/reorder — save full order */
    public function reorder(Request $request)
    {
        $data = $request->validate([
            'menus'                => 'required|array',
            'menus.*.id'           => 'required|exists:app_menus,id',
            'menus.*.order'        => 'integer',
            'menus.*.items'        => 'array',
            'menus.*.items.*.id'   => 'required|exists:app_menu_items,id',
            'menus.*.items.*.order'=> 'integer',
        ]);

        foreach ($data['menus'] as $m) {
            AppMenu::where('id', $m['id'])->update(['order' => $m['order']]);
            foreach ($m['items'] ?? [] as $it) {
                AppMenuItem::where('id', $it['id'])->update(['order' => $it['order']]);
            }
        }
        return $this->adminIndex();
    }
}
