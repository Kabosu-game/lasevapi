<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeMenuItem extends Model
{
    protected $fillable = ['slug', 'name', 'image', 'sort_order', 'is_visible'];

    protected $casts = [
        'sort_order' => 'integer',
        'is_visible' => 'boolean',
    ];

    /** Anciennes rubriques remplacées par la nouvelle architecture (slug => rubrique qui la remplace). */
    public const LEGACY = [
        'affirmation' => 'oracles',
        'meditation' => 'activities',
        'gratitude' => 'journal',
    ];

    public function isLegacy(): bool
    {
        return array_key_exists($this->slug, self::LEGACY);
    }
}
