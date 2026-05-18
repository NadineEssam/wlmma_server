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
        Schema::create('tool_images', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('image_id'); // image_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('tool_id'); // tool_id bigint(20) UNSIGNED NOT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('image_id');
            $table->index('tool_id');

            // Add foreign key constraints
            // $table->foreign('image_id')->references('id')->on('images')->onDelete('cascade');
            // $table->foreign('tool_id')->references('id')->on('commercial_tools')->onDelete('cascade');

            // Add unique constraint to prevent duplicate image assignments
            $table->unique(['image_id', 'tool_id'], 'tool_images_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_images');
    }
};
