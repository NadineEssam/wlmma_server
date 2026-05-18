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
        Schema::create('cashbacks', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->enum('campaign', ['yes', 'no'])->default('no'); // campaign enum('yes','no') NOT NULL DEFAULT 'no'
            $table->decimal('campaign_amount', 10, 2)->nullable(); // campaign_amount decimal(10,2) DEFAULT NULL
            $table->decimal('order_spent', 10, 2)->nullable(); // order_spent decimal(10,2) DEFAULT NULL
            $table->decimal('order_cashback', 10, 2)->nullable(); // order_cashback decimal(10,2) DEFAULT NULL
            $table->decimal('code_cashback', 10, 2)->nullable(); // code_cashback decimal(10,2) DEFAULT NULL
            $table->timestamps(); // created_at and updated_at
        });

        // Insert the cashback settings from your SQL dump
        DB::table('cashbacks')->insert([
            [
                'id' => 1,
                'campaign' => 'yes',
                'campaign_amount' => 80.00,
                'order_spent' => 4.00,
                'order_cashback' => 8.00,
                'code_cashback' => 2.00,
                'created_at' => '2025-10-16 13:15:51',
                'updated_at' => '2025-10-16 19:15:51'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbacks');
    }
};
