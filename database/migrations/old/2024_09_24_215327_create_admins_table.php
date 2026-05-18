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
        Schema::create('admins', callback: function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 64);
            $table->string('second_name', 64);
            $table->text('address')->nullable();
            $table->string('national_id', 14)->unique()->nullable();
            $table->string('phone_number', 20)->unique();
            $table->string('email')->unique()->nullable();
            $table->string('password');
            $table->boolean('is_authorized_by_manager')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};
