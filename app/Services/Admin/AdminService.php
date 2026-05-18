<?php

namespace App\Services\Admin;

use App\Http\Requests\Admin\RoleAdminRequest;
use App\Repositories\Admin\AdminRepository;
use Illuminate\Http\Request;

class AdminService
{
    public function __construct(protected AdminRepository $adminRepo)
    {
    }

    public function getAdminRole($adminId)
    {
        return $this->adminRepo->getAdminRole($adminId);
    }

    public function listAdminPermissions(Request $request)
    {
        return $this->adminRepo->listAdminPermissions($request);
    }

    public function assignRoleToAdmin(RoleAdminRequest $request)
    {
        return $this->adminRepo->assignRoleToAdmin($request);
    }

    public function revokeRoleFromAdmin(RoleAdminRequest $request)
    {
        return $this->adminRepo->revokeRoleFromAdmin($request);
    }
}
