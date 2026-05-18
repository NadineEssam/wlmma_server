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
        Schema::create('password_resets', function (Blueprint $table) {
            $table->id(); // id bigint(20) NOT NULL AUTO_INCREMENT
            $table->string('email')->nullable(); // email varchar(255) DEFAULT NULL
            $table->string('token')->nullable(); // token varchar(255) DEFAULT NULL
            $table->timestamp('created_at')->useCurrent(); // created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP

            // Add indexes for better performance
            $table->index('email');
            $table->index('token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_resets');
    }
};
