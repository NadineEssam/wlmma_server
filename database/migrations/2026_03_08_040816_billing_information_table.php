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
        Schema::create('billing_information', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('payment_checkout_id')->nullable(); // payment_checkout_id varchar(255) DEFAULT NULL
            $table->string('entity_id')->nullable(); // entity_id varchar(255) DEFAULT NULL
            $table->bigInteger('card_number')->nullable(); // card_number bigint(255) DEFAULT NULL
            $table->string('first_name')->nullable(); // first_name varchar(255) DEFAULT NULL
            $table->string('last_name')->nullable(); // last_name varchar(255) DEFAULT NULL
            $table->decimal('amount', 8, 2)->nullable(); // amount decimal(8,2) DEFAULT NULL
            $table->string('payment_method')->nullable(); // payment_method varchar(255) DEFAULT NULL
            $table->string('referencedId')->nullable(); // referencedId varchar(255) DEFAULT NULL
            $table->string('payment_id')->nullable(); // payment_id varchar(255) DEFAULT NULL
            $table->string('registration_id')->nullable(); // registration_id varchar(255) DEFAULT NULL
            $table->string('street')->nullable(); // street varchar(255) DEFAULT NULL
            $table->string('city')->nullable(); // city varchar(255) DEFAULT NULL
            $table->string('state')->nullable(); // state varchar(255) DEFAULT NULL
            $table->string('country')->nullable(); // country varchar(255) DEFAULT NULL
            $table->string('postcode')->nullable(); // postcode varchar(255) DEFAULT NULL
            $table->string('email')->nullable(); // email varchar(255) DEFAULT NULL
            $table->unsignedBigInteger('user_id')->nullable(); // user_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('order_id')->nullable(); // order_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('cart_id')->nullable(); // cart_id bigint(20) UNSIGNED DEFAULT NULL
            $table->unsignedBigInteger('book_id')->nullable(); // book_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('describtion')->nullable(); // describtion varchar(255) DEFAULT NULL
            $table->string('payment_status')->nullable(); // payment_status varchar(255) DEFAULT NULL
            $table->decimal('wallet_amount', 10, 2)->nullable(); // wallet_amount decimal(10,2) DEFAULT NULL
            $table->decimal('card_amount', 10, 2)->nullable(); // card_amount decimal(10,2) DEFAULT NULL
            $table->timestamps(); // created_at and updated_at
            $table->string('iban')->nullable(); // iban varchar(255) DEFAULT NULL

            // Add indexes for foreign keys
            $table->index('user_id');
            // $table->index('order_id');
            $table->index('cart_id');
            $table->index('book_id');

            // Add foreign key constraints (optional but recommended)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            // $table->foreign('cart_id')->references('id')->on('carts')->onDelete('set null');
            // $table->foreign('book_id')->references('id')->on('bookings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('billing_information');
    }
};
