<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('name', 64); // name varchar(64) NOT NULL
            $table->timestamps(); // created_at and updated_at
        });

        // Insert the permissions from your SQL dump
        DB::table('permissions')->insert([
            [
                'id' => 1,
                'name' => 'roleManaging',
                'created_at' => '2024-10-10 18:57:09',
                'updated_at' => '2024-10-10 18:57:09'
            ],
            [
                'id' => 2,
                'name' => 'viewDashboard',
                'created_at' => '2024-10-10 18:57:09',
                'updated_at' => '2024-10-10 18:57:09'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
