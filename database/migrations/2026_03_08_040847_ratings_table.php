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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('user_id')->nullable(); // user_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('activity_id')->nullable(); // activity_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('tool_id')->nullable(); // tool_id bigint(20) UNSIGNED DEFAULT NULL
            $table->tinyInteger('rating')->nullable()->comment('Rating out of 5'); // rating tinyint(3) DEFAULT NULL
            $table->text('comment')->nullable()->comment('Optional feedback'); // comment text DEFAULT NULL
            $table->string('user_email'); // user_email varchar(255) NOT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('user_id');
            $table->index('activity_id');
            $table->index('tool_id');
            $table->index('rating');

            // Add foreign key constraints (commented out until related tables exist)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            // $table->foreign('tool_id')->references('id')->on('commercial_tools')->onDelete('cascade');

            // Add check constraint to ensure rating is between 1 and 5 (optional, database-specific)
            // $table->check('rating >= 1 AND rating <= 5');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
