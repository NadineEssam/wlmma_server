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
        Schema::create('tools', function (Blueprint $table) {
            $table->id(); // id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT
            $table->string('name_en'); // name_en varchar(255) NOT NULL
            $table->string('name_ar')->nullable(); // name_ar varchar(255) DEFAULT NULL
            $table->text('description_en')->nullable(); // description_en text DEFAULT NULL
            $table->text('description_ar')->nullable(); // description_ar text DEFAULT NULL
            $table->unsignedBigInteger('type_id')->nullable(); // type_id bigint(20) UNSIGNED DEFAULT NULL
            $table->decimal('price', 8, 2)->nullable(); // price decimal(8,2) DEFAULT NULL
            $table->unsignedBigInteger('user_id'); // user_id bigint(20) UNSIGNED NOT NULL
            $table->timestamps(); // created_at and updated_at

            // Add indexes
            $table->index('type_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tools');
    }
};
