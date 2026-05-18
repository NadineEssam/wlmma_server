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
        Schema::create('ratings', function (Blueprint $table) {
            bigInteger('id', 20)->nullable();
            bigInteger('user_id', 20)->nullable();
            bigInteger('activity_id', 20)->nullable();
            bigInteger('tool_id', 20)->nullable();
            tinyInteger('rating', 3)->nullable()->comment('Rating out of 5');
            text('comment')->comment('Optional feedback');
            string('user_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};