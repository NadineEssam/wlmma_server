<?php

use App\Models\Activity;
use App\Models\Image;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('tool_images')) {
            Schema::create('tool_images', function (Blueprint $table) {
                $table->id();
                $table->foreignIdFor(Image::class, 'image_id');
                $table->foreignIdFor(Activity::class, 'tool_id');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tool_images');
    }
};
