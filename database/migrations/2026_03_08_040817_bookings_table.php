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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_returned')->default(0);
            $table->unsignedBigInteger('activity_id');
            $table->integer('attendence')->default(0);
            $table->text('tool_id')->nullable();
            $table->text('tool_capacity')->nullable();
            $table->decimal('total_price', 8, 2);
            $table->integer('capacity');
            $table->string('date');
            $table->string('time')->nullable();
            $table->string('user_name');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('phone_number', 15);
            $table->string('photographer')->nullable()->comment('0 => NO , 1 => Yes');
            $table->string('tour_guide')->nullable()->comment('0 => NO , 1 => Yes');
            $table->unsignedBigInteger('status_id')->default(1)->comment('1 => Waiting , 2 => Rejected, 3 => Accepted , 4 => Cancelled by user , 5 => Paied , 6 => Cancelled by provider , 7 => Booking Completed , 8 => Archived');
            $table->boolean('is_paied')->default(0);
            $table->integer('code');
            $table->unsignedBigInteger('zatca_invoice_id')->nullable();
            $table->string('zatca_status', 20)->nullable();
            $table->text('zatca_qr_code')->nullable();
            $table->string('invoice_number')->nullable();
            $table->timestamps();

            // Add indexes only (remove foreign key constraints for now)
            $table->index('activity_id');
            $table->index('status_id');
            $table->index('user_id');
            $table->index('zatca_invoice_id');
            $table->index('zatca_status');
            $table->index('invoice_number');

            // Comment out foreign key constraints temporarily
            // $table->foreign('activity_id')->references('id')->on('activities')->onDelete('cascade');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('status_id')->references('id')->on('booking_statuses')->onDelete('cascade');
            // $table->foreign('zatca_invoice_id')->references('id')->on('zatca_invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
