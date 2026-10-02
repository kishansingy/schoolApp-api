<?php

namespace App\Http\Middleware;

use App\Models\AppTable;
use App\Models\TablePermission;
use Closure;
use Illuminate\Http\Request;

class CheckTablePermission
{
    /**
     * Map HTTP method → permission column.
     */
    private const METHOD_MAP = [
        'GET'    => 'can_read',
        'POST'   => 'can_create',
        'PUT'    => 'can_update',
        'PATCH'  => 'can_update',
        'DELETE' => 'can_delete',
    ];

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Unauthenticated — let auth middleware handle it
        if (!$user) return $next($request);

        // Admins bypass all table-level checks
        if ($user->hasRole('admin')) return $next($request);

        // Resolve the AppTable from route
        $appTable = $request->route('appTable');
        if (!$appTable instanceof AppTable) return $next($request);

        $method    = $request->method();
        $permCol   = self::METHOD_MAP[$method] ?? 'can_read';
        $userRoles = $user->getRoleNames();

        // Check if ANY of the user's roles has the required permission
        $allowed = TablePermission::where('app_table_id', $appTable->id)
            ->whereIn('role', $userRoles)
            ->where($permCol, true)
            ->exists();

        // Fallback to table-level default if no role-specific row exists
        $hasRoleRow = TablePermission::where('app_table_id', $appTable->id)
            ->whereIn('role', $userRoles)
            ->exists();

        if (!$hasRoleRow) {
            // Use the table's own default flags
            $allowed = (bool) $appTable->{$permCol};
        }

        if (!$allowed) {
            return response()->json(['message' => "You don't have {$permCol} permission on this table."], 403);
        }

        return $next($request);
    }
}
