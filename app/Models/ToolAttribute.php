<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ToolAttribute extends Model
{
    use HasFactory;
    protected $fillable = [
        'tool_id',
        'attribute_name_en',
        'attribute_name_ar'
    ];
    
    public function ToolAttributeValues(){
        return $this->hasMany(ToolAttributeValues::class,'tool_attribute_id');
    }
}
