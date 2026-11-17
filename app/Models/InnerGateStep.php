<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Étape d'une porte : enseignement, méditation, respiration, introspection ou exercice. */
class InnerGateStep extends Model
{
    public const TYPES = [
        'lesson' => 'Enseignement',
        'meditation' => 'Méditation',
        'breathing' => 'Respiration',
        'introspection' => 'Introspection (questions)',
        'exercise' => 'Exercice pratique',
    ];

    private const AUDIO_EXTENSIONS = ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'oga', 'flac'];

    protected $fillable = [
        'inner_gate_id', 'position', 'type', 'title', 'content', 'prompts', 'duration_minutes', 'media_url', 'media_path',
    ];

    protected $casts = [
        'prompts' => 'array',
        'position' => 'integer',
        'duration_minutes' => 'integer',
    ];

    protected $hidden = ['media_path', 'media_url'];

    protected $appends = ['media', 'media_kind'];

    public function gate()
    {
        return $this->belongsTo(InnerGate::class, 'inner_gate_id');
    }

    /** Fichier téléversé prioritaire sur le lien. */
    public function getMediaAttribute(): ?string
    {
        if ($this->media_path) {
            return rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->media_path, '/');
        }
        return $this->media_url ?: null;
    }

    /** « audio » ou « video » selon l'extension, pour choisir le lecteur dans l'app. */
    public function getMediaKindAttribute(): ?string
    {
        $media = $this->media;
        if (!$media) {
            return null;
        }
        $extension = strtolower(pathinfo(parse_url($media, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));
        return in_array($extension, self::AUDIO_EXTENSIONS, true) ? 'audio' : 'video';
    }
}
