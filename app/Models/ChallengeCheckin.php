<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChallengeCheckin extends Model
{
    protected $fillable = ['challenge_participant_id', 'checkin_date'];

    protected $casts = ['checkin_date' => 'date'];

    public function participant()
    {
        return $this->belongsTo(ChallengeParticipant::class, 'challenge_participant_id');
    }
}
