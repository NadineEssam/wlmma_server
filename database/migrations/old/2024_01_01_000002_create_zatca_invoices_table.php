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
        if (!Schema::hasTable('zatca_invoices')) {
            Schema::create('zatca_invoices', function (Blueprint $table) {
                $table->id();
                $table->char('uuid', 36)->unique();
                $table->unsignedBigInteger('icv')->unique();
                $table->string('invoice_number')->index();
                $table->string('type', 30)->index();
                $table->string('subtype', 10);
                $table->string('hash', 64);
                $table->string('previous_hash', 64);
                $table->string('status', 20)->default('pending')->index();
                $table->longText('xml')->nullable();
                $table->longText('signed_xml')->nullable();
                $table->text('qr_code')->nullable();
                $table->longText('zatca_response')->nullable();
                $table->string('reference_id', 255)->nullable();
                $table->decimal('total_amount', 15, 2);
                $table->decimal('vat_amount', 15, 2);
                $table->string('invoiceable_type')->nullable()->index();
                $table->unsignedBigInteger('invoiceable_id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zatca_invoices_table');
    }
};
