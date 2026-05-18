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
        // // Add ZATCA fields to orders table
        // Schema::table('orders', function (Blueprint $table) {
        //     $table->unsignedBigInteger('zatca_invoice_id')->nullable()->after('location');
        //     $table->string('zatca_status', 20)->nullable()->after('zatca_invoice_id');
        //     $table->text('zatca_qr_code')->nullable()->after('zatca_status');
        //     $table->string('invoice_number')->nullable()->after('zatca_qr_code');

        //     $table->foreign('zatca_invoice_id')
        //         ->references('id')
        //         ->on('zatca_invoices')
        //         ->onDelete('set null');

        //     $table->index('zatca_status');
        //     $table->index('invoice_number');
        // });

        // // Add ZATCA fields to bookings table
        // Schema::table('bookings', function (Blueprint $table) {
        //     $table->unsignedBigInteger('zatca_invoice_id')->nullable()->after('code');
        //     $table->string('zatca_status', 20)->nullable()->after('zatca_invoice_id');
        //     $table->text('zatca_qr_code')->nullable()->after('zatca_status');
        //     $table->string('invoice_number')->nullable()->after('zatca_qr_code');

        //     $table->foreign('zatca_invoice_id')
        //         ->references('id')
        //         ->on('zatca_invoices')
        //         ->onDelete('set null');

        //     $table->index('zatca_status');
        //     $table->index('invoice_number');
        // });
        if (!Schema::hasTable('orders')) {
            // Add ZATCA fields to orders table
            Schema::table('orders', function (Blueprint $table) {
                // إضافة الأعمدة بدون استخدام after() لتجنب مشاكل الأعمدة المفقودة
                if (!Schema::hasColumn('orders', 'zatca_invoice_id')) {
                    $table->unsignedBigInteger('zatca_invoice_id')->nullable();
                }
                if (!Schema::hasColumn('orders', 'zatca_status')) {
                    $table->string('zatca_status', 20)->nullable();
                }
                if (!Schema::hasColumn('orders', 'zatca_qr_code')) {
                    $table->text('zatca_qr_code')->nullable();
                }
                if (!Schema::hasColumn('orders', 'invoice_number')) {
                    $table->string('invoice_number')->nullable();
                }

                // إضافة الفوريجن كي فقط إذا الجدول موجود
                if (Schema::hasTable('zatca_invoices') && Schema::hasColumn('orders', 'zatca_invoice_id')) {
                    $table
                        ->foreign('zatca_invoice_id')
                        ->references('id')
                        ->on('zatca_invoices')
                        ->onDelete('set null');
                }

                // إضافة الـ indexes إذا الأعمدة موجودة
                if (Schema::hasColumn('orders', 'zatca_status')) {
                    $table->index('zatca_status');
                }
                if (Schema::hasColumn('orders', 'invoice_number')) {
                    $table->index('invoice_number');
                }
            });

            // Add ZATCA fields to bookings table
            Schema::table('bookings', function (Blueprint $table) {
                if (!Schema::hasColumn('bookings', 'zatca_invoice_id')) {
                    $table->unsignedBigInteger('zatca_invoice_id')->nullable();
                }
                if (!Schema::hasColumn('bookings', 'zatca_status')) {
                    $table->string('zatca_status', 20)->nullable();
                }
                if (!Schema::hasColumn('bookings', 'zatca_qr_code')) {
                    $table->text('zatca_qr_code')->nullable();
                }
                if (!Schema::hasColumn('bookings', 'invoice_number')) {
                    $table->string('invoice_number')->nullable();
                }

                if (Schema::hasTable('zatca_invoices') && Schema::hasColumn('bookings', 'zatca_invoice_id')) {
                    $table
                        ->foreign('zatca_invoice_id')
                        ->references('id')
                        ->on('zatca_invoices')
                        ->onDelete('set null');
                }

                if (Schema::hasColumn('bookings', 'zatca_status')) {
                    $table->index('zatca_status');
                }
                if (Schema::hasColumn('bookings', 'invoice_number')) {
                    $table->index('invoice_number');
                }
            });
        }
        // Add polymorphic columns to zatca_invoices for linking back
        if (Schema::hasTable('zatca_invoices')) {
            Schema::table('zatca_invoices', function (Blueprint $table) {
                if (!Schema::hasColumn('zatca_invoices', 'invoiceable_type')) {
                    $table->string('invoiceable_type')->nullable()->after('vat_amount');
                    $table->unsignedBigInteger('invoiceable_id')->nullable()->after('invoiceable_type');
                    $table->index(['invoiceable_type', 'invoiceable_id']);
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['zatca_invoice_id']);
            $table->dropIndex(['zatca_status']);
            $table->dropIndex(['invoice_number']);
            $table->dropColumn(['zatca_invoice_id', 'zatca_status', 'zatca_qr_code', 'invoice_number']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropForeign(['zatca_invoice_id']);
            $table->dropIndex(['zatca_status']);
            $table->dropIndex(['invoice_number']);
            $table->dropColumn(['zatca_invoice_id', 'zatca_status', 'zatca_qr_code', 'invoice_number']);
        });

        if (Schema::hasTable('zatca_invoices') && Schema::hasColumn('zatca_invoices', 'invoiceable_type')) {
            Schema::table('zatca_invoices', function (Blueprint $table) {
                $table->dropIndex(['invoiceable_type', 'invoiceable_id']);
                $table->dropColumn(['invoiceable_type', 'invoiceable_id']);
            });
        }
    }
};
