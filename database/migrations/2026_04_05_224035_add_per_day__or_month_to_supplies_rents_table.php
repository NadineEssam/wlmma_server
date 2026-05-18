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
        Schema::table('supplies_rents', function (Blueprint $table) {
            $table->string('per_day_Or_month')->nullable()->after('price')->default('day');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplies_rents', function (Blueprint $table) {
            $table->dropColumn('per_day_Or_month');
        });
    }
};
