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
        Schema::create('admin_role', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->unsignedBigInteger('admin_id');
            $table->unsignedBigInteger('role_id');

            // Add indexes for better performance
            $table->index('admin_id');
            $table->index('role_id');

            // Comment out foreign key constraints for now
            // $table->foreign('admin_id')->references('id')->on('admins')->onDelete('cascade');
            // $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

            // Add unique constraint to prevent duplicate entries
            $table->unique(['admin_id', 'role_id']);
        });

        // Insert the admin_role relationships from your SQL dump
        DB::table('admin_role')->insert([
            [
                'id' => 1,
                'created_at' => '2024-10-10 18:57:10',
                'updated_at' => '2024-10-10 18:57:10',
                'admin_id' => 1,
                'role_id' => 1
            ],
            [
                'id' => 2,
                'created_at' => '2024-10-10 18:57:10',
                'updated_at' => '2024-10-10 18:57:10',
                'admin_id' => 2,
                'role_id' => 1
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_role');
    }
};
