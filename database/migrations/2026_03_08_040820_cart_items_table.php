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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('cart_id'); // cart_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('tool_id'); // tool_id bigint(20) UNSIGNED NOT NULL
            // $table->bigInteger('attribute_id')->nullable(); // attribute_id bigint(20) DEFAULT NULL
            $table->integer('quantity'); // quantity int(11) NOT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('cart_id');
            // $table->index('tool_id');
            // $table->index('attribute_id');

            // Add foreign key constraints
            $table->foreign('cart_id')->references('id')->on('carts')->onDelete('cascade');
            $table->foreign('tool_id')->references('id')->on('commercial_tools')->onDelete('cascade');
            // $table->foreign('attribute_id')->references('id')->on('commercial_too_attributes')->onDelete('set null');

            // Add unique constraint to prevent duplicate items in the same cart
            $table->unique(['cart_id', 'tool_id'], 'cart_items_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
