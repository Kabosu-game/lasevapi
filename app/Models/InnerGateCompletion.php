<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Porte terminée par un utilisateur : elle reste franchie même si des étapes sont ajoutées ensuite. */
class InnerGateCompletion extends Model
{
    protected $fillable = ['user_id', 'inner_gate_id', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];
}
