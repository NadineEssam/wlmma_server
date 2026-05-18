<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityPlan extends Model
{
    use HasFactory;
    protected $fillable = [
        'activity_id',
        'city_name_en',
        'city_name_ar',
        'starts_at',
        'ends_at',
        'dates',
    ];
}
