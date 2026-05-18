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
        Schema::create('activity_capacities', function (Blueprint $table) {
            bigInteger('id', 20)->nullable();
            bigInteger('activity_id', 20)->nullable();
            date('date')->nullable();
            string('day_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_capacities');
    }
};