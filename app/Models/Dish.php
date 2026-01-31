<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dish extends Model
{
    protected $fillable = ['name', 'image', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
