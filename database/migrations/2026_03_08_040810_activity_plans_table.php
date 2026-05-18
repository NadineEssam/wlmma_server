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
        Schema::create('activity_plans', function (Blueprint $table) {
            $table->id();  // This creates: id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('activity_id');  // activity_id bigint(20) UNSIGNED NOT NULL
            $table->string('city_name_en');  // city_name_en varchar(255) NOT NULL
            $table->string('city_name_ar')->nullable();  // city_name_ar varchar(255) DEFAULT NULL
            $table->string('starts_at');  // starts_at varchar(255) NOT NULL
            $table->string('ends_at');  // ends_at varchar(255) NOT NULL
            $table->text('dates');  // dates text NOT NULL
            $table->timestamps();  // created_at and updated_at

            // Add indexes
            $table->index('activity_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_plans');
    }
};
