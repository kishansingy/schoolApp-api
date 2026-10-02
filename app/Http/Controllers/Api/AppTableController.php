<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppTable;
use App\Services\SchemaTableManager;
use Illuminate\Http\Request;

class AppTableController extends Controller
{
    public function index()
    {
        return AppTable::with('parent')->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                   => 'required|string|unique:app_tables,name|regex:/^[a-z_][a-z0-9_]*$/',
            'label'                  => 'required|string',
            'table_type'             => 'string|in:standard,header,detail,footer',
            'detail_of_table_id'     => 'nullable|exists:app_tables,id',
            'parent_id'              => 'nullable|exists:app_tables,id',
            // Controls
            'extensible'             => 'boolean',
            'live_feed'              => 'boolean',
            'auto_number'            => 'boolean',
            'auto_number_prefix'     => 'nullable|string|max:10',
            'auto_number_base'       => 'integer|min:0',
            'auto_number_padding'    => 'integer|min:1|max:20',
            'create_access_controls' => 'boolean',
            'user_role'              => 'nullable|string',
            // Application Access
            'accessible_from'        => 'string|in:all,this_app,subscriber',
            'can_read'               => 'boolean',
            'can_create'             => 'boolean',
            'can_update'             => 'boolean',
            'can_delete'             => 'boolean',
            'allow_web_services'     => 'boolean',
            'allow_configuration'    => 'boolean',
        ]);

        // Auto-assign schema_table name
        $data['schema_table'] = 'sch_' . $data['name'];

        $table = AppTable::create($data);

        // Create the actual schema table (empty for now, fields added later)
        app(SchemaTableManager::class)->sync($table);

        return $table;
    }

    public function show(AppTable $appTable)
    {
        return $appTable->load('parent', 'fields', 'detailTables');
    }

    public function update(Request $request, AppTable $appTable)
    {
        $data = $request->validate([
            'name'                   => 'sometimes|string|unique:app_tables,name,' . $appTable->id . '|regex:/^[a-z_][a-z0-9_]*$/',
            'label'                  => 'sometimes|string',
            'table_type'             => 'string|in:standard,header,detail,footer',
            'detail_of_table_id'     => 'nullable|exists:app_tables,id',
            'parent_id'              => 'nullable|exists:app_tables,id',
            'extensible'             => 'boolean',
            'live_feed'              => 'boolean',
            'auto_number'            => 'boolean',
            'auto_number_prefix'     => 'nullable|string|max:10',
            'auto_number_base'       => 'integer|min:0',
            'auto_number_padding'    => 'integer|min:1|max:20',
            'create_access_controls' => 'boolean',
            'user_role'              => 'nullable|string',
            'accessible_from'        => 'string|in:all,this_app,subscriber',
            'can_read'               => 'boolean',
            'can_create'             => 'boolean',
            'can_update'             => 'boolean',
            'can_delete'             => 'boolean',
            'allow_web_services'     => 'boolean',
            'allow_configuration'    => 'boolean',
            'whatsapp_broadcast'     => 'boolean',
            'whatsapp_phone_field'   => 'nullable|string',
        ]);

        $appTable->update($data);
        return $appTable;
    }

    public function destroy(AppTable $appTable)
    {
        $appTable->delete();
        return response()->noContent();
    }

    public function fields(AppTable $appTable)
    {
        return $appTable->allFields()->values();
    }

    /**
     * GET /tables/{table}/visible-fields
     * Returns all fields visible to the current user's role.
     * Admins see everything. Others see only fields where visible=true for their role.
     */
    public function visibleFields(AppTable $appTable, Request $request)
    {
        $user   = $request->user();
        $fields = $appTable->allFields();

        // Admins see all fields
        if ($user && $user->hasRole('admin')) {
            return $fields->values();
        }

        // Get user's first role
        $role = $user?->roles->first()?->name;
        if (!$role) {
            return $fields->values(); // no role = show all
        }

        // Filter by field permissions
        $filtered = $fields->filter(function ($field) use ($role) {
            $perm = \App\Models\FieldPermission::where('app_field_id', $field->id)
                ->where('role', $role)
                ->first();
            // If no permission row exists, default to visible
            return $perm === null || $perm->visible;
        });

        return $filtered->values();
    }
}
