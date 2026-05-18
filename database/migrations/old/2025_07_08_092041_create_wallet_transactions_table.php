<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('wallet_transactions')) {
            Schema::create('wallet_transactions', function (Blueprint $table) {
                $table->id();
                // $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
                $table->unsignedBigInteger('wallet_id');
                $table->foreignId('user_id')->constrained()->onDelete('cascade');
                $table->enum('type', ['credit', 'debit']);
                $table->decimal('amount', 10, 2);
                $table->string('reference')->nullable();  // merchantTransactionId or booking_id
                $table->string('reason')->nullable();  // e.g. "Booking #123", "Top-up", "Refund"
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
