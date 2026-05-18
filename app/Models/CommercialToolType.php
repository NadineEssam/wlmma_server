<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialToolType extends Model
{
    use HasFactory;
     protected $fillable = [
        'name_en',
        'name_ar',
       
    ];
}
