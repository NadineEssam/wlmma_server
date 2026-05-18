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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();  // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('title_en');  // title_en varchar(255) NOT NULL
            $table->string('title_ar');  // title_ar varchar(255) NOT NULL
            $table->text('link')->nullable();  // link text DEFAULT NULL
            $table->string('image');  // image varchar(255) NOT NULL
            $table->timestamps();  // created_at and updated_at
        });

        // Insert the offers from your SQL dump
        DB::table('offers')->insert([
            [
                // 'id' => 3,
                'title_en' => 'Slider 3',
                'title_ar' => 'شريحة 3',
                'link' => 'https://www.google.com',
                'image' => 'https://wlmma-dev.fransa.in/storage/offers/68a614e38dfa3.jpg',
                'created_at' => '2025-08-06 19:56:32',
                'updated_at' => '2025-08-21 00:33:07'
            ],
            [
                // 'id' => 4,
                'title_en' => 'Slider 4ewqqqqqqqqqqq',
                'title_ar' => 'شريحة 4ewqqqqqqqqqqq',
                'link' => 'https://www.google.com',
                'image' => 'https://wlmma-dev.fransa.in/storage/offers/68a614d50b384.jpg',
                'created_at' => '2025-08-06 19:59:26',
                'updated_at' => '2025-08-21 00:32:53'
            ],
            [
                // 'id' => 6,
                'title_en' => 'Slider test',
                'title_ar' => 'شريحة اختبار',
                'link' => 'https://www.google.com',
                'image' => 'https://wlmma-dev.fransa.in/storage/offers/68a614c7871d7.jpg',
                'created_at' => '2025-08-10 20:54:25',
                'updated_at' => '2025-08-21 00:32:39'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
