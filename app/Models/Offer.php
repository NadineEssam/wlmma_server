<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasFactory;

    protected $table = 'offers';

    protected $fillable = [
        'title_ar',
        'title_en',
        'link',
        'image'
    ];

    // public function user()
    // {
    //     return $this->belongsTo(User::class);
    // }

    public function getImagePathAttribute($value)
    {
        // Manually prepend the URL with the desired structure
        return env('APP_URL') . 'storage/app/public/' . $value;
    }
}
