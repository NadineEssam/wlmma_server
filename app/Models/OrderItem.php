<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'tool_id',
        'quantity',
        'price'
    ];

    /**
     * Define relationship with the Order model
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Define relationship with the CommercialTool model
     */
    public function tool()
    {
        return $this->belongsTo(CommercialTool::class, 'tool_id');
    }
}
