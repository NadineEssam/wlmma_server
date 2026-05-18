<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    // Define the table associated with this model
    protected $table = 'cart_items';

    // Allow mass assignment only for these fields
    protected $fillable = [
        // 'session_id',
        'cart_id',
        'tool_id',
        'attribute_id',
        'quantity',
    ];

    /**
     * Relationship with the Cart.
     */
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id');
    }

    /**
     * Relationship with the Tool.
     */
    public function tool()
    {
        return $this->belongsTo(CommercialTool::class, 'tool_id');
    }
}
