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
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('user_id'); // user_id bigint(20) UNSIGNED NOT NULL
            $table->string('user_acting_as', 255)->nullable(); // user_acting_as varchar(255) DEFAULT NULL
            $table->string('title_en', 255)->nullable(); // title_en varchar(255) DEFAULT NULL
            $table->string('title_ar', 255)->nullable(); // title_ar varchar(255) DEFAULT NULL
            $table->string('body_en', 255)->nullable(); // body_en varchar(255) DEFAULT NULL
            $table->string('body_ar', 255)->nullable(); // body_ar varchar(255) DEFAULT NULL
            $table->string('type')->default('general'); // type varchar(255) NOT NULL DEFAULT 'general'
            $table->unsignedBigInteger('book_id')->nullable(); // book_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('order_id')->nullable(); // order_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('action')->nullable(); // action varchar(255) DEFAULT NULL
            $table->boolean('is_seen')->default(0); // is_seen tinyint(1) NOT NULL DEFAULT '0'
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('user_id');
            // $table->index('book_id');
            // $table->index('order_id');
            // $table->index('type');
            // $table->index('is_seen');

            // Add foreign key constraints (commented out until related tables exist)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('book_id')->references('id')->on('bookings')->onDelete('set null');
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
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
