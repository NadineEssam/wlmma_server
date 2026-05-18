<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    // Define the table name (optional if it matches the plural of the model name)
    protected $table = 'ratings';

    // Mass assignable attributes
    protected $fillable = [
        'user_id',
        'activity_id',
        'tool_id',
        'rating',
        'comment',
        'user_email',
        'user_name',
    ];

    // Define relationships

    /**
     * The user who left the rating (if any).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The activity that the rating is associated with.
     */
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
