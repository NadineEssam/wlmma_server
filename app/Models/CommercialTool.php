<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_en',
        'name_ar',
        'description_en',
        'description_ar',
        'user_id',
        'type_id',
        'price'
    ];

    public function commercialAttribute()
    {
        return $this->hasOne(CommercialTooAttribute::class, 'tool_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function type()
    {
        return $this->belongsTo(CommercialToolType::class);
    }

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
            // $image->image_path = env('APP_URL') . '/storage/app/public/commercialtools/' . basename($image->image_path);
            $image->image_path = env('APP_URL') . 'storage/app/public/commercialtools/' . basename($image->image_path);
        }

        return $images;
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'tool_id');
    }
}
