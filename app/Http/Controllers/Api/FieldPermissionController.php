<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppField;
use App\Models\AppTable;
use App\Models\FieldPermission;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class FieldPermissionController extends Controller
{
    /** Get all role permissions for every field in a table */
    public function index(AppTable $appTable)
    {
        $roles  = Role::all()->pluck('name');
        $fields = $appTable->allFields();

        return $fields->map(function ($field) use ($roles) {
            $existing = FieldPermission::where('app_field_id', $field->id)
                ->get()->keyBy('role');

            return [
                'field_id'    => $field->id,
                'field_name'  => $field->name,
                'field_label' => $field->label,
                'permissions' => $roles->map(fn($role) => $existing->get($role) ?? [
                    'id'           => null,
                    'app_field_id' => $field->id,
                    'role'         => $role,
                    'visible'      => true,
                    'editable'     => true,
                    'mandatory'    => false,
                ])->values(),
            ];
        })->values();
    }

    /** Save field permissions for a table (all fields, all roles) */
    public function save(Request $request, AppTable $appTable)
    {
        $data = $request->validate([
            'fields'                          => 'required|array',
            'fields.*.field_id'               => 'required|exists:app_fields,id',
            'fields.*.permissions'            => 'required|array',
            'fields.*.permissions.*.role'     => 'required|string|exists:roles,name',
            'fields.*.permissions.*.visible'  => 'boolean',
            'fields.*.permissions.*.editable' => 'boolean',
            'fields.*.permissions.*.mandatory'=> 'boolean',
        ]);

        foreach ($data['fields'] as $fieldRow) {
            foreach ($fieldRow['permissions'] as $perm) {
                FieldPermission::updateOrCreate(
                    ['app_field_id' => $fieldRow['field_id'], 'role' => $perm['role']],
                    [
                        'visible'   => $perm['visible']   ?? true,
                        'editable'  => $perm['editable']  ?? true,
                        'mandatory' => $perm['mandatory']  ?? false,
                    ]
                );
            }
        }

        return $this->index($appTable);
    }
}
