<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuppliesRent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id',
        'supplier_id',
        'renter_id',
        'quantity',
        'price',
        'per_day_Or_month',
    ];

    public function supplier()
    {
        // Use supplier_id not user_id
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function renter()
    {
        return $this->belongsTo(User::class, 'renter_id');
    }

    public function tool()
    {
        return $this->belongsTo(CommercialTool::class, 'tool_id');
    }
}
