<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WaitingList extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'activity_id',
        'phone_number',
        'position',
        'status',
    ];

    // Define relationship to User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Define relationship to Activity model
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
