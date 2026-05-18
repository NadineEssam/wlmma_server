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
            $table->id(); // id int(11) NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('wallet_id'); // wallet_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('user_id'); // user_id bigint(20) UNSIGNED NOT NULL
            $table->unsignedBigInteger('cart_id')->nullable(); // cart_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('order_id')->nullable(); // order_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('book_id')->nullable(); // book_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('provider_id')->nullable(); // provider_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('type'); // type varchar(255) NOT NULL
            $table->decimal('amount', 10, 2); // amount decimal(10,2) NOT NULL
            $table->string('reference')->nullable(); // reference varchar(255) DEFAULT NULL
            $table->string('reason')->nullable(); // reason varchar(255) DEFAULT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes for better performance
            $table->index('wallet_id');
            $table->index('user_id');
            $table->index('cart_id');
            $table->index('order_id');
            $table->index('book_id');

            // Add foreign key constraints (commented out until related tables exist)
            $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('cart_id')->references('id')->on('carts')->onDelete('set null');
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            // $table->foreign('book_id')->references('id')->on('bookings')->onDelete('set null');
            // $table->foreign('provider_id')->references('id')->on('users')->onDelete('set null');
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
