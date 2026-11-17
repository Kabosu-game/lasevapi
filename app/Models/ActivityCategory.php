<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Catégorie de l'espace ACTIVITÉS (la clé correspond au champ « category » des méditations). */
class ActivityCategory extends Model
{
    protected $fillable = [
        'key',
        'name',
        'description',
        'icon',
        'color',
        'with_breathing_exercises',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'with_breathing_exercises' => 'boolean',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /** Icônes disponibles dans l'app (nom Material => libellé dans l'admin). */
    public const ICONS = [
        'self_improvement' => 'Posture de méditation',
        'directions_run' => 'Course',
        'spa' => 'Fleur (détente)',
        'bedtime' => 'Lune (sommeil)',
        'headphones' => 'Casque',
        'air' => 'Souffle',
        'music_note' => 'Note de musique',
        'nature' => 'Arbre',
        'water_drop' => 'Goutte d\'eau',
        'wb_sunny' => 'Soleil',
        'favorite' => 'Cœur',
        'psychology' => 'Esprit',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function meditations()
    {
        return $this->hasMany(Meditation::class, 'category', 'key');
    }

    /** Clé => nom de toutes les catégories (formulaires et listes de l'admin). */
    public static function options(): array
    {
        return self::ordered()->pluck('name', 'key')->all();
    }

    /** Clés des catégories visibles dans l'app. */
    public static function activeKeys(): array
    {
        return self::where('is_active', true)->ordered()->pluck('key')->all();
    }
}
