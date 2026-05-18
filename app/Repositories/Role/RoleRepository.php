<?php

namespace App\Repositories\Role;

use App\Http\Requests\Role\PermissionRoleRequest;
use App\Models\PermissionRole;
use App\Models\Role;
use App\Models\AdminRole;

class RoleRepository
{
    public function list()
    {
        return Role::select('id', 'name')->get();
    }

    public function addRoleToAdmin($id, $role)
    {
        AdminRole::create([
            'admin_id' => $id,
            'role_id' => Role::select('id')->where('name', $role)->first()?->id,
        ]);
    }

    public function createPermissionRole(PermissionRoleRequest $request)
    {
        return PermissionRole::create($request->only([
            'permission_id',
            'role_id',
        ]));
    }
}
