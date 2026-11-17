<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Une des 7 Portes Intérieures du parcours « Face à toi ». */
class InnerGate extends Model
{
    protected $fillable = [
        'number', 'name', 'theme', 'color', 'description', 'affirmation', 'companion_message', 'image', 'is_active',
    ];

    protected $casts = [
        'number' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['image_url'];

    public function steps()
    {
        return $this->hasMany(InnerGateStep::class)->orderBy('position')->orderBy('id');
    }

    public function completions()
    {
        return $this->hasMany(InnerGateCompletion::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }
        return str_starts_with($this->image, 'http')
            ? $this->image
            : rtrim(config('app.url'), '/') . '/storage/' . ltrim($this->image, '/');
    }
}
