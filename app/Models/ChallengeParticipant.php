<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeParticipant extends Model
{
    protected $fillable = [
        'challenge_id', 'user_id', 'days_completed', 'points', 'last_checkin_date',
        'completed_at', 'final_rank', 'reward_granted',
    ];

    protected $casts = [
        'last_checkin_date' => 'date',
        'completed_at' => 'datetime',
        'reward_granted' => 'boolean',
        'days_completed' => 'integer',
        'points' => 'integer',
    ];

    public function challenge()
    {
        return $this->belongsTo(Challenge::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function checkins()
    {
        return $this->hasMany(ChallengeCheckin::class);
    }
}
