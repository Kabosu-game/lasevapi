<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Exercice de respiration guidé (durées en secondes ; 0 = phase absente). */
class BreathingPattern extends Model
{
    protected $fillable = [
        'name',
        'description',
        'inhale_seconds',
        'hold_in_seconds',
        'exhale_seconds',
        'hold_out_seconds',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'inhale_seconds' => 'integer',
        'hold_in_seconds' => 'integer',
        'exhale_seconds' => 'integer',
        'hold_out_seconds' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Rythme lisible, ex. « 4-7-8 » ou « 4-4-4-4 ». */
    public function getRhythmAttribute(): string
    {
        return collect([$this->inhale_seconds, $this->hold_in_seconds, $this->exhale_seconds, $this->hold_out_seconds])
            ->filter(fn ($s) => $s > 0)
            ->implode('-');
    }
}
