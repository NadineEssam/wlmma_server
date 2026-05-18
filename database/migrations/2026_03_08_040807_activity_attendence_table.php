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
        Schema::create('activity_attendence', function (Blueprint $table) {
            $table->id(); // This matches: id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->integer('attendence'); // attendence int(11) NOT NULL
            $table->unsignedBigInteger('user_id'); // user_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('activity_id'); // activity_id bigint(20) UNSIGNED NOT NULL
            $table->timestamp('created_at')->useCurrent(); // created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate(); // updated_at timestamp NOT NULL DEFAULT '0000-00-00 00:00:00'

            // Add indexes for better performance
            $table->index('user_id');
            $table->index('activity_id');

            // Add foreign key constraints (optional but recommended)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_attendence');
    }
};
