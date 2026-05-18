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
        Schema::create('commercial_too_attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tool_id')->constrained('commercial_tools');
            $table->string('attribute_name_ar');
            $table->string('attribute_name_en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_too_attributes');
    }
};
