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
            $table->id(); // id bigint(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY
            $table->unsignedBigInteger('tool_id'); // tool_id bigint(11) UNSIGNED NOT NULL
            $table->unsignedBigInteger('supplier_id'); // supplier_id bigint(11) UNSIGNED NOT NULL
            $table->unsignedBigInteger('renter_id'); // renter_id bigint(11) UNSIGNED NOT NULL
            $table->integer('quantity'); // quantity int(11) NOT NULL
            $table->decimal('price', 8, 2)->nullable(); // price decimal(8,2) DEFAULT NULL
            $table->datetime('created_at')->nullable()->useCurrent(); // created_at datetime DEFAULT CURRENT_TIMESTAMP
            $table->datetime('updated_at')->nullable()->useCurrent(); // updated_at datetime DEFAULT CURRENT_TIMESTAMP

            // Add indexes for better performance
            $table->index('tool_id');
            $table->index('supplier_id');
            $table->index('renter_id');

            // Add foreign key constraints (commented out until related tables exist)
            // $table->foreign('tool_id')->references('id')->on('commercial_tools')->onDelete('cascade');
            // $table->foreign('supplier_id')->references('id')->on('users')->onDelete('cascade');
            // $table->foreign('renter_id')->references('id')->on('users')->onDelete('cascade');
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
