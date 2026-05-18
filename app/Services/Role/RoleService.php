<?php

namespace App\Services\Role;

use App\Http\Requests\Role\PermissionRoleRequest;
use App\Repositories\Role\RoleRepository;

class RoleService
{
    public function __construct(protected RoleRepository $roleRepo)
    {
    }

    public function list()
    {
        return $this->roleRepo->list();
    }

    public function createPermissionRole(PermissionRoleRequest $request)
    {
        return $this->roleRepo->createPermissionRole($request);
    }
}
