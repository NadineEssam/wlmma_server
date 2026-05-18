<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('google_id')->nullable()->index();
            $table->string('apple_id')->nullable()->index();

            $table->enum('acting_as', ['customer', 'provider'])->default('customer');

            $table->string('country_code')->nullable();
            $table->string('phone_number', 15)->nullable()->unique();

            $table->string('name')->nullable();
            $table->string('last_name')->nullable();

            $table->string('code')->nullable();

            $table->string('email')->nullable()->unique();

            $table->enum('is_approved_provider', ['no', 'yes'])->default('no');

            $table->enum('gender', ['male', 'female'])->nullable();

            $table->string('registration_id')->nullable();

            $table->string('password')->nullable();

            $table->text('fcm_token')->nullable();

            $table->string('location', 250)->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postcode')->nullable();

            $table
                ->unsignedBigInteger('user_types_id')
                ->nullable()
                ->default(1)
                ->comment('1 => user , 2 => company , 3 => individual-business');

            $table->string('iban', 34)->nullable();
            $table->integer('iban_image')->nullable();

            $table->string('trn', 50)->nullable();
            $table->integer('trn_image')->nullable();

            $table->string('cr', 50)->nullable();
            $table->integer('cr_image')->nullable();

            $table->integer('company_logo')->nullable();

            $table->string('national_id', 50)->nullable();
            $table->integer('national_id_image')->nullable();

            $table->integer('tour_guide')->nullable();

            $table->unsignedBigInteger('live_photo')->nullable()->index();

            $table->timestamps();

            // Foreign keys (اختياري لو الجداول موجودة)
            // $table->foreign('user_types_id')->references('id')->on('user_types');
            // $table->foreign('live_photo')->references('id')->on('files');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
