<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chef extends Model
{
    protected $fillable = ['name', 'image', 'role', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
