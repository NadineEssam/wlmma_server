<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('ratings')) {
            Schema::create('ratings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();  // Registered user ID
                $table->unsignedBigInteger('activity_id');  // Activity being rated
                $table->unsignedBigInteger('tool_id');  // Activity being rated
                $table->unsignedTinyInteger('rating')->comment('Rating out of 5');  // 1 to 5 rating
                $table->text('comment')->nullable()->comment('Optional feedback');
                $table->string('user_email')->nullable()->comment('Email for users');  // User email
                $table->string('user_name')->nullable()->comment('Name for users');  // User name
                $table->timestamps();

                // Foreign key constraints
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');

                // Optional: Ensure guests cannot leave `user_id` populated
                // DB-level validation could be added if needed.
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
