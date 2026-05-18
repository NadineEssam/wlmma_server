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
        Schema::create('activity_statuses', function (Blueprint $table) {
            $table->id();  // This creates: id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('status');  // status varchar(255) NOT NULL
            $table->timestamps();  // created_at and updated_at
        });

        DB::table('activity_statuses')->insert([
            [
                'status' => 'Active',
                'created_at' => '2025-08-06 19:56:32',
                'updated_at' => '2025-08-21 00:33:07'
            ],
            [
                'status' => 'Not Active',
                'created_at' => '2025-08-06 19:56:32',
                'updated_at' => '2025-08-21 00:33:07'
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_statuses');
    }
};
