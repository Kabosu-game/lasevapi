<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Inscription à un événement envoyée depuis l'app. */
class EventRegistration extends Model
{
    protected $fillable = ['event_id', 'first_name', 'last_name', 'phone'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
