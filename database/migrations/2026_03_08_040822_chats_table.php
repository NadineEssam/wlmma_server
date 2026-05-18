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
        Schema::create('chats', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->text('room_id'); // room_id text NOT NULL
            $table->text('message_id')->nullable(); // message_id text DEFAULT NULL
            $table->unsignedBigInteger('receiver_id')->nullable(); // receiver_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('receiver_type', 255)->nullable(); // receiver_type varchar(255) DEFAULT NULL
            $table->unsignedBigInteger('sender_id')->nullable(); // sender_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('user_type_sender', 255)->nullable(); // user_type_sender varchar(255) DEFAULT NULL
            $table->string('sender_type', 255)->nullable(); // sender_type varchar(255) DEFAULT NULL
            $table->timestamp('created_at')->nullable()->useCurrent(); // created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP
            $table->timestamp('updated_at')->nullable(); // updated_at timestamp NULL DEFAULT NULL

            // Add indexes for better performance
            // $table->index('receiver_id');
            // $table->index('sender_id');
            // $table->index('receiver_type');
            // $table->index('user_type_sender');
            // $table->index('sender_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
