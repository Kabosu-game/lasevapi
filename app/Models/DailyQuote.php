<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DailyQuote extends Model
{
    protected $fillable = [
        'quote_1',
        'quote_2',
        'quote_3',
        'quote_date',
        'is_active',
    ];

    protected $casts = [
        'quote_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Obtenir la phrase du jour selon l'heure actuelle
     */
    public static function getCurrentQuote(?Carbon $date = null): ?string
    {
        $date = $date ?? Carbon::now();
        
        // Récupérer la citation pour cette date
        $dailyQuote = self::where('quote_date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->first();
        
        // Si pas de citation pour cette date, prendre la dernière active ou une par défaut
        if (!$dailyQuote) {
            $dailyQuote = self::where('is_active', true)
                ->where('quote_date', '<=', $date->format('Y-m-d'))
                ->orderBy('quote_date', 'desc')
                ->first();
        }
        
        if (!$dailyQuote) {
            // Citations par défaut
            $defaultQuotes = [
                'La paix intérieure commence par le sourire de l\'âme.',
                'Chaque jour est une nouvelle opportunité de grandir.',
                'La gratitude transforme ce que nous avons en suffisance.',
            ];
        } else {
            $defaultQuotes = [
                $dailyQuote->quote_1,
                $dailyQuote->quote_2,
                $dailyQuote->quote_3,
            ];
        }
        
        // Déterminer l'index selon l'heure
        $hour = $date->hour;
        if ($hour < 12) {
            return $defaultQuotes[0]; // 00:00 - 11:59
        } elseif ($hour < 23) {
            return $defaultQuotes[1]; // 12:00 - 22:59
        } else {
            return $defaultQuotes[2]; // 23:00 - 23:59
        }
    }

    /**
     * Obtenir les 3 phrases du jour pour une date (pour l'app)
     */
    public static function getQuotesForDate(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::now();
        $dailyQuote = self::where('quote_date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->first();

        if (!$dailyQuote) {
            $dailyQuote = self::where('is_active', true)
                ->where('quote_date', '<=', $date->format('Y-m-d'))
                ->orderBy('quote_date', 'desc')
                ->first();
        }

        if (!$dailyQuote) {
            return [
                'La paix intérieure commence par le sourire de l\'âme.',
                'Chaque jour est une nouvelle opportunité de grandir.',
                'La gratitude transforme ce que nous avons en suffisance.',
            ];
        }

        return [
            $dailyQuote->quote_1,
            $dailyQuote->quote_2,
            $dailyQuote->quote_3,
        ];
    }
}

