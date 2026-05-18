<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds payment and refund tracking fields to orders table to enable:
     * - Tracking paid orders that are cancelled
     * - Automatic wallet refund processing
     * - ZATCA credit note generation for cancelled orders
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Payment tracking (similar to bookings.is_paied)
            $table->boolean('is_paid')->default(false)->after('status');

            // Refund tracking (similar to bookings.is_returned)
            $table->boolean('is_returned')->default(false)->after('is_paid');

            // Add indexes for efficient querying in scheduled commands
            $table->index(['status', 'is_paid', 'is_returned']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['status', 'is_paid', 'is_returned']);
            $table->dropColumn(['is_paid', 'is_returned']);
        });
    }
};
