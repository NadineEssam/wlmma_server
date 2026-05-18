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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();  // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->unsignedBigInteger('user_id')->nullable();  // user_id bigint(20) UNSIGNED DEFAULT NULL
            $table->decimal('old_price', 8, 2)->nullable();  // old_price decimal(8,2) DEFAULT NULL
            $table->decimal('total', 10, 2);  // total decimal(10,2) NOT NULL
            $table->string('status')->default('pending');  // status varchar(255) NOT NULL DEFAULT 'pending'
            $table->boolean('is_paid')->default(0);  // is_paid tinyint(1) NOT NULL DEFAULT '0'
            $table->boolean('is_returned')->default(0);  // is_returned tinyint(1) NOT NULL DEFAULT '0'
            $table->text('additional_details')->nullable();  // additional_details text DEFAULT NULL
            $table->decimal('lat', 9, 6)->default(0.0);  // lat decimal(9,6) DEFAULT '0.000000'
            $table->decimal('long', 9, 6)->default(0.0);  // long decimal(9,6) DEFAULT '0.000000'
            $table->string('location')->nullable();  // location varchar(255) DEFAULT NULL
            $table->unsignedBigInteger('zatca_invoice_id')->nullable();  // zatca_invoice_id bigint(20) UNSIGNED DEFAULT NULL
            $table->string('zatca_status', 20)->nullable();  // zatca_status varchar(20) DEFAULT NULL
            $table->text('zatca_qr_code')->nullable();  // zatca_qr_code text DEFAULT NULL
            $table->string('invoice_number')->nullable();  // invoice_number varchar(255) DEFAULT NULL

            $table->timestamps();  // created_at and updated_at

            // Add indexes for better performance
            $table->index('user_id');
            $table->index('status');
            $table->index('is_paid');
            $table->index('zatca_invoice_id');

            // Add foreign key constraints (commented out until related tables exist)
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            // $table->foreign('zatca_invoice_id')->references('id')->on('zatca_invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
