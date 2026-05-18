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
        Schema::create('activity_images', function (Blueprint $table) {
            $table->id();  // This creates: id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('image_id')->nullable();  // image_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('activity_id')->nullable();  // activity_id bigint(20) UNSIGNED NOT NULL
            $table->timestamps();  // This creates both created_at and updated_at with correct defaults

            // Add indexes
            $table->index('activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_images');
    }
};
