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
            bigInteger('id', 20)->nullable();
            bigInteger('activity_id', 20)->nullable();
            bigInteger('tool_id', 20)->nullable();
            bigInteger('user_id', 20)->nullable();
            enum('type');
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