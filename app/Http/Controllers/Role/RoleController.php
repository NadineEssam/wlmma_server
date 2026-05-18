<?php

namespace App\Http\Controllers\Role;

use App\Core\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Role\PermissionRoleRequest;
use App\Http\Resources\Role\RoleResource;
use App\Services\Role\RoleService;

class RoleController extends Controller
{
    public function __construct(protected RoleService $roleService) {}

    public function list()
    {
        $data = RoleResource::collection($this->roleService->list());

        return response()->json([
            'data' => $data,
            'meta' => ['total' => count($data)],
        ]);
    }

    // public function createPermissionRole(PermissionRoleRequest $request)
    // {
    //     $data = $this->roleService->createPermissionRole($request);

    //     return response()->json([
    //         'data' => $data,
    //         'meta' => ['total' => count($data)],
    //     ]);
    // }
    public function createPermissionRole(PermissionRoleRequest $request)
    {
        $data = $this->roleService->createPermissionRole($request);

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => is_countable($data) ? count($data) : 1, // Count if $data is countable, else default to 1
            ],
        ]);
    }
}
