<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AffirmationCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'color',
        'order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relation avec les affirmations
     */
    public function affirmations(): HasMany
    {
        return $this->hasMany(Affirmation::class, 'category_id');
    }

    /**
     * Obtenir le nombre d'affirmations actives dans cette catégorie
     */
    public function getActiveAffirmationsCountAttribute(): int
    {
        return $this->affirmations()->count();
    }
}

