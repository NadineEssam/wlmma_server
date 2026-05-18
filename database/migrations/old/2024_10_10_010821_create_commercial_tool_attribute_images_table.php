<?php

use App\Models\CommercialTool;
use App\Models\Image;
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
        Schema::create('commercial_tool_images', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Image::class,'image_id');
            $table->foreignIdFor(CommercialTool::class,'commercial_tool_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commercial_tool_attribute_images');
    }
};
