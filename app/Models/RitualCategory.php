<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Catégorie de l'Espace Sacré des Lois. */
class RitualCategory extends Model
{
    /** Icônes proposées dans l'admin (noms d'icônes Material affichées par l'app). */
    public const ICONS = [
        'menu_book' => 'Livre',
        'home' => 'Maison',
        'eco' => 'Feuille',
        'park' => 'Arbre',
        'water_drop' => 'Goutte d\'eau',
        'self_improvement' => 'Méditation',
        'spa' => 'Lotus',
        'auto_stories' => 'Livre ouvert',
        'local_fire_department' => 'Flamme',
        'brightness_7' => 'Soleil',
        'nightlight' => 'Lune',
    ];

    protected $fillable = ['slug', 'name', 'description', 'icon', 'sort_order', 'is_active'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function rituals()
    {
        return $this->hasMany(Ritual::class);
    }
}
