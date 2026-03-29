<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'body',
        'cover_image',
        'author_id',
        'is_premium',
        'category',
    ];

    /**
     * Relation avec l'auteur
     */
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /**
     * Galerie d'images (relation polymorphique)
     */
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
