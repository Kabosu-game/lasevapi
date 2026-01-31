<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeMenuItem extends Model
{
    protected $fillable = ['slug', 'name', 'image', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
