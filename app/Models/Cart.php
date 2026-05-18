<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    // Define the table associated with this model
    protected $table = 'carts';

    // Allow mass assignment only for these fields
    protected $fillable = [
        'user_id',
        // 'tool_id',
        'device_id',
        'old_price', // NEW
        'total_price', // NEW
        // 'quantity',
    ];

    /**
     * Relationship with the user model (for logged-in users).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relationship with the tool model.
     */
    public function tool()
    {
        return $this->belongsTo(CommercialTool::class, 'tool_id');
    }

    /**
     * Relationship with the guest model (for guests).
     */

    public function cartItems()
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }
}
