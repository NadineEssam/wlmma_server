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
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn('location');
            $table->string('city_name_en');
            $table->string('city_name_ar');
            $table->decimal('lat',9,6);
            $table->decimal('long',9,6);
            $table->boolean('is_tourguideable');
            $table->dropColumn('description');
            $table->string('description_en');
            $table->string('description_ar');
            $table->dropColumn('title');
            $table->string('title_en');
            $table->string('title_ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            //
        });
    }
};
