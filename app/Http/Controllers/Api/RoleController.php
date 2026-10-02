<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** List all roles */
    public function index()
    {
        return Role::all()->map(fn($role) => [
            'id'          => $role->id,
            'name'        => $role->name,
            'users_count' => \App\Models\User::role($role->name)->count(),
        ]);
    }

    /** Create a role */
    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|unique:roles,name']);
        return Role::create(['name' => $data['name'], 'guard_name' => 'web']);
    }

    /** Delete a role */
    public function destroy(Role $role)
    {
        if (in_array($role->name, ['admin', 'viewer'])) {
            return response()->json(['message' => 'Cannot delete system roles.'], 422);
        }
        $role->delete();
        return response()->noContent();
    }

    /** List all users with their roles */
    public function users()
    {
        return User::with('roles')->get()->map(fn($u) => [
            'id'            => $u->id,
            'name'          => $u->name,
            'email'         => $u->email,
            'roles'         => $u->roles->pluck('name'),
            'app_record_id' => $u->app_record_id,
            'record_type'   => $u->record_type,
        ]);
    }

    /** Assign roles to a user */
    public function assignRoles(Request $request, User $user)
    {
        $data = $request->validate(['roles' => 'required|array', 'roles.*' => 'string|exists:roles,name']);
        $user->syncRoles($data['roles']);
        return $user->load('roles');
    }

    /** Get unlinked records for a given type (student/staff/parent) — excludes already-linked ones */
    public function unlinkedRecords(Request $request)
    {
        $type = $request->input('type');
        if (!$type) return response()->json([]);

        // Find the table — try plural first, then singular
        $table = \App\Models\AppTable::where('name', $type . 's')->first()
            ?? \App\Models\AppTable::where('name', $type)->first();
        if (!$table) return response()->json([]);

        // Get app_record_ids already linked to a user (any type)
        $linkedIds = User::whereNotNull('app_record_id')
            ->pluck('app_record_id')
            ->toArray();

        // Get records excluding linked ones, deduplicated by a unique key
        $seen   = [];
        $result = [];

        \App\Models\AppRecord::where('app_table_id', $table->id)
            ->whereNotIn('id', $linkedIds)
            ->latest()
            ->get()
            ->each(function ($r) use (&$seen, &$result) {
                $d = $r->data ?? [];

                // Build label
                $label = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                if (!$label) $label = $d['name'] ?? $d['admission_no'] ?? $d['staff_no'] ?? '';

                // Deduplicate: skip if we've seen this exact label+key combo
                $uniqueKey = $d['admission_no'] ?? $d['staff_no'] ?? $d['email'] ?? $label;
                if ($uniqueKey && isset($seen[$uniqueKey])) return;
                if ($uniqueKey) $seen[$uniqueKey] = true;

                if ($label) {
                    $result[] = ['id' => $r->id, 'label' => $label];
                }
            });

        return response()->json($result);
    }
    public function createUser(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'email'         => 'required|email|unique:users,email',
            'password'      => 'required|string|min:6',
            'roles'         => 'array',
            'roles.*'       => 'string|exists:roles,name',
            'app_record_id' => 'nullable|integer',
            'record_type'   => 'nullable|string|max:50',
        ]);

        $user = User::create([
            'name'          => $data['name'],
            'email'         => $data['email'],
            'password'      => bcrypt($data['password']),
            'app_record_id' => $data['app_record_id'] ?? null,
            'record_type'   => $data['record_type'] ?? null,
        ]);

        if (!empty($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'roles'         => $user->roles->pluck('name'),
            'app_record_id' => $user->app_record_id,
            'record_type'   => $user->record_type,
        ], 201);
    }

    /** Update user (password, roles, record link) */
    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'email'         => 'sometimes|email|unique:users,email,' . $user->id,
            'password'      => 'nullable|string|min:6',
            'roles'         => 'array',
            'roles.*'       => 'string|exists:roles,name',
            'app_record_id' => 'nullable|integer',
            'record_type'   => 'nullable|string|max:50',
        ]);

        $update = array_filter([
            'name'          => $data['name'] ?? null,
            'email'         => $data['email'] ?? null,
            'app_record_id' => $data['app_record_id'] ?? null,
            'record_type'   => $data['record_type'] ?? null,
        ], fn($v) => $v !== null);

        if (!empty($data['password'])) {
            $update['password'] = bcrypt($data['password']);
        }

        $user->update($update);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        return response()->json([
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'roles'         => $user->fresh()->roles->pluck('name'),
            'app_record_id' => $user->app_record_id,
            'record_type'   => $user->record_type,
        ]);
    }

    /** Delete a user */
    public function destroyUser(User $user)
    {
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            return response()->json(['message' => 'Cannot delete the last admin.'], 422);
        }
        $user->delete();
        return response()->noContent();
    }
}
