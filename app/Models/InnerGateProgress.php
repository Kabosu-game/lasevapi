<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InnerGateProgress extends Model
{
    protected $table = 'inner_gate_progress';

    protected $fillable = ['user_id', 'inner_gate_step_id', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];

    public function step()
    {
        return $this->belongsTo(InnerGateStep::class, 'inner_gate_step_id');
    }
}
