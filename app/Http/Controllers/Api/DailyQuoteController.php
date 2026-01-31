<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyQuote;
use Illuminate\Http\Request;

class DailyQuoteController extends Controller
{
    /**
     * Obtenir la phrase du jour selon l'heure actuelle
     */
    public function getCurrentQuote(Request $request)
    {
        $date = $request->input('date') ? \Carbon\Carbon::parse($request->input('date')) : null;
        $quote = DailyQuote::getCurrentQuote($date);
        
        return response()->json([
            'success' => true,
            'data' => [
                'quote' => $quote,
                'hour' => now()->hour,
                'date' => now()->format('Y-m-d'),
            ],
        ]);
    }

    /**
     * Obtenir les 3 phrases du jour (pour l'app - affichage selon l'heure)
     */
    public function getTodayQuotes(Request $request)
    {
        $date = $request->input('date') ? \Carbon\Carbon::parse($request->input('date')) : null;
        $quotes = DailyQuote::getQuotesForDate($date);
        
        return response()->json([
            'success' => true,
            'data' => [
                'quotes' => $quotes,
                'date' => ($date ?? now())->format('Y-m-d'),
            ],
        ]);
    }
}

