<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
                $table->id();  // Creates an unsigned bigint as the primary key
                $table->unsignedBigInteger('user_id')->nullable();  // For authenticated users
                $table->decimal('total', 10, 2);
                $table->string('status')->default('pending');  // Order status (e.g., pending, completed)
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
