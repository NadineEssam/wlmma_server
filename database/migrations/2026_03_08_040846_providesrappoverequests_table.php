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
        Schema::create('providesrappoverequests', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('customer_id')->nullable(); // customer_id bigint(20) UNSIGNED DEFAULT NULL
            $table->integer('approved')->default(0); // approved int(11) DEFAULT '0'
            $table->timestamps(); // created_at and updated_at

            // Add indexes
            $table->index('customer_id');
            $table->index('approved');

            // Add foreign key constraint (commented out until users table exists)
            // $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providesrappoverequests');
    }
};
