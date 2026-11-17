<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FitnessLesson extends Model
{
    protected $fillable = [
        'fitness_program_id', 'day_number', 'title', 'description', 'video_path', 'video_url',
        'duration_minutes', 'sort_order',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['video'];

    public function program()
    {
        return $this->belongsTo(FitnessProgram::class, 'fitness_program_id');
    }

    /** URL de lecture : fichier téléversé en priorité, sinon lien externe. */
    public function getVideoAttribute(): ?string
    {
        if ($this->video_path) {
            return rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->video_path, '/');
        }
        return $this->video_url ?: null;
    }
}
