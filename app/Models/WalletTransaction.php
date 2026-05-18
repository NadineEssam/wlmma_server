<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'user_id',
        'book_id',
        'cart_id',
        'order_id',
        'provider_id',
        'type',
        'amount',
        'reference',
        'reason',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class, 'book_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
