<?php

namespace Database\Seeders;

use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PivotSeeder extends Seeder
{
    public function run(): void
    {
        $manager = Role::select('id')->where('name', 'manager')->first()?->id;
        DB::table('admin_role')->insert([
            [
                'role_id' => $manager,
                'admin_id' => Admin::select('id')->where('phone_number', '+966551234008')->first()?->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'role_id' => $manager,
                'admin_id' => Admin::select('id')->where('phone_number', '+966551242456')->first()?->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);

        DB::table('permission_role')->insert([
            [
                'role_id' => $manager,
                'permission_id' => Permission::select('id')->where('name', 'roleManaging')->first()?->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'role_id' => $manager,
                'permission_id' => Permission::select('id')->where('name', 'viewDashboard')->first()?->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        ]);
    }
}
