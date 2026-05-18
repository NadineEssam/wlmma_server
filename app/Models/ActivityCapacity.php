<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityCapacity extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'date',
        'day_name',
        'capacity',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }
}
