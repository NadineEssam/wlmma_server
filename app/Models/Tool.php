<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tool extends Model
{
    use HasFactory;
    protected $fillable = [
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'activity_id',
        'price'
    ];
    public function ToolImages()
    {
        return $this->belongsToMany(Image::class, 'tool_images', 'tool_id', 'image_id');
    }

    // public function getImagesAttribute()
    // {
    //     $images = $this->ActivityImages;

    //     foreach ($images as &$image) {
    //         $image->image_path = env('APP_URL') . '/storage/' . $image->image_path;
    //     }
    //     return $images;
    // }
    public function getImagePathAttribute($value)
    {
        // Manually prepend the URL with the desired structure
        return env('APP_URL') . '/storage/app/public/' . $value;
    }
    
    
    public function toolAttribute()
    {
        return $this->hasOne(ToolAttribute::class, 'tool_id');
    }
}
