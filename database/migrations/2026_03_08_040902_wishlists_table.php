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
        Schema::create('wishlists', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('user_id'); // user_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('activity_id')->nullable(); // activity_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('tool_id')->nullable(); // tool_id bigint(20) UNSIGNED DEFAULT NULL
            $table->enum('type', ['activity', 'tool']); // type enum('activity','tool') NOT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('user_id');
            $table->index('activity_id');
            $table->index('tool_id');


            // Add foreign key constraints (commented out until related tables exist)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            // $table->foreign('tool_id')->references('id')->on('commercial_tools')->onDelete('cascade');

            // Add unique constraint to prevent duplicate wishlist items
            $table->unique(['user_id', 'activity_id'], 'wishlists_user_activity_unique');
            $table->unique(['user_id', 'tool_id'], 'wishlists_user_tool_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlists');
    }
};
