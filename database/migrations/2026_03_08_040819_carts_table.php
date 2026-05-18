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
        Schema::create('carts', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('user_id')->nullable(); // user_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('device_id', 250)->nullable(); // device_id varchar(250) DEFAULT NULL
            $table->decimal('old_price', 8, 2)->nullable(); // old_price decimal(8,2) DEFAULT NULL
            $table->decimal('total_price', 8, 2)->nullable(); // total_price decimal(8,2) DEFAULT NULL
            $table->timestamps(); // created_at and updated_at

            // Add index for user_id
            // $table->index('user_id');

            // Add foreign key constraint (optional but recommended)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
