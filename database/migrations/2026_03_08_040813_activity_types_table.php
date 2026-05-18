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
            $table->id(); // This creates: id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('name_ar'); // name_ar varchar(255) NOT NULL
            $table->string('name_en'); // name_en varchar(255) NOT NULL
            $table->string('image'); // image varchar(255) NOT NULL
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_types');
    }
};
