<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'message_id',
        'receiver_id',
        'receiver_type',
        'sender_id',
        'user_type_sender',
        'sender_type',
    ];
}
