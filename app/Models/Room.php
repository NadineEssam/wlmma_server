<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;
    protected $fillable = ['room_id','user_id','user_type'];

    // If you want to track when a room is created or updated
    protected $dates = ['created_at', 'updated_at'];
}
