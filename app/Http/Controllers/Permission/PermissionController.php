<?php

namespace App\Http\Controllers\Permission;

use App\Http\Controllers\Controller;
use App\Http\Resources\Permission\PermissionResource;
use App\Services\Permission\PermissionService;

class PermissionController extends Controller
{
    public function __construct(protected PermissionService $permissionService) {}

    public function list()
    {
        $data = PermissionResource::collection($this->permissionService->list());

        return response()->json([
            'data' => $data,
            'meta' => ['total' => count($data)],
        ]);
    }
}
