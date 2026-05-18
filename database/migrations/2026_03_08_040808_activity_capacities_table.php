<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_capacities', function (Blueprint $table) {
            $table->id();  // This creates: id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('activity_id')->nullable();  // activity_id bigint(20) UNSIGNED DEFAULT NULL
            $table->date('date')->nullable();  // date date DEFAULT NULL
            $table->string('day_name', 255)->nullable();  // day_name varchar(255) DEFAULT NULL
            $table->bigInteger('capacity')->nullable();  // capacity bigint(20) DEFAULT NULL
            $table->timestamp('created_at')->nullable()->useCurrent();  // created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP
            $table->timestamp('updated_at')->nullable()->useCurrent();  // updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP

            // Add index for better performance
            $table->index('activity_id');

            // Add foreign key constraint (optional but recommended)
            // $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_capacities');
    }
};
