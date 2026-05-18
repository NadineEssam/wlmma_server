<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityType extends Model
{
    use HasFactory;
    protected $table = 'activity_types';
    protected $fillable = [
        'name_en',
        'name_ar',
        'image',

    ];
    public function activities()
    {
        return $this->hasMany(Activity::class, 'activity_type_id');
    }
}
