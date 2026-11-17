<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FitnessProgram extends Model
{
    public const CATEGORIES = ['yoga' => 'Yoga', 'training' => 'Entraînement sportif'];
    public const LEVELS = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];
    public const DURATIONS = [7, 21, 30];

    protected $fillable = [
        'title', 'description', 'category', 'level', 'duration_days', 'image', 'is_published', 'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'duration_days' => 'integer',
        'sort_order' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function lessons()
    {
        return $this->hasMany(FitnessLesson::class)->orderBy('day_number')->orderBy('sort_order');
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return str_starts_with($this->image, 'http')
            ? $this->image
            : rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->image, '/');
    }
}
