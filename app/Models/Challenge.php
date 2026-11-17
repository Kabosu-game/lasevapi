<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Challenge extends Model
{
    public const TYPES = [
        'meditation' => 'Méditation',
        'gratitude' => 'Gratitude',
        'yoga' => 'Yoga',
        'hydration' => 'Hydratation',
        'discipline' => 'Discipline',
        'spiritual' => 'Spirituel',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'title', 'description', 'objective', 'type', 'target_days', 'points_per_day',
        'completion_bonus', 'badge_name', 'reward', 'image', 'starts_at', 'ends_at',
        'is_published', 'recurring_monthly', 'parent_challenge_id', 'closed_at',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'ends_at' => 'date',
        'is_published' => 'boolean',
        'recurring_monthly' => 'boolean',
        'closed_at' => 'datetime',
        'target_days' => 'integer',
        'points_per_day' => 'integer',
        'completion_bonus' => 'integer',
    ];

    protected $appends = ['status', 'image_url'];

    public function participants()
    {
        return $this->hasMany(ChallengeParticipant::class);
    }

    public function parent()
    {
        return $this->belongsTo(Challenge::class, 'parent_challenge_id');
    }

    /** draft | scheduled | active | ended | closed */
    public function getStatusAttribute(): string
    {
        if ($this->closed_at) {
            return 'closed';
        }
        if (!$this->is_published) {
            return 'draft';
        }
        $today = now()->startOfDay();
        if ($this->starts_at && $today->lt($this->starts_at)) {
            return 'scheduled';
        }
        if ($this->ends_at && $today->gt($this->ends_at)) {
            return 'ended';
        }
        return 'active';
    }

    public function isOpenForCheckin(): bool
    {
        return $this->status === 'active';
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

    /** Participants classés : points, puis jours accomplis, puis ancienneté. */
    public function leaderboardQuery()
    {
        return $this->participants()
            ->with('user:id,name')
            ->orderByDesc('points')
            ->orderByDesc('days_completed')
            ->orderBy('updated_at');
    }
}
