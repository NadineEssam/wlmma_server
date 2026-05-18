<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityAttendence extends Model
{
    use HasFactory;
    protected $table = 'activity_attendence';
    protected $fillable = [
        'attendence',
        'user_id',
        'activity_id',

    ];
    public function activity()
    {
        return $this->belongs(Activity::class);
    }

    public function user()
    {
        return $this->belongs(Activity::class);
    }
}
