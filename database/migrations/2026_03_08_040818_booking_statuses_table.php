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
        Schema::create('booking_statuses', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('status'); // status varchar(255) NOT NULL
            $table->timestamps(); // created_at and updated_at
        });

        // Insert the booking statuses from your SQL dump
        DB::table('booking_statuses')->insert([
            ['id' => 1, 'status' => 'Waiting', 'created_at' => null, 'updated_at' => null],
            ['id' => 2, 'status' => 'Rejected', 'created_at' => null, 'updated_at' => null],
            ['id' => 3, 'status' => 'Accepted', 'created_at' => null, 'updated_at' => null],
            ['id' => 4, 'status' => 'Cancelled by user', 'created_at' => null, 'updated_at' => null],
            ['id' => 5, 'status' => 'Paied', 'created_at' => null, 'updated_at' => null],
            ['id' => 6, 'status' => 'Cancelled by provider', 'created_at' => null, 'updated_at' => null],
            ['id' => 7, 'status' => 'Booking Completed', 'created_at' => null, 'updated_at' => null],
            ['id' => 8, 'status' => 'Archived', 'created_at' => null, 'updated_at' => null],
            ['id' => 9, 'status' => 'Refunded', 'created_at' => null, 'updated_at' => null],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_statuses');
    }
};
