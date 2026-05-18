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
        Schema::create('otps', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('user_id')->nullable(); // user_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('phone_number', 15)->nullable(); // phone_number varchar(15) DEFAULT NULL
            $table->string('email')->nullable(); // email varchar(255) DEFAULT NULL
            $table->string('otp', 6); // otp varchar(6) NOT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('user_id');
            $table->index('phone_number');
            $table->index('email');
            $table->index('otp');

            // Add foreign key constraint (commented out until users table exists)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otps');
    }
};
