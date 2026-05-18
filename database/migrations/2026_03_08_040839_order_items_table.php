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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('order_id'); // order_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('tool_id'); // tool_id bigint(20) UNSIGNED NOT NULL
            $table->integer('quantity'); // quantity int(11) NOT NULL
            $table->decimal('price', 10, 2); // price decimal(10,2) NOT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('order_id');
            $table->index('tool_id');

            // Add foreign key constraints (commented out until related tables exist)
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            // $table->foreign('tool_id')->references('id')->on('commercial_tools')->onDelete('cascade');

            // Add unique constraint to prevent duplicate items in the same order
            $table->unique(['order_id', 'tool_id'], 'order_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
