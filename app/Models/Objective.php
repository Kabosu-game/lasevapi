<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Objectif proposé lors de l'inscription dans l'app. */
class Objective extends Model
{
    protected $fillable = ['name', 'sort_order'];

    protected $casts = [
        'sort_order' => 'integer',
    ];
}
