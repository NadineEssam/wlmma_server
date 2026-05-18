<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('zatca_certificates')) {
            Schema::create('zatca_certificates', function (Blueprint $table) {
                $table->id();
                $table->string('type', 20);
                $table->text('certificate');
                $table->text('private_key');
                $table->text('secret');
                $table->string('request_id')->nullable();
                $table->timestamp('issued_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->boolean('is_active')->default(1);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('zatca_certificates');
    }
};
