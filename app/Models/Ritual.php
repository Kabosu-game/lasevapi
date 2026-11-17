<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tutoriel de l'Espace Sacré des Lois. */
class Ritual extends Model
{
    protected $fillable = [
        'ritual_category_id', 'title', 'summary', 'objectives', 'materials', 'steps', 'precautions', 'traditions',
        'duration_minutes', 'image', 'video_url', 'is_published', 'sort_order',
    ];

    protected $casts = [
        'objectives' => 'array',
        'materials' => 'array',
        'steps' => 'array',
        'precautions' => 'array',
        'duration_minutes' => 'integer',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = ['image_url'];

    public function category()
    {
        return $this->belongsTo(RitualCategory::class, 'ritual_category_id');
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
