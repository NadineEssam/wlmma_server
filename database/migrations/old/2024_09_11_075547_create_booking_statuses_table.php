<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('booking_statuses')) {
            Schema::create('booking_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('status');
                $table->timestamps();
            });
            DB::table('booking_statuses')->insert([
                [
                    'id' => 1,
                    'status' => 'Waiting',
                ],
                [
                    'id' => 2,
                    'status' => 'Cancelled',
                ],
                [
                    'id' => 3,
                    'status' => 'Accepted',
                ],
                [
                    'id' => 4,
                    'status' => 'Waiting for payment',
                ]
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_statuses');
    }
};
