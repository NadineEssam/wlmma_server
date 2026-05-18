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
        Schema::create('commercial_too_attribute_values', function (Blueprint $table) {
            bigInteger('id', 20)->nullable();
            bigInteger('tool_attribute_id', 20)->nullable();
            string('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_too_attribute_values');
    }
};