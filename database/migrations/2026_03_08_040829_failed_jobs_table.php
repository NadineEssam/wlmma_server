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
        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('uuid')->unique(); // uuid varchar(255) NOT NULL
            $table->text('connection'); // connection text NOT NULL
            $table->text('queue'); // queue text NOT NULL
            $table->longText('payload'); // payload longtext NOT NULL
            $table->longText('exception'); // exception longtext NOT NULL
            $table->timestamp('failed_at')->useCurrent(); // failed_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
    }
};
