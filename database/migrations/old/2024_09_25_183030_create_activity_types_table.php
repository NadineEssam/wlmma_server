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
        Schema::create('activity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->timestamps();
        });

        DB::table('activity_types')->insert([
            [
                'id' => 1,
                'name_en' => 'Aerial',
                'name_ar' => 'جوي',
            ],
            [
                'id' => 2,
                'name_en' => 'Wild',
                'name_ar' => 'بري',
            ],
            [
                'id' => 3,
                'name_en' => 'Nautical',
                'name_ar' => 'بحري',
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
