<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUserDetailsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('iban', 34)->nullable(); // Replace 'column_name' with the column after which you want to add IBAN
            $table->string('trn', 50)->nullable()->after('iban'); // Tax Registration Number
            $table->string('cr', 50)->nullable()->after('trn'); // Commercial Registration
            $table->string('national_id', 50)->nullable()->after('cr'); // Identification Number for individuals
            $table->unsignedBigInteger('live_photo')->nullable()->after('national_id'); // Live Photo URL or path
            $table->foreign('live_photo')->references('id')->on('files');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['iban', 'trn', 'cr', 'national_id', 'live_photo']);
        });
    }
}
