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
        Schema::create('migrations_old', function (Blueprint $table) {
            $table->id(); // id int(10) unsigned NOT NULL AUTO_INCREMENT
            $table->string('migration'); // migration varchar(255) NOT NULL
            $table->integer('batch'); // batch int(11) NOT NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('migrations_old');
    }
};
