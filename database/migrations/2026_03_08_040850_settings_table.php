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
        Schema::create('settings', function (Blueprint $table) {
            $table->id(); // id int(11) NOT NULL AUTO_INCREMENT
            $table->string('name_en'); // name_en varchar(255) NOT NULL
            $table->string('name_ar')->nullable(); // name_ar varchar(255) DEFAULT NULL
            $table->string('key')->unique(); // setting key
            $table->text('value')->nullable(); // setting value
            $table->string('type')->default('text'); // text, textarea, image, etc.
            $table->integer('sort_order')->default(0);
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
