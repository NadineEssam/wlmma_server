<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'street',
        'city',
        'state',
        'country',
        'postcode',
        'email',
        'first_name',
        'last_name',
        'payment_method',
        'referencedId',
        'registration_id',
        'payment_id',
        'amount',
        'user_id',
        'order_id',
        'cart_id',
        'book_id',
        'card_number',
        'describtion',
        'payment_status',
        'payment_checkout_id',
        'entity_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function book()
    {
        return $this->belongsTo(Booking::class);
    }
}
