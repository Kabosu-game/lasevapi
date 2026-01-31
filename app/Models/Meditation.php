<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meditation extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'duration',
    ];

    protected $appends = ['audio_url', 'image_url'];

    /**
     * Relation polymorphique : une méditation a un media (audio)
     */
    public function media()
    {
        return $this->morphOne(Media::class, 'mediable');
    }

    /**
     * URL de l'audio pour l'app Flutter (compatible avec le modèle Meditation Flutter)
     */
    public function getAudioUrlAttribute(): string
    {
        if (!$this->relationLoaded('media') || !$this->media) {
            return '';
        }
        $path = $this->media->file_path;
        return str_starts_with($path, 'http') ? $path : (rtrim(config('app.url'), '/') . '/storage/' . ltrim($path, '/'));
    }

    /**
     * URL de l'image (placeholder ou future couverture) pour Flutter
     */
    public function getImageUrlAttribute(): string
    {
        return '';
    }
}
