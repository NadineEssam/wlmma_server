<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // Add this line

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('commercial_tool_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en');
            $table->timestamps();
        });

        // Insert the tool types from your SQL dump
        DB::table('commercial_tool_types')->insert([
            [
                'id' => 1,
                'name_ar' => 'تأجير',
                'name_en' => 'For Rent',
                'created_at' => '2024-10-10 18:56:49',
                'updated_at' => '2024-10-10 18:56:49'
            ],
            [
                'id' => 2,
                'name_ar' => 'شراء',
                'name_en' => 'For Sale',
                'created_at' => '2024-10-10 18:56:49',
                'updated_at' => '2024-10-10 18:56:49'
            ],
            [
                'id' => 3,
                'name_ar' => 'ايجار لمزودين الخدمة',
                'name_en' => 'Rent for provider',
                'created_at' => '2025-10-09 18:56:49',
                'updated_at' => '2025-10-09 18:56:49'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_tool_types');
    }
};
