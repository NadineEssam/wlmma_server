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
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->timestamps(); // created_at and updated_at
            $table->unsignedBigInteger('permission_id'); // permission_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('role_id'); // role_id bigint(20) UNSIGNED NOT NULL

            // Add indexes for better performance
            $table->index('permission_id');
            $table->index('role_id');

            // Add foreign key constraints (commented out until related tables exist)
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
            // $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            // Add unique constraint to prevent duplicate permission-role assignments
            $table->unique(['permission_id', 'role_id'], 'permission_role_unique');
        });

        // Insert the permission_role relationships from your SQL dump
        DB::table('permission_role')->insert([
            [
                'id' => 1,
                'created_at' => '2024-10-10 18:57:10',
                'updated_at' => '2024-10-10 18:57:10',
                'permission_id' => 1,
                'role_id' => 1
            ],
            [
                'id' => 2,
                'created_at' => '2024-10-10 18:57:10',
                'updated_at' => '2024-10-10 18:57:10',
                'permission_id' => 2,
                'role_id' => 1
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};
