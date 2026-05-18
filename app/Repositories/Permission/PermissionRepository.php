<?php

namespace App\Repositories\Permission;

use App\Models\Permission;

class PermissionRepository
{
    public function list()
    {
        return Permission::select('id', 'name')->get();
    }
}
