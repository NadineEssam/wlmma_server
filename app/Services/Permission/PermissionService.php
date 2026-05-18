<?php

namespace App\Services\Permission;

use App\Repositories\Permission\PermissionRepository;

class PermissionService
{
    public function __construct(protected PermissionRepository $permissionRepo)
    {
    }

    public function list()
    {
        return $this->permissionRepo->list();
    }
}
