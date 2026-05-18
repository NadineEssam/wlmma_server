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
        Schema::create('supplies_rents', function (Blueprint $table) {
            bigInteger('id', 11)->nullable();
            bigInteger('tool_id', 11)->nullable();
            bigInteger('supplier_id', 11)->nullable();
            bigInteger('renter_id', 11)->nullable();
            integer('quantity', 11)->nullable();
            decimal('price', 8, 2)->nullable();
            dateTime('created_at');
            dateTime('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies_rents');
    }
};