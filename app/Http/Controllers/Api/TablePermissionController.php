<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppTable;
use App\Models\TablePermission;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class TablePermissionController extends Controller
{
    /** Get all role permissions for a table */
    public function index(AppTable $appTable)
    {
        $roles       = Role::all()->pluck('name');
        $existing    = $appTable->tablePermissions()->get()->keyBy('role');

        // Return a row per role (fill defaults if not set)
        return $roles->map(fn($role) => $existing->get($role) ?? [
            'id'           => null,
            'app_table_id' => $appTable->id,
            'role'         => $role,
            'can_read'     => false,
            'can_create'   => false,
            'can_update'   => false,
            'can_delete'   => false,
        ])->values();
    }

    /** Save (upsert) permissions for all roles on a table */
    public function save(Request $request, AppTable $appTable)
    {
        $rows = $request->validate([
            'permissions'              => 'required|array',
            'permissions.*.role'       => 'required|string|exists:roles,name',
            'permissions.*.can_read'   => 'boolean',
            'permissions.*.can_create' => 'boolean',
            'permissions.*.can_update' => 'boolean',
            'permissions.*.can_delete' => 'boolean',
        ]);

        foreach ($rows['permissions'] as $row) {
            TablePermission::updateOrCreate(
                ['app_table_id' => $appTable->id, 'role' => $row['role']],
                [
                    'can_read'   => $row['can_read']   ?? false,
                    'can_create' => $row['can_create'] ?? false,
                    'can_update' => $row['can_update'] ?? false,
                    'can_delete' => $row['can_delete'] ?? false,
                ]
            );
        }

        return $this->index($appTable);
    }
}
