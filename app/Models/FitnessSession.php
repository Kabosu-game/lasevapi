<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FitnessSession extends Model
{
    protected $fillable = ['user_id', 'fitness_lesson_id', 'duration_minutes', 'completed_at'];

    protected $casts = ['completed_at' => 'datetime'];

    public function lesson()
    {
        return $this->belongsTo(FitnessLesson::class, 'fitness_lesson_id');
    }
}
