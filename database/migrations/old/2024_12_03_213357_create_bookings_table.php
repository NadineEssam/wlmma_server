<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     /**
//      * Run the migrations.
//      */
//     public function up(): void
//     {
//         Schema::create('bookings', function (Blueprint $table) {
//             $table->id();
//             $table->foreignId('activity_id')->constrained('activities');
//             $table->integer('capacity');
//             $table->date('date');
//             $table->time('time');
//             $table->string('user_name');
//             $table->integer('user_id')->nullable();
//             $table->string('phone_number', 15);//->index()->unique(); maykrarash tani fi el table kolo
//             $table->string('photographer')->nullable();
//             $table->timestamps();
//         });
//     }

//     /**
//      * Reverse the migrations.
//      */
//     public function down(): void
//     {
//         Schema::dropIfExists('bookings');
//     }
// };

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('activity_id')->constrained('activities');
                $table->integer('tool_id')->nullable();
                $table->string('total_price');
                $table->integer('capacity');
                $table->date('date');
                $table->time('time');
                $table->string('user_name');
                $table->integer('user_id')->nullable();
                $table->string('phone_number', 15);  // ->index()->unique(); maykrarash tani fi el table kolo
                $table->string('photographer')->nullable();
                $table->string('tour_guide')->nullable();
                $table->foreignId('status_id')->comment('1 => Waiting, 2 => Cancelled, 3 => Accepted, 4 => Waiting for payment')->constrained('activity_statuses');
                $table->integer('code');
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
