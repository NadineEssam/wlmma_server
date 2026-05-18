<?php

namespace App\Repositories\Admin;

use App\Http\Requests\Admin\RoleAdminRequest;
use App\Models\Role;
use App\Models\AdminRole;
use App\Models\Admin;
use Illuminate\Http\Request;

class AdminRepository
{
    public function getAdminRole($adminId)
    {
        return Role::query()->where('ar.admin_id', $adminId)
            ->where('roles.name', 'manager')
            ->leftjoin('admin_role as ar', 'roles.id', '=', 'ar.role_id')
            ->exists();
    }

    public function listAdminPermissions(Request $request)
    {
        return Admin::query()->where('admins.id', $request->user()->id)
            ->join('admin_role', 'admins.id', '=', 'admin_role.admin_id')
            ->join('permission_role', 'admin_role.role_id', '=', 'permission_role.role_id')
            ->join('permissions', 'permission_role.permission_id', '=', 'permissions.id')
            ->select('permissions.name', 'permissions.id')
            ->orderBy('id')
            ->get();
    }

    public function assignRoleToAdmin(RoleAdminRequest $request)
    {
        $AdminRole = AdminRole::create($request->only([
            'admin_id',
            'role_id',
        ]));

        return $AdminRole;
    }

    public function revokeRoleFromAdmin(RoleAdminRequest $request)
    {
        return AdminRole::query()
            ->where('admin_id', $request->admin_id)
            ->where('role_id', $request->role_id)
            ->delete();
    }
}
