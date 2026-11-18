<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Trace d'affichage d'un message du compagnon : sert à respecter le délai avant de le remontrer. */
class CompanionMessageView extends Model
{
    public $timestamps = false;

    protected $fillable = ['companion_message_id', 'user_id', 'shown_at'];

    protected $casts = ['shown_at' => 'datetime'];
}
