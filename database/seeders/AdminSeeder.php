<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->insert([
            [
                'first_name' => 'test',
                'second_name' => 'admin',
                'national_id' => '29751518416484',
                'address' => 'Riyadh',
                'phone_number' => '+966551234008',
                'email' => 'admin@walama.com',
                'password' => Hash::make(value: 'admin@123'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'is_authorized_by_manager' => true,
            ],
            [
                'first_name' => 'test',
                'second_name' => 'Manager',
                'national_id' => '29515163519815',
                'address' => 'Makkah',
                'phone_number' => '+966551242456',
                'email' => 'manager@walama.com',
                'password' => Hash::make('manager@123'),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'is_authorized_by_manager' => true,
            ],
        ]);
    }
}
