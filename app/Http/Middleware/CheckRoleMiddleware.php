<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $admin = auth('admins')->user();

        if (!$admin) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $adminPermissions = Admin::query()->where('admins.id', $admin->id)
            ->join('admin_role', 'admins.id', '=', 'admin_role.admin_id')
            ->join('permission_role', 'admin_role.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->select('permissions.name')
            ->pluck('permissions.name')
            ->toArray();

        if (!in_array($role, $adminPermissions)) {
            return response()->json([
                'message' => 'Regrettably, the admin requires a role in order to be authorized.',
                'code' => Response::HTTP_FORBIDDEN,
            ]);
        }

        return $next($request);
    }
}
