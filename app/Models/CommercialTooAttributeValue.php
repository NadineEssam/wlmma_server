<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialTooAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool_id',
        'attribute_name_en',
        'attribute_name_ar',
    ];

    public function attribute()
    {
        return $this->belongsTo(CommercialTooAttribute::class, 'tool_attribute_id');
    }
}
