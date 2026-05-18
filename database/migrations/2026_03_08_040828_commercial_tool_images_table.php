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
        Schema::create('commercial_tool_images', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('commercial_tool_id')->nullable();
            $table->unsignedBigInteger('image_id')->nullable();
            $table->timestamps();

            // Add indexes
            // $table->index('commercial_tool_id');
            // $table->index('image_id');

            // Add foreign key constraints (commented out until related tables exist)
            // $table->foreign('commercial_tool_id')->references('id')->on('commercial_tools')->onDelete('cascade');
            // $table->foreign('image_id')->references('id')->on('images')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_tool_images');
    }
};
