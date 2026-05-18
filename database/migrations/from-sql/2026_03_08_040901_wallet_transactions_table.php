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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            integer('id', 11)->nullable();
            bigInteger('wallet_id', 20)->nullable();
            bigInteger('user_id', 20)->nullable();
            bigInteger('cart_id', 20)->nullable();
            bigInteger('order_id', 20)->nullable();
            bigInteger('book_id', 20)->nullable();
            bigInteger('provider_id', 20)->nullable();
            string('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};