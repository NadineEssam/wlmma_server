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
        Schema::create('user_types', function (Blueprint $table) {
            $table->id();  // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('type');  // type varchar(255) NOT NULL
            $table->timestamps();  // created_at and updated_at
        });

        // Insert default user types
        DB::table('user_types')->insert([
            [
                'id' => 1,
                'type' => 'user',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 2,
                'type' => 'company',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'id' => 3,
                'type' => 'individual-business',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_types');
    }
};
