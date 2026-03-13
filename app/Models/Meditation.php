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
        $path = trim($this->media->file_path);
        if ($path === '') {
            return '';
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        // Chemin relatif : URL publique via le lien symbolique public/storage (ex. /storage/audios/meditations/...)
        $path = ltrim($path, '/');
        if (!preg_match('#^storage/#', $path)) {
            $path = 'storage/' . $path;
        }
        $base = rtrim(config('app.url'), '/');
        return $base . '/' . $path;
    }

    /**
     * URL de l'image (placeholder ou future couverture) pour Flutter
     */
    public function getImageUrlAttribute(): string
    {
        return '';
    }
}
