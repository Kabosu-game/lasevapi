<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OracleDraw extends Model
{
    protected $fillable = ['user_id', 'oracle_card_id', 'cycle', 'personalized_interpretation', 'drawn_at'];

    protected $casts = ['drawn_at' => 'datetime'];

    public function card()
    {
        return $this->belongsTo(OracleCard::class, 'oracle_card_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
