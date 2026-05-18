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
        Schema::create('bookings', function (Blueprint $table) {
            bigInteger('id', 20)->nullable();
            tinyInteger('is_returned', 1)->default('0');
            bigInteger('activity_id', 20)->nullable();
            integer('attendence', 11)->nullable()->default('0');
            text('tool_id');
            text('tool_capacity');
            decimal('total_price', 8, 2)->nullable();
            integer('capacity', 11)->nullable();
            string('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};