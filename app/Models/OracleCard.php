<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OracleCard extends Model
{
    protected $fillable = ['name', 'message', 'interpretation', 'keywords', 'image', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    protected $appends = ['image_url'];

    public function draws()
    {
        return $this->hasMany(OracleDraw::class);
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
