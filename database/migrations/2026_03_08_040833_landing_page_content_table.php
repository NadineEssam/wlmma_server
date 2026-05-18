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
        Schema::create('landing_page_content', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('page_id'); // page_id varchar(255) NOT NULL
            $table->json('content'); // content json NOT NULL
            $table->timestamps(); // created_at and updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('landing_page_content');
    }
};
