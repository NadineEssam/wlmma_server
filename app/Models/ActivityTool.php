<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'commercial_tool_id',
        'activity_id',
    ];


    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function commercialTool()
    {
        return $this->belongsTo(CommercialTool::class, 'commercial_tool_id');
    }


    // public function type()
    // {
    //     return $this->belongsTo(CommercialToolType::class);
    // }

    public function toolImages()
    {
        return $this->belongsToMany(Image::class, 'commercial_tool_images', 'commercial_tool_id', 'image_id');
    }

    // public function getImagesAttribute()
    // {
    //     $images = $this->toolImages;

    //     foreach ($images as &$image) {
    //         $image->image_path = env('APP_URL') . '/storage/app/public/' . $image->image_path;
    //     }
    //     return $images;
    // }



    public function getImagesAttribute()
    {
        $images = $this->toolImages;

        foreach ($images as &$image) {
            // $image->image_path = env('APP_URL') . '/api/storage/app/public/commercialtools/' . basename($image->image_path);
            $image->image_path = env('APP_URL') . 'storage/app/public/commercialtools/' . basename($image->image_path);
        }

        return $images;
    }
}
