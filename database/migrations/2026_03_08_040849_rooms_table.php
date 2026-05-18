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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->text('room_id')->nullable(); // room_id text DEFAULT NULL
            $table->unsignedBigInteger('user_id')->nullable(); // user_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('user_type', 255)->nullable(); // user_type varchar(255) DEFAULT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('user_id');
            $table->index('user_type');

            // Add foreign key constraint (commented out until users table exists)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Add unique constraint for room_id if needed
            // $table->unique('room_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
