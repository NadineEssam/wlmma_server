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
        Schema::create('activity_tools', function (Blueprint $table) {
            $table->id();  // This creates: id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('commercial_tool_id');  // commercial_tool_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('activity_id');  // activity_id bigint(20) UNSIGNED NOT NULL
            $table->timestamps();  // created_at and updated_at

            // Add indexes
            $table->index('activity_id');
            $table->index('commercial_tool_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_tools');
    }
};
