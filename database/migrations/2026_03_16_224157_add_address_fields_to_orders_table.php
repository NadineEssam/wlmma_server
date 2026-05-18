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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('shortNationalAddress', 255)->nullable();  // shortNationalAddress varchar(255) DEFAULT NULL
            $table->string('province', 255)->nullable();  // province varchar(255) DEFAULT NULL
            $table->string('cityGovernorate', 255)->nullable();  // cityGovernorate varchar(255) DEFAULT NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'shortNationalAddress',
                'province',
                'cityGovernorate'
            ]);
        });
    }
};
