<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'description',
        'event_date',
        'location',
        'duration',
        'contact',
        'price',
        'current_participants',
        'status',
    ];

    /**
     * Galerie d'images (relation polymorphique)
     */
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }

    /** Inscriptions reçues depuis l'app. */
    public function registrations()
    {
        return $this->hasMany(EventRegistration::class)->latest();
    }

    /** Les inscriptions ne sont possibles que pour un événement à venir ou en cours. */
    public function acceptsRegistrations(): bool
    {
        return in_array($this->status ?? 'upcoming', ['upcoming', 'ongoing'], true);
    }
}
