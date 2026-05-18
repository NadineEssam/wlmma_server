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
        Schema::create('files', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('name', 128); // name varchar(128) NOT NULL
            $table->string('mime_type', 32); // mime_type varchar(32) NOT NULL
            $table->string('path', 255); // path varchar(255) NOT NULL
            $table->string('extension', 12); // extension varchar(12) NOT NULL
            $table->integer('size'); // size int(11) NOT NULL
            $table->timestamps(); // created_at and updated_at
            $table->unsignedBigInteger('user_id')->nullable(); // user_id bigint(20) UNSIGNED DEFAULT NULL

            // Add indexes
            $table->index('user_id');

            // Add foreign key constraint (commented out until users table exists)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
