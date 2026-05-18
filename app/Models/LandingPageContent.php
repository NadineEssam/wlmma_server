<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandingPageContent extends Model
{
    use HasFactory;

    protected $table = 'landing_page_content';

    protected $fillable = ['page_id', 'content'];

    protected $casts = [
        'content' => 'array',
    ];
}
