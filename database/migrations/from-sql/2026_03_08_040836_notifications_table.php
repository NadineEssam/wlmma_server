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
        Schema::create('notifications', function (Blueprint $table) {
            bigInteger('id', 20)->nullable();
            bigInteger('user_id', 20)->nullable();
            string('user_acting_as', 255)->nullable();
            string('title_en', 255)->nullable();
            string('title_ar', 255)->nullable();
            string('body_en', 255)->nullable();
            string('body_ar', 255)->nullable();
            string('type', 255)->nullable()->default('general');
            bigInteger('book_id', 20)->nullable();
            bigInteger('order_id', 20)->nullable();
            string('action', 255)->nullable();
            tinyInteger('is_seen', 1)->nullable()->default('0');
            timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};