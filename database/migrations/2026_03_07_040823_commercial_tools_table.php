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
        Schema::create('commercial_tools', function (Blueprint $table) {
            $table->id();  // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('name_en');  // name_en varchar(255) NOT NULL
            $table->string('name_ar')->nullable();  // name_ar varchar(255) DEFAULT NULL
            $table->text('description_en');  // description_en text NOT NULL
            $table->text('description_ar')->nullable();  // description_ar text DEFAULT NULL
            $table->unsignedBigInteger('type_id')->nullable()->comment('1 -> Rent , 2 -> Sale , 3 -> Rent for provider ');  // type_id bigint(20) UNSIGNED DEFAULT NULL
            $table->decimal('price', 8, 2)->nullable();  // price decimal(8,2) DEFAULT NULL
            $table->timestamps();  // created_at and updated_at
            $table->unsignedBigInteger('user_id');  // user_id bigint(20) UNSIGNED NOT NULL

            // Add indexes
            // $table->index('type_id');
            // $table->index('user_id');

            // Add foreign key constraints (commented out until related tables exist)
            // $table->foreign('type_id')->references('id')->on('commercial_tool_types')->onDelete('set null');
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Insert the commercial_tools from your SQL dump
        // DB::table('commercial_tools')->insert([
        //     [
        //         'name_en' => 'Camping Tent',
        //         'name_ar' => 'خيمة تخييم',
        //         'description_en' => 'Large waterproof tent suitable for 4 people.',
        //         'description_ar' => 'خيمة كبيرة مقاومة للماء تصلح لأربعة أشخاص.',
        //         'type_id' => 1,  // Rent
        //         'price' => 50.0,
        //         'user_id' => 1,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name_en' => 'Sleeping Bag',
        //         'name_ar' => 'كيس نوم',
        //         'description_en' => 'Warm sleeping bag for cold nights.',
        //         'description_ar' => 'كيس نوم دافئ للليالي الباردة.',
        //         'type_id' => 2,  // Sale
        //         'price' => 30.0,
        //         'user_id' => 1,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        //     [
        //         'name_en' => 'Portable Stove',
        //         'name_ar' => 'موقد متنقل',
        //         'description_en' => 'Compact gas stove for camping trips.',
        //         'description_ar' => 'موقد غازي صغير للرحلات التخييمية.',
        //         'type_id' => 1,  // Rent
        //         'price' => 20.0,
        //         'user_id' => 2,
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ],
        // ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_tools');
    }
};
