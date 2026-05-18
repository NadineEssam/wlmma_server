<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('admins', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->text('fcm_token')->nullable(); // fcm_token text DEFAULT NULL
            $table->string('first_name', 64); // first_name varchar(64) NOT NULL
            $table->string('second_name', 64); // second_name varchar(64) NOT NULL
            $table->text('address')->nullable(); // address text DEFAULT NULL
            $table->string('national_id', 14)->nullable(); // national_id varchar(14) DEFAULT NULL
            $table->string('phone_number', 20); // phone_number varchar(20) NOT NULL
            $table->string('email')->nullable(); // email varchar(255) DEFAULT NULL
            $table->string('password'); // password varchar(255) NOT NULL
            $table->boolean('is_authorized_by_manager')->default(0); // is_authorized_by_manager tinyint(1) NOT NULL DEFAULT '0'
            $table->string('remember_token', 100)->nullable(); // remember_token varchar(100) DEFAULT NULL
            $table->timestamps(); // created_at and updated_at
            $table->softDeletes(); // deleted_at timestamp NULL DEFAULT NULL
        });

        // Insert the admin users from your SQL dump
        DB::table('admins')->insert([
            [
                'id' => 1,
                'fcm_token' => 'c7PpDq02Lft-UdQrJZjphR:APA91bFdaJ-RtiM-7GpS0je62K_KmyfvqT3nOXBQpgQFozTsV9pX34rql89Z5dwbzFL1DaPBPa9NOF5Htu4iKdNj9pX7-hZMVS-syD8YHIKSu45Q107FcEA',
                'first_name' => 'test',
                'second_name' => 'admin',
                'address' => 'Riyadh',
                'national_id' => '29751518416484',
                'phone_number' => '+966551234008',
                'email' => 'admin@wlmma.com',
                'password' => Hash::make('i44new_$Rxitf8hbt'), // You'll need to set the actual password
                'is_authorized_by_manager' => 1,
                'remember_token' => null,
                'created_at' => '2024-10-10 18:57:08',
                'updated_at' => '2025-10-19 19:42:47',
                'deleted_at' => null
            ],
            [
                'id' => 2,
                'fcm_token' => 'eYTtJ4aiTbDWxQcqVp-eXy:APA91bHHgdBfvd8_McVYw6MxkU19pwF8NaHhhBUHu8_U-HaJj7n-NYEHcbeiTqUYCF6FJjPJ9W8y1ll-IoxJtkGrd3UuEvRU1Ic8wi-lM3w5FtUZNRvkZeM',
                'first_name' => 'test',
                'second_name' => 'Manager',
                'address' => 'Makkah',
                'national_id' => '29515163519815',
                'phone_number' => '+966551242456',
                'email' => 'manager@walama.com',
                'password' => Hash::make('password'), // You'll need to set the actual password
                'is_authorized_by_manager' => 1,
                'remember_token' => null,
                'created_at' => '2024-10-10 18:57:09',
                'updated_at' => '2024-10-10 18:57:09',
                'deleted_at' => null
            ],
            [
                'id' => 5,
                'fcm_token' => 'c7PpDq02Lft-UdQrJZjphR:APA91bFdaJ-RtiM-7GpS0je62K_KmyfvqT3nOXBQpgQFozTsV9pX34rql89Z5dwbzFL1DaPBPa9NOF5Htu4iKdNj9pX7-hZMVS-syD8YHIKSu45Q107FcEA',
                'first_name' => 'test 2',
                'second_name' => 'admin 2',
                'address' => 'Riyadh',
                'national_id' => '29751518416489',
                'phone_number' => '+966551234007',
                'email' => 'admin2@wlmma.com',
                'password' => Hash::make('password'), // You'll need to set the actual password
                'is_authorized_by_manager' => 1,
                'remember_token' => null,
                'created_at' => '2024-10-10 18:57:08',
                'updated_at' => '2025-10-16 19:34:31',
                'deleted_at' => null
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
